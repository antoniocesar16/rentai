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
        $this->baseUrl = config('services.evolution.url', 'http://31.97.242.226:8080');
        $this->apiKey = config('services.evolution.api_key', '75f14a4f-7b78-458f-af98-3bed7f15e4eb');
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

    public function sendMessage(string $instanceName, string $numero, string $mensagem): array
    {
        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$this->baseUrl}/message/sendText/{$instanceName}", [
                        'number' => $numero,
                        'text' => $mensagem,
                    ]);

            return $response->json() ?? ['success' => true];
        } catch (\Exception $e) {
            Log::error("Erro ao enviar mensagem: " . $e->getMessage());
            return ['error' => 'Falha ao enviar mensagem', 'message' => $e->getMessage()];
        }
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
}