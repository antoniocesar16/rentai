<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\WhatsappInstance;
use App\Models\WhatsappWebhookMessage;
use App\Services\EvolutionAPIService;
use App\Services\GeminiService;
use App\Services\PropertySearchApiService;
use App\Services\PropertyMatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function __construct(
        private EvolutionAPIService $evolutionAPI,
        private GeminiService $geminiService,
        private PropertySearchApiService $propertySearchApiService,
        private PropertyMatchService $propertyMatchService,
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
        $senderNumber = ($messageData['key']['remoteJid'] ?? null)
            ?? ($messageData['key']['remoteJidAlt'] ?? null);
            
        if (!$senderNumber) {
            $senderNumber = $data['sender'] ?? null;
        }
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

        if ($this->wantsPropertyMedia($messageText)) {
            $responseText = $this->respondWithPropertyMedia($instance, (string) $senderNumber, (string) $messageText);
        } elseif ($detectedIntent === 'list_apartments' && $confidence > 0.6) {
            $responseText = $this->respondWithApartmentList($instance, (string) $senderNumber, (string) $messageText);
        } else {
            $responseText = $this->respondWithHumanizedRag(
                $instance,
                (string) $senderNumber,
                (string) ($senderName ?? ''),
                (string) $messageText,
            );
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

        $senderNumber = ($messageData['key']['remoteJid'] ?? null)
            ?? ($messageData['key']['remoteJidAlt'] ?? null);

        if (!$senderNumber) {
            $senderNumber = $data['sender'] ?? null;
        }
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

    private function wantsPropertyMedia(string $messageText): bool
    {
        $text = mb_strtolower($messageText);
        $keywords = [
            'foto',
            'fotos',
            'imagem',
            'imagens',
            'video',
            'ver mais',
            'mais detalhes',
            'mostrar imovel',
            'mostrar apartamento',
            'quero ver',
            'tem foto',
            'me mostra',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
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
    private function respondWithApartmentList(WhatsappInstance $instance, string $senderNumber, string $userMessage): string
    {
        $properties = $this->propertySearchApiService->search($userMessage, 'title,description', 30);

        if (empty($properties)) {
            $properties = $this->propertySearchApiService->search('', 'title,description', 30);
        }

        if (empty($properties)) {
            $properties = Property::select('id', 'title', 'price', 'location', 'description', 'photos', 'details', 'user_id')
                ->latest()
                ->get()
                ->toArray();
        }

        $bestMatch = $this->propertyMatchService->findBestMatchingProperty($properties, $userMessage);
        $response = $this->propertyMatchService->buildBestApartmentMessage($bestMatch, $properties);

        $sendResult = $this->evolutionAPI->sendMessage(
            $instance->instance_name,
            $senderNumber,
            $response
        );

        Log::info('Resposta de listagem enviada', [
            'instance' => $instance->instance_name,
            'sender_number' => $senderNumber,
            'properties_count' => count($properties),
            'best_match' => $bestMatch['title'] ?? null,
            'send_result' => $sendResult,
        ]);

        return $response;
    }

    private function respondWithPropertyMedia(WhatsappInstance $instance, string $senderNumber, string $userMessage): string
    {
        $properties = $this->retrieveRelevantPropertiesWithPhotos($instance, $userMessage);

        if (empty($properties)) {
            $fallback = 'No momento nao encontrei fotos cadastradas para este imovel, mas posso te enviar os detalhes em texto. Quer que eu envie agora?';
            $this->evolutionAPI->sendMessage($instance->instance_name, $senderNumber, $fallback, ['delay' => 300]);
            return $fallback;
        }

        $intro = 'Perfeito! Separei algumas fotos para voce dar uma olhada.';
        $this->evolutionAPI->sendMessage($instance->instance_name, $senderNumber, $intro, ['delay' => 250]);

        $sent = $this->sendPropertyPhotos($instance, $senderNumber, $properties, 4);

        if ($sent === 0) {
            $fallback = 'Nao consegui carregar as imagens agora, mas posso te enviar os detalhes do imovel em texto.';
            $this->evolutionAPI->sendMessage($instance->instance_name, $senderNumber, $fallback, ['delay' => 300]);
            return $fallback;
        }

        $ending = 'Se quiser, te mando agora as condicoes e como agendar visita.';
        $this->evolutionAPI->sendMessage($instance->instance_name, $senderNumber, $ending, ['delay' => 300]);

        return $intro . "\n" . $ending;
    }

    private function sendPropertyPhotos(WhatsappInstance $instance, string $senderNumber, array $properties, int $maxPhotos): int
    {
        $jobs = $this->buildPropertyPhotoJobs($properties, $maxPhotos);

        foreach ($jobs as $job) {
            $this->evolutionAPI->sendMediaMessage(
                $instance->instance_name,
                $senderNumber,
                $job['media'],
                $job['caption'],
                'image/jpeg',
                $job['fileName'],
                ['delay' => 350]
            );
        }

        return count($jobs);
    }

    private function buildPropertyPhotoJobs(array $properties, int $maxPhotos): array
    {
        $jobs = [];

        foreach ($properties as $property) {
            $photos = $property['photos'] ?? [];
            if (!is_array($photos)) {
                continue;
            }

            foreach (array_slice($photos, 0, 2) as $index => $photo) {
                if (count($jobs) >= $maxPhotos) {
                    return $jobs;
                }

                $mediaUrl = $this->normalizeMediaUrl((string) $photo);
                if ($mediaUrl === null) {
                    continue;
                }

                $jobs[] = [
                    'media' => $mediaUrl,
                    'caption' => $index === 0
                        ? "{$property['title']} - {$property['location']} - R$ {$property['price']}"
                        : "Mais um angulo de {$property['title']}",
                    'fileName' => 'imovel-' . $property['id'] . '.jpg',
                ];
            }
        }

        return $jobs;
    }

    /**
    * Resposta humanizada com RAG (banco + historico da conversa).
     */
    private function respondWithHumanizedRag(
        WhatsappInstance $instance,
        string $senderNumber,
        string $senderName,
        string $userMessage,
    ): string {
        $properties = $this->retrieveRelevantProperties($instance, $userMessage);

        $conversationHistory = WhatsappWebhookMessage::query()
            ->where('whatsapp_instance_id', $instance->id)
            ->where('sender_number', $senderNumber)
            ->latest()
            ->limit(8)
            ->get(['direction', 'message_text', 'created_at'])
            ->reverse()
            ->values()
            ->toArray();

        $response = $this->geminiService->generateHumanizedReplyWithRag(
            userMessage: $userMessage,
            senderName: $senderName,
            properties: $properties,
            conversationHistory: $conversationHistory,
        );

        if (!is_string($response) || trim($response) === '') {
            $response = "Entendi! Posso te ajudar a encontrar um apartamento ideal. Se quiser, me diga bairro, faixa de preco e quantidade de quartos.";
        }

        $sendResult = $this->evolutionAPI->sendMessage(
            $instance->instance_name,
            $senderNumber,
            $response
        );

        Log::info('Tentativa de envio da resposta RAG', [
            'instance' => $instance->instance_name,
            'sender_number' => $senderNumber,
            'send_result' => $sendResult,
        ]);

        return $response;
    }

    private function retrieveRelevantProperties(WhatsappInstance $instance, string $userMessage): array
    {
        $apiProperties = $this->propertySearchApiService->search($userMessage, 'title,description', 12);

        if (!empty($apiProperties)) {
            return $apiProperties;
        }

        $query = Property::query()
            ->where('user_id', $instance->user_id)
            ->select('id', 'title', 'description', 'price', 'location');

        $terms = preg_split('/\s+/', mb_strtolower($userMessage));
        $terms = array_values(array_filter($terms ?: [], fn($term) => mb_strlen($term) >= 3));

        if (!empty($terms)) {
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%' . $term . '%';
                    $q->orWhereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(location) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$like]);
                }
            });
        }

        $properties = $query->limit(6)->get()->toArray();

        if (empty($properties)) {
            $properties = Property::query()
                ->where('user_id', $instance->user_id)
                ->select('id', 'title', 'description', 'price', 'location')
                ->latest()
                ->limit(6)
                ->get()
                ->toArray();
        }

        return $properties;
    }

    private function retrieveRelevantPropertiesWithPhotos(WhatsappInstance $instance, string $userMessage): array
    {
        $query = Property::query()
            ->where('user_id', $instance->user_id)
            ->whereNotNull('photos')
            ->select('id', 'title', 'price', 'location', 'photos');

        $terms = preg_split('/\s+/', mb_strtolower($userMessage));
        $terms = array_values(array_filter($terms ?: [], fn($term) => mb_strlen($term) >= 3));

        if (!empty($terms)) {
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%' . $term . '%';
                    $q->orWhereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(location) LIKE ?', [$like]);
                }
            });
        }

        $rows = $query->limit(4)->get()->toArray();

        if (empty($rows)) {
            $rows = Property::query()
                ->where('user_id', $instance->user_id)
                ->whereNotNull('photos')
                ->select('id', 'title', 'price', 'location', 'photos')
                ->latest()
                ->limit(4)
                ->get()
                ->toArray();
        }

        return $rows;
    }

    private function normalizeMediaUrl(string $media): ?string
    {
        $media = trim($media);
        if ($media === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $media) === 1) {
            return $media;
        }

        $base = rtrim((string) config('app.url'), '/');
        $path = '/' . ltrim($media, '/');

        return $base . $path;
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
