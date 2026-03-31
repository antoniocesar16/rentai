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
     * Recebe webhooks de mensagens do WhatsApp via Evolution API.
     */
    public function handleWebhook(Request $request, ?string $slug = null)
    {
        Log::info('Webhook request recebida', $this->buildWebhookLogContext($request, $slug));

        try {
            $response = response()->json(['success' => true, 'ignored' => true]);

            if ($this->shouldProcessIncomingMessage($request)) {
                $validationResult = $this->validateWebhookData($request);

                if ($validationResult) {
                    $response = $validationResult;
                } else {
                    $response = $this->processInboundMessage($request, $slug);
                }
            } else {
                Log::info('Webhook ignorada (evento nao processavel para resposta).', [
                    'event' => $request->input('event'),
                    'instance' => $this->extractInstanceNameFromPayload($request),
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function processInboundMessage(Request $request, ?string $slug)
    {
        $instance = $this->resolveInstance($request, $slug);

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

        $intent = $this->geminiService->analyzeIntent($messageText);
        $detectedIntent = isset($intent['intent']) ? $intent['intent'] : null;
        $confidence = isset($intent['confidence']) ? (float) $intent['confidence'] : 0.0;

        if ($detectedIntent === 'list_apartments' && $confidence > 0.6) {
            $responseText = $this->respondWithApartmentList($instance, (string) $senderNumber);
        } else {
            $responseText = $this->respondWithDefaultMessage($instance, (string) $senderNumber);
        }

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
    }

    /**
     * Valida dados essenciais do webhook.
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
     * Evita loop: processa apenas mensagens recebidas do cliente.
     */
    private function shouldProcessIncomingMessage(Request $request): bool
    {
        $event = (string) ($request->input('event') ?? '');
        $normalizedEvent = strtolower(str_replace('_', '.', $event));

        if ($normalizedEvent !== 'messages.upsert') {
            return false;
        }

        $fromMe = (bool) data_get($request->all(), 'data.key.fromMe', false);
        if ($fromMe) {
            return false;
        }

        $messageText = data_get($request->all(), 'data.message.conversation')
            ?? data_get($request->all(), 'data.body');

        return is_string($messageText) && trim($messageText) !== '';
    }

    /**
    * Resolve a instancia alvo pelo slug ou instanceName do payload.
     */
    private function resolveInstance(Request $request, ?string $slug): WhatsappInstance
    {
        $instanceName = $this->extractInstanceNameFromPayload($request);
        $query = WhatsappInstance::query();

        if ($slug) {
            $query->where('webhook_slug', $slug);
        }

        if ($instanceName) {
            $query->orWhere('instance_name', $instanceName);
        }

        if (!$slug && !$instanceName) {
            $query->whereRaw('1 = 0');
        }

        return $query->firstOrFail();
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
    * Tenta extrair o nome da instancia da estrutura de payload da Evolution API.
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
     * Responde com lista de apartamentos.
     */
    private function respondWithApartmentList(WhatsappInstance $instance, string $senderNumber): string
    {
        $properties = Property::select('id', 'title', 'price', 'location', 'description')
            ->limit(5)
            ->get()
            ->toArray();

        $response = $this->geminiService->formatApartmentList($properties);

        $this->evolutionAPI->sendMessage(
            $instance->instance_name,
            $senderNumber,
            $response
        );

        Log::info("Resposta de listagem enviada para {$senderNumber}");

        return $response;
    }

    /**
    * Responde com mensagem padrao.
     */
    private function respondWithDefaultMessage(WhatsappInstance $instance, string $senderNumber): string
    {
        $response = "Ola!\n\nEscreva *LISTAR* para ver nossos apartamentos disponiveis, ou envie sua duvida. Estamos aqui para ajudar!";

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
