<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\WhatsappInstance;
use App\Models\WhatsappWebhookMessage;
use App\Services\EvolutionAPIService;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function __construct(
        private EvolutionAPIService $evolutionAPI,
        private GeminiService $geminiService,
    ) {}

    /**
     * Recebe webhooks de mensagens do WhatsApp via Evolution API
     */
    public function handleWebhook(Request $request, ?string $slug = null)
    {
        Log::info('Webhook request recebida', $this->buildWebhookLogContext($request, $slug));

        try {
            $validationResult = $this->validateWebhookData($request);
            if ($validationResult) {
                return $validationResult;
            }

            $instanceName = $this->extractInstanceNameFromPayload($request);

            // Localiza a instância pelo slug (preferencialmente) ou pelo instanceName do payload.
            $instanceQuery = WhatsappInstance::query();

            if ($slug) {
                $instanceQuery->where('webhook_slug', $slug);
            }

            if ($instanceName) {
                $instanceQuery->orWhere('instance_name', $instanceName);
            }

            if (!$slug && !$instanceName) {
                $instanceQuery->whereRaw('1 = 0');
            }

            $instance = $instanceQuery->firstOrFail();

            // Extrai dados da mensagem
            $data = $request->json()->all();
            $messageData = $data['data'] ?? [];
            $senderNumber = $data['sender']
                ?? ($messageData['key']['remoteJid'] ?? null)
                ?? ($messageData['key']['remoteJidAlt'] ?? null);
            $senderName = $messageData['pushName'] ?? null;
            $messageText = $messageData['message']['conversation']
                ?? $messageData['body']
                ?? '';
            $eventName = $data['event'] ?? null;

            // Armazena mensagem recebida para montar histórico de chat.
            $this->storeWebhookMessage(
                instance: $instance,
                direction: 'inbound',
                senderNumber: $senderNumber,
                senderName: $senderName,
                messageText: $messageText,
                eventName: $eventName,
                payload: $data,
            );

            Log::info("Mensagem recebida de {$senderNumber}: {$messageText}");

            // Analisa a intenção com Gemini
            $intent = $this->geminiService->analyzeIntent($messageText);

            // Processa a intenção
            if ($intent['intent'] === 'list_apartments' && $intent['confidence'] > 0.6) {
                $responseText = $this->respondWithApartmentList($instance, $senderNumber);
            } else {
                $responseText = $this->respondWithDefaultMessage($instance, $senderNumber);
            }

            // Armazena resposta do bot.
            $this->storeWebhookMessage(
                instance: $instance,
                direction: 'outbound',
                senderNumber: $senderNumber,
                senderName: $senderName,
                messageText: $responseText,
                eventName: 'bot.response',
                payload: [
                    'intent' => $intent,
                    'trigger_event' => $eventName,
                ],
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Valida dados do webhook
     */
    private function validateWebhookData(Request $request)
    {
        $data = $request->json()->all();
        $messageData = $data['data'] ?? null;

        if (!$messageData) {
            return response()->json(['success' => false, 'message' => 'No message data'], 400);
        }

        $senderNumber = $data['sender']
            ?? ($messageData['key']['remoteJid'] ?? null)
            ?? ($messageData['key']['remoteJidAlt'] ?? null);
        $messageText = $messageData['message']['conversation']
            ?? $messageData['body']
            ?? '';

        if (!$senderNumber || !$messageText) {
            return response()->json(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        return null;
    }

    /**
     * Monta contexto de log para cada request recebida no webhook.
     */
    private function buildWebhookLogContext(Request $request, ?string $slug): array
    {
        $headers = [
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->header('User-Agent'),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
        ];

        return [
            'slug' => $slug,
            'instance_name' => $this->extractInstanceNameFromPayload($request),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'headers' => $headers,
            'payload' => $request->all(),
        ];
    }

    /**
     * Tenta extrair o nome da instância da estrutura de payload da Evolution API.
     */
    private function extractInstanceNameFromPayload(Request $request): ?string
    {
        $payload = $request->json()->all();

        return $payload['instance']
            ?? $payload['instanceName']
            ?? $payload['data']['instance']
            ?? $payload['data']['instanceName']
            ?? null;
    }


    /**
     * Responde com lista de apartamentos
     */
    private function respondWithApartmentList($instance, $senderNumber): string
    {
        // Busca apartamentos
        $properties = Property::select('id', 'title', 'price', 'location', 'description')
            ->limit(5)
            ->get()
            ->toArray();

        // Formata resposta
        $response = $this->geminiService->formatApartmentList($properties);

        // Envia resposta via WhatsApp
        $this->evolutionAPI->sendMessage(
            $instance->instance_name,
            $senderNumber,
            $response
        );

        Log::info("Resposta de listagem enviada para {$senderNumber}");

        return $response;
    }

    /**
     * Responde com mensagem padrão
     */
    private function respondWithDefaultMessage($instance, $senderNumber): string
    {
        $response = "Olá! 👋\n\nEscreva *LISTAR* para ver nossos apartamentos disponíveis, ou envie sua dúvida. Estamos aqui para ajudar! 😊";

        $this->evolutionAPI->sendMessage(
            $instance->instance_name,
            $senderNumber,
            $response
        );

        return $response;
    }

    private function storeWebhookMessage(
        WhatsappInstance $instance,
        string $direction,
        ?string $senderNumber,
        ?string $senderName,
        ?string $messageText,
        ?string $eventName,
        ?array $payload,
    ): void {
        WhatsappWebhookMessage::create([
            'whatsapp_instance_id' => $instance->id,
            'instance_name' => $instance->instance_name,
            'event' => $eventName,
            'direction' => $direction,
            'sender_number' => $senderNumber,
            'sender_name' => $senderName,
            'message_text' => $messageText,
            'payload' => $payload,
        ]);
    }

}
