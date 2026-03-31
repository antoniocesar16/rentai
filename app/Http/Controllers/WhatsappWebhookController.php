<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\WhatsappInstance;
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
    public function handleWebhook(Request $request, string $slug)
    {
        Log::info('Webhook request recebida', $this->buildWebhookLogContext($request, $slug));

        try {
            $validationResult = $this->validateWebhookData($request);
            if ($validationResult) {
                return $validationResult;
            }

            // Localiza a instância pelo slug
            $instance = WhatsappInstance::where('webhook_slug', $slug)->firstOrFail();

            // Extrai dados da mensagem
            $data = $request->json()->all();
            $messageData = $data['data']['message'] ?? null;
            $senderNumber = $messageData['sender']['id'] ?? null;
            $messageText = $messageData['body'] ?? '';

            Log::info("Mensagem recebida de {$senderNumber}: {$messageText}");

            // Analisa a intenção com Gemini
            $intent = $this->geminiService->analyzeIntent($messageText);

            // Processa a intenção
            if ($intent['intent'] === 'list_apartments' && $intent['confidence'] > 0.6) {
                $this->respondWithApartmentList($instance, $senderNumber);
            } else {
                $this->respondWithDefaultMessage($instance, $senderNumber);
            }

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
        $messageData = $data['data']['message'] ?? null;

        if (!$messageData) {
            return response()->json(['success' => false, 'message' => 'No message data'], 400);
        }

        $senderNumber = $messageData['sender']['id'] ?? null;
        $messageText = $messageData['body'] ?? '';

        if (!$senderNumber || !$messageText) {
            return response()->json(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        return null;
    }

    /**
     * Monta contexto de log para cada request recebida no webhook.
     */
    private function buildWebhookLogContext(Request $request, string $slug): array
    {
        $headers = [
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->header('User-Agent'),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
        ];

        return [
            'slug' => $slug,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'headers' => $headers,
            'payload' => $request->all(),
        ];
    }


    /**
     * Responde com lista de apartamentos
     */
    private function respondWithApartmentList($instance, $senderNumber): void
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
    }

    /**
     * Responde com mensagem padrão
     */
    private function respondWithDefaultMessage($instance, $senderNumber): void
    {
        $this->evolutionAPI->sendMessage(
            $instance->instance_name,
            $senderNumber,
            "Olá! 👋\n\nEscreva *LISTAR* para ver nossos apartamentos disponíveis, ou envie sua dúvida. Estamos aqui para ajudar! 😊"
        );
    }

}
