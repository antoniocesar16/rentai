<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionAPIService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.evolution.url', '');
        $this->apiKey = config('services.evolution.api_key', '');
    }

    public function createInstance(string $instanceName, $number = null): array
    {
        $data = [
            'instanceName' => $instanceName,
            'qrcode' => true,
            'integration' => "WHATSAPP-BAILEYS",
        ];

        if ($number) {
            $data['number'] = $number;
        }

        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
        ])->post("{$this->baseUrl}/instance/create", $data);
        return $response->json();
    }

    public function connectInstance(string $instanceName): array
    {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
        ])->get("{$this->baseUrl}/instance/connect/{$instanceName}");

        return $response->json();
    }

    public function getPairingCode(string $instanceName): array
    {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
        ])->get("{$this->baseUrl}/instance/connect/{$instanceName}");
        Log::debug('Resposta do getPairingCode', ['response' => $response->json()]);

        return $response->json();
    }

    public function getQRCode(string $instanceName): array
    {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
        ])->get("{$this->baseUrl}/instance/connect/{$instanceName}");

        return $response->json();
    }

    public function getInstanceStatus(string $instanceName): array
    {
        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
        ])->get("{$this->baseUrl}/instance/fetchInstances", [
                    'instanceName' => $instanceName
                ]);

        $data = $response->json();
        Log::debug('Fetch instance response', ['response' => $data]);

        if (isset($data[0]['connectionStatus'])) {
            return ['state' => $data[0]['connectionStatus']];
        }

        return ['state' => 'disconnected'];
    }

    public function deleteInstance(string $instanceName): array
    {
        try {
            Log::debug("Tentando deletar instância {$instanceName} na Evolution API");
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Accept' => 'application/json',
                ])->delete("{$this->baseUrl}/instance/delete/{$instanceName}");

            Log::debug("Instância {$instanceName} deletada", ['response' => $response->json()]);
            return $response->json() ?? ['success' => true];
        } catch (\Exception $e) {
            Log::warning("Erro ao deletar instância {$instanceName}: " . $e->getMessage());
            return ['success' => true, 'message' => 'Instância removida localmente'];
        }
    }

    public function logoutInstance(string $instanceName): array
    {
        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Accept' => 'application/json',
                ])->delete("{$this->baseUrl}/instance/logout/{$instanceName}");

            return $response->json() ?? ['success' => true];
        } catch (\Exception $e) {
            Log::warning("Erro ao desconectar instância {$instanceName}: " . $e->getMessage());
            return ['success' => true, 'message' => 'Desconectado localmente'];
        }
    }

    public function sendMessage(string $instanceName, string $numero, string $mensagem, array $options = []): array
    {
        try {
            $normalizedNumber = $this->normalizeRecipientNumber($numero);
            $payload = [
                'number' => $normalizedNumber,
                'text' => $mensagem,
                'delay' => (int) ($options['delay'] ?? 0),
                'linkPreview' => (bool) ($options['linkPreview'] ?? true),
                'mentionsEveryOne' => (bool) ($options['mentionsEveryOne'] ?? false),
            ];

            if (!empty($options['mentioned']) && is_array($options['mentioned'])) {
                $payload['mentioned'] = array_values(array_filter($options['mentioned'], fn($item) => is_string($item) && trim($item) !== ''));
            }

            Log::info('Evolution sendText iniciado', [
                'instance' => $instanceName,
                'number_original' => $numero,
                'number_normalized' => $normalizedNumber,
                'message_length' => mb_strlen($mensagem),
            ]);

            if (!empty($options['quoted']) && is_array($options['quoted'])) {
                $payload['quoted'] = $options['quoted'];
            }

            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$this->baseUrl}/message/sendText/{$instanceName}", $payload);

            $responseBody = $response->json();

            if (!$response->successful()) {
                Log::warning('Evolution sendText falhou', [
                    'instance' => $instanceName,
                    'number' => $normalizedNumber,
                    'status' => $response->status(),
                    'response' => $responseBody,
                ]);

                return [
                    'error' => true,
                    'status' => $response->status(),
                    'response' => $responseBody,
                ];
            }

            Log::info('Evolution sendText concluido', [
                'instance' => $instanceName,
                'number' => $normalizedNumber,
                'status' => $response->status(),
            ]);

            return $responseBody ?? ['success' => true];
        } catch (\Exception $e) {
            Log::error("Erro ao enviar mensagem: " . $e->getMessage());
            return ['error' => 'Falha ao enviar mensagem', 'message' => $e->getMessage()];
        }
    }

    /**
     * Envia uma mensagem longa em múltiplas partes (split) com delay entre elas
     * Útil para respostas que excedem limites de caracteres do WhatsApp
     */
    public function sendMessageBlock(string $instanceName, string $numero, string $mensagem, array $options = []): array
    {
        $maxLength = $options['maxLength'] ?? 1000; // WhatsApp suporta até ~4096 caracteres
        $delay = $options['delay'] ?? 1000; // 1 segundo entre mensagens
        $normalizedNumber = $this->normalizeRecipientNumber($numero);

        // Se a mensagem cabe em uma só, enviar normalmente
        if (mb_strlen($mensagem) <= $maxLength) {
            return $this->sendMessage($instanceName, $numero, $mensagem, $options);
        }

        // Dividir a mensagem em chunks
        $chunks = $this->splitMessageByLimit($mensagem, $maxLength);
        $totalChunks = count($chunks);

        Log::info('Evolution sendMessageBlock iniciado', [
            'instance' => $instanceName,
            'number' => $normalizedNumber,
            'original_length' => mb_strlen($mensagem),
            'chunks' => $totalChunks,
            'max_length' => $maxLength,
            'delay_ms' => $delay,
        ]);

        $results = [];
        foreach ($chunks as $index => $chunk) {
            // Aguardar delay antes de enviar (exceto na primeira)
            if ($index > 0) {
                usleep($delay * 1000); // Converter ms para microsegundos
            }

            $result = $this->sendMessage($instanceName, $numero, $chunk, [
                'linkPreview' => $options['linkPreview'] ?? true,
                'mentionsEveryOne' => $options['mentionsEveryOne'] ?? false,
                'delay' => 0, // Usar nosso delay, não o da API
            ]);

            $results[] = $result;

            // Log de cada chunk enviado
            Log::debug('Evolution sendMessageBlock - chunk enviado', [
                'instance' => $instanceName,
                'number' => $normalizedNumber,
                'chunk_index' => $index + 1,
                'chunk_total' => $totalChunks,
                'chunk_length' => mb_strlen($chunk),
                'success' => !isset($result['error']),
            ]);
        }

        Log::info('Evolution sendMessageBlock concluido', [
            'instance' => $instanceName,
            'number' => $normalizedNumber,
            'total_chunks' => $totalChunks,
        ]);

        return [
            'success' => true,
            'chunks_sent' => $totalChunks,
            'original_length' => mb_strlen($mensagem),
            'results' => $results,
        ];
    }

    /**
     * Divide uma mensagem em múltiplos chunks respeitando o limite de caracteres
     * Tenta quebrar em limites naturais (pontos, quebras de linha, espaços)
     */
    private function splitMessageByLimit(string $message, int $maxLength): array
    {
        if (mb_strlen($message) <= $maxLength) {
            return [$message];
        }

        $chunks = [];
        $current = '';
        $sentences = preg_split('/(?<=[.!?\n])\s+/u', $message) ?? [$message];

        foreach ($sentences as $sentence) {
            // Se a sentença sozinha já é maior que o limite, quebrar por caracteres
            if (mb_strlen($sentence) > $maxLength) {
                if (!empty($current)) {
                    $chunks[] = trim($current);
                    $current = '';
                }

                // Quebrar sentença grande em pedaços (com suporte a multibyte)
                $sentenceLength = mb_strlen($sentence);
                for ($i = 0; $i < $sentenceLength; $i += intval($maxLength / 2)) {
                    $chunk = mb_substr($sentence, $i, intval($maxLength / 2));
                    if (!empty(trim($chunk))) {
                        $chunks[] = trim($chunk);
                    }
                }
            } elseif (mb_strlen($current) + mb_strlen($sentence) + 1 > $maxLength) {
                // Não cabe no chunk atual, guardar o atual e começar novo
                if (!empty($current)) {
                    $chunks[] = trim($current);
                }
                $current = $sentence;
            } else {
                // Cabe no chunk atual
                $current = $current ? $current . ' ' . $sentence : $sentence;
            }
        }

        if (!empty($current)) {
            $chunks[] = trim($current);
        }

        return array_filter($chunks, fn($c) => !empty($c));
    }

    public function sendMediaMessage(
        string $instanceName,
        string $numero,
        string $media,
        string $caption = '',
        string $mimeType = 'image/jpeg',
        string $fileName = 'imovel.jpg',
        array $options = [],
    ): array {
        try {
            $normalizedNumber = $this->normalizeRecipientNumber($numero);
            $payload = [
                'number' => $normalizedNumber,
                'mediatype' => 'image',
                'mimetype' => $mimeType,
                'caption' => $caption,
                'media' => $media,
                'fileName' => $fileName,
                'delay' => (int) ($options['delay'] ?? 0),
                'linkPreview' => (bool) ($options['linkPreview'] ?? true),
                'mentionsEveryOne' => (bool) ($options['mentionsEveryOne'] ?? false),
            ];

            if (!empty($options['mentioned']) && is_array($options['mentioned'])) {
                $payload['mentioned'] = array_values(array_filter($options['mentioned'], fn($item) => is_string($item) && trim($item) !== ''));
            }

            if (!empty($options['quoted']) && is_array($options['quoted'])) {
                $payload['quoted'] = $options['quoted'];
            }

            $response = Http::timeout(20)
                ->connectTimeout(5)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$this->baseUrl}/message/sendMedia/{$instanceName}", $payload);

            $responseBody = $response->json();

            if (!$response->successful()) {
                Log::warning('Evolution sendMedia falhou', [
                    'instance' => $instanceName,
                    'number' => $normalizedNumber,
                    'status' => $response->status(),
                    'response' => $responseBody,
                ]);

                return [
                    'error' => true,
                    'status' => $response->status(),
                    'response' => $responseBody,
                ];
            }

            return $responseBody ?? ['success' => true];
        } catch (\Exception $e) {
            Log::error("Erro ao enviar midia: " . $e->getMessage());
            return ['error' => 'Falha ao enviar midia', 'message' => $e->getMessage()];
        }
    }

    private function normalizeRecipientNumber(string $number): string
    {
        $candidate = trim($number);

        if (str_contains($candidate, '@')) {
            $candidate = explode('@', $candidate)[0] ?? $candidate;
        }

        $digits = preg_replace('/\D+/', '', $candidate) ?? '';

        return $digits !== '' ? $digits : trim($number);
    }

    public function getProfileInfo(string $instanceName): array
    {
        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                ])->get("{$this->baseUrl}/instance/fetchInstances", [
                        'instanceName' => $instanceName
                    ]);

            $data = $response->json();
            Log::debug('Profile data from fetchInstances', ['response' => $data]);

            if (isset($data[0])) {
                return [
                    'name' => $data[0]['profileName'] ?? 'Usuário',
                    'about' => $data[0]['profileStatus'] ?? 'Olá! Eu estou usando o WhatsApp.',
                    'picture' => $data[0]['profilePicUrl'] ?? null,
                ];
            }

            return ['name' => 'Usuário', 'about' => 'Olá! Eu estou usando o WhatsApp.', 'picture' => null];
        } catch (\Exception $e) {
            Log::error("Erro ao buscar perfil: " . $e->getMessage());
            return ['name' => 'Usuário', 'about' => 'Olá! Eu estou usando o WhatsApp.', 'picture' => null];
        }
    }

    public function setupWebhook(string $instanceName, string $webhookUrl): array
    {
        try {
            $headers = [
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            $webhookData = [
                'enabled' => true,
                'url' => $webhookUrl,
                'webhookByEvents' => true,
                'webhookBase64' => false,
                'events' => [
                    'MESSAGES_UPSERT',
                    'MESSAGES_UPDATE',
                    'SEND_MESSAGE',
                    'CONNECTION_UPDATE',
                ],
            ];

            // Algumas versões da Evolution exigem o payload dentro da chave "webhook".
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders($headers)
                ->post("{$this->baseUrl}/webhook/set/{$instanceName}", [
                    'webhook' => $webhookData,
                ]);

            // Fallback para versões que aceitam payload flat.
            if (!$response->successful()) {
                $response = Http::timeout(10)
                    ->connectTimeout(5)
                    ->withHeaders($headers)
                    ->post("{$this->baseUrl}/webhook/set/{$instanceName}", $webhookData);
            }

            $responseBody = $response->json();

            if ($response->successful()) {
                Log::info("Webhook configurado para {$instanceName}", ['response' => $responseBody]);
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'response' => $responseBody,
                ];
            }

            $message = data_get($responseBody, 'response.message.0.0')
                ?? data_get($responseBody, 'message')
                ?? 'Erro desconhecido ao configurar webhook';

            Log::warning("Falha ao configurar webhook para {$instanceName}", [
                'status' => $response->status(),
                'response' => $responseBody,
            ]);

            return [
                'error' => true,
                'status' => $response->status(),
                'message' => (string) $message,
                'response' => $responseBody,
            ];
        } catch (\Exception $e) {
            Log::error("Erro ao configurar webhook: " . $e->getMessage());
            return ['error' => 'Falha ao configurar webhook', 'message' => $e->getMessage()];
        }
    }
}
