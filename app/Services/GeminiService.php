<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $fallbackModel = 'gemini-3-flash-preview';
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->model = config('services.gemini.model', 'gemini-3-flash-preview');
    }

    public function testPrompt(string $prompt, ?string $model = null, bool $verifySsl = true): array
    {
        return $this->callModel($prompt, [
            'temperature' => 0.2,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 200,
        ], $model, $verifySsl);
    }

    /**
     * Analisa a mensagem do usuário para determinar se é um pedido de listagem
     * Retorna true ou false e a intenção detectada.
     */
    public function analyzeIntent(string $userMessage): array
    {
        $result = ['intent' => 'unknown', 'confidence' => 0];
        $localIntent = $this->detectIntentLocally($userMessage);

        if ($localIntent['intent'] === 'list_apartments') {
            Log::info('Gemini analyzeIntent fallback local aplicado', [
                'intent' => $localIntent['intent'],
                'confidence' => $localIntent['confidence'],
                'message_length' => mb_strlen($userMessage),
            ]);

            $result = $localIntent;
        }

        Log::info('Gemini analyzeIntent iniciado', [
            'message_length' => mb_strlen($userMessage),
            'model_config' => $this->model,
        ]);

        if (!$this->apiKey) {
            Log::warning('Gemini API key não configurada');
            $result['message'] = 'API key not configured';
        }

        $systemPrompt = <<<PROMPT
Você é um assistente de bot de WhatsApp para uma plataforma de aluguel de apartamentos.
Analise a mensagem do usuário e identifique sua intenção.

Possíveis intenções:

Responda APENAS em JSON com a estrutura:
{
  "intent": "list_apartments" ou "unknown",
  "confidence": número entre 0 e 1,
  "message": "breve explicação da intenção detectada"
}
PROMPT;

        $userPrompt = "Analise esta mensagem do usuário: '{$userMessage}'";

        try {
            $response = $this->callModel($systemPrompt . "\n\n" . $userPrompt, [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 500,
            ]);

            if ($response['successful']) {
                $content = $response['content'] ?? '';

                preg_match('/\{.*\}/s', $content, $matches);
                if ($matches) {
                    $parsed = json_decode($matches[0], true);
                    if ($parsed) {
                        $result = $parsed;
                        Log::debug('Gemini analysis result', $result);
                    }
                }

                Log::info('Gemini analyzeIntent concluido', [
                    'intent' => $result['intent'] ?? 'unknown',
                    'confidence' => $result['confidence'] ?? 0,
                    'model' => $response['model'] ?? $this->model,
                ]);

                if (($result['intent'] ?? 'unknown') === 'unknown') {
                    $fallbackIntent = $this->detectIntentLocally($userMessage);
                    if ($fallbackIntent['intent'] !== 'unknown') {
                        Log::info('Gemini analyzeIntent fallback local após resposta unknown', [
                            'intent' => $fallbackIntent['intent'],
                            'confidence' => $fallbackIntent['confidence'],
                            'model' => $response['model'] ?? $this->model,
                        ]);

                        return $fallbackIntent;
                    }
                }
            } else {
                Log::warning('Erro ao chamar Gemini API', ['response' => $response['raw'] ?? null, 'status' => $response['status'] ?? null]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao analisar intenção com Gemini: ' . $e->getMessage());
        }

        return $result; // Single exit point
    }

    /**
     * Gera resposta humanizada usando contexto recuperado do banco (RAG).
     */
    public function generateHumanizedReplyWithRag(
        string $userMessage,
        string $senderName,
        array $properties,
        array $conversationHistory,
    ): string {
        $result = '';

        Log::info('Gemini RAG iniciado', [
            'sender_name' => $senderName,
            'user_message_length' => mb_strlen($userMessage),
            'properties_count' => count($properties),
            'history_count' => count($conversationHistory),
            'model_config' => $this->model,
        ]);

        if (!$this->apiKey) {
            return $result;
        }

        $propertyContext = json_encode($properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $historyContext = json_encode($conversationHistory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $prompt = <<<PROMPT
Voce e um atendente humano de imobiliaria no WhatsApp.
Objetivo: responder de forma natural, curta e util, usando APENAS o contexto abaixo.

Regras:
- Nao invente informacoes que nao estejam no contexto.
- Se faltar dado, diga de forma transparente e faca 1 pergunta objetiva.
- Escreva em portugues brasileiro, tom amigavel e humano.
- Resposta com no maximo 450 caracteres.
- Nao responder em JSON.

Nome do cliente: {$senderName}
Mensagem do cliente: {$userMessage}

Contexto de imoveis (RAG):
{$propertyContext}

Historico recente da conversa:
{$historyContext}
PROMPT;

        try {
            $response = $this->callModel($prompt, [
                'temperature' => 0.8,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1500,
            ]);

            if ($response['successful']) {
                $content = $response['content'] ?? '';
                if (is_string($content)) {
                    $result = trim($content);
                }

                Log::info('Gemini RAG concluido', [
                    'response_length' => mb_strlen($result),
                    'model' => $response['model'] ?? $this->model,
                ]);
            } else {
                Log::warning('Falha ao gerar resposta RAG no Gemini', ['response' => $response['raw'] ?? null, 'status' => $response['status'] ?? null]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao gerar resposta RAG com Gemini: ' . $e->getMessage());
        }

        return $result;
    }



    /**
     * Gera resposta formatada com a lista de apartamentos
     */
    public function formatApartmentList(array $properties): string
    {
        if (empty($properties)) {
            return "Desculpe, no momento não temos apartamentos disponíveis para listar.";
        }

        $message = "🏠 *Apartamentos Disponíveis:*\n\n";
        
        foreach ($properties as $property) {
            $message .= "📍 *{$property['title']}*\n";
            $message .= "💰 Preço: R$ {$property['price']}\n";
            $message .= "🗺️ Localização: {$property['location']}\n";
            
            if (!empty($property['description'])) {
                $desc = substr($property['description'], 0, 100);
                $message .= "📝 {$desc}...\n";
            }
            
            $message .= "\n";
        }

        $message .= "Para mais informações, entre em contato conosco! 💬";
        
        return $message;
    }

    private function callModel(string $prompt, array $generationConfig = [], ?string $model = null, bool $verifySsl = true): array
    {
        if (!$this->apiKey) {
            return [
                'successful' => false,
                'status' => 0,
                'raw' => null,
                'content' => '',
                'error' => 'Gemini API key not configured',
            ];
        }

        $activeModel = $model ?: $this->model;
        if ($this->isLegacyModel($activeModel)) {
            Log::warning('Modelo Gemini legado detectado. Aplicando fallback automatico.', [
                'requested_model' => $activeModel,
                'fallback_model' => $this->fallbackModel,
            ]);
            $activeModel = $this->fallbackModel;
        }

        Log::info('Gemini request start', [
            'model' => $activeModel,
            'verify_ssl' => $verifySsl,
            'prompt_length' => mb_strlen($prompt),
            'prompt_preview' => mb_substr(trim($prompt), 0, 120),
            'generation_config' => $generationConfig,
        ]);

        try {
            $request = Http::timeout(30);

            if (!$verifySsl) {
                $request = $request->withoutVerifying();
            }

            $response = $request->post($this->baseUrl . $activeModel . ':generateContent?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ]);

            Log::info('Gemini request finish', [
                'model' => $activeModel,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'finish_reason' => $response->json('candidates.0.finishReason'),
                'response_id' => $response->json('responseId'),
                'prompt_tokens' => $response->json('usageMetadata.promptTokenCount'),
                'candidates_tokens' => $response->json('usageMetadata.candidatesTokenCount'),
                'thoughts_tokens' => $response->json('usageMetadata.thoughtsTokenCount'),
                'total_tokens' => $response->json('usageMetadata.totalTokenCount'),
            ]);

            return [
                'successful' => $response->successful(),
                'status' => $response->status(),
                'raw' => $response->json(),
                'content' => $response->json('candidates.0.content.parts.0.text', ''),
                'error' => $response->successful() ? null : ($response->json('error.message') ?? 'Gemini request failed'),
                'model' => $activeModel,
            ];
        } catch (ConnectionException $e) {
            Log::error('Gemini request connection error', [
                'model' => $activeModel,
                'verify_ssl' => $verifySsl,
                'message' => $e->getMessage(),
            ]);

            return [
                'successful' => false,
                'status' => 0,
                'raw' => null,
                'content' => '',
                'error' => 'SSL/conexão falhou: ' . $e->getMessage(),
                'model' => $activeModel,
            ];
        }
    }

    private function isLegacyModel(string $model): bool
    {
        $legacyModels = [
            'gemini-pro',
            'gemini-1.0-pro',
        ];

        return in_array(mb_strtolower(trim($model)), $legacyModels, true);
    }

    private function detectIntentLocally(string $userMessage): array
    {
        $text = mb_strtolower($userMessage);
        $normalizedText = $this->normalizeText($text);

        $listKeywords = [
            'lista',
            'listar',
            'ver apartamentos',
            'mostrar apartamentos',
            'quais apartamentos',
            'apartamentos',
            'imoveis',
            'imoveis disponiveis',
            'imoveis em',
            'tem apartamento',
            'tem apartamentos',
            'disponiveis',
            'disponivel',
            'disponíveis',
            'disponivel',
        ];

        foreach ($listKeywords as $keyword) {
            $normalizedKeyword = $this->normalizeText($keyword);

            if (str_contains($normalizedText, $normalizedKeyword)) {
                return [
                    'intent' => 'list_apartments',
                    'confidence' => 0.9,
                    'message' => 'Pedido de listagem identificado por palavras-chave.',
                ];
            }
        }

        return [
            'intent' => 'unknown',
            'confidence' => 0,
        ];
    }

    private function normalizeText(string $text): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        if ($normalized === false) {
            $normalized = $text;
        }

        return preg_replace('/\s+/', ' ', trim(mb_strtolower($normalized))) ?? trim(mb_strtolower($text));
    }
}

