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
                'mentioned' => is_array($options['mentioned'] ?? null) ? $options['mentioned'] : [],
            ];

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
                'mentioned' => is_array($options['mentioned'] ?? null) ? $options['mentioned'] : [],
            ];

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
