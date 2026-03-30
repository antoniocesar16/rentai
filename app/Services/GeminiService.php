<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
    }

    /**
     * Analisa a mensagem do usuário para determinar se é um pedido de listagem
     * Retorna true ou false e a intenção detectada.
     */
    public function analyzeIntent(string $userMessage): array
    {
        $result = ['intent' => 'unknown', 'confidence' => 0];

        if (!$this->apiKey) {
            Log::warning('Gemini API key não configurada');
            return $result;
        }

        $systemPrompt = <<<PROMPT
Você é um assistente de bot de WhatsApp para uma plataforma de aluguel de apartamentos.
Analise a mensagem do usuário e identifique sua intenção.

Possíveis intenções:
- "list_apartments": O usuário quer listar/ver apartamentos disponíveis (keywords: listar, ver, mostrar, quais, disponíveis, apartamentos)
- "unknown": Outra intenção não identificada

Responda APENAS em JSON com a estrutura:
{
  "intent": "list_apartments" ou "unknown",
  "confidence": número entre 0 e 1,
  "message": "breve explicação da intenção detectada"
}
PROMPT;

        $userPrompt = "Analise esta mensagem do usuário: '{$userMessage}'";

        try {
            $response = Http::timeout(30)
                ->post($this->baseUrl . '?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $systemPrompt . "\n\n" . $userPrompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 200,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                // Extrai JSON da resposta
                preg_match('/\{.*\}/s', $content, $matches);
                if ($matches) {
                    $parsed = json_decode($matches[0], true);
                    if ($parsed) {
                        $result = $parsed;
                        Log::debug('Gemini analysis result', $result);
                    }
                }
            } else {
                Log::warning('Erro ao chamar Gemini API', ['response' => $response->json()]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao analisar intenção com Gemini: ' . $e->getMessage());
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
}

