<?php

namespace App\Http\Controllers;

use App\Models\WhatsappInstance;
use App\Services\EvolutionAPIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;


class WhatsappInstanceController extends Controller
{
    public function __construct(private EvolutionAPIService $evolutionAPI) {}

    public function index()
    {
        $instances = WhatsappInstance::where('user_id', auth()->id())->latest()->get();
        $lastPairingCode = $this->evolutionAPI->getPairingCode($instances->last()->instance_name ?? '');
        return view('whatsapp-connect.index', compact('instances', 'lastPairingCode'));
    }

    public function create()
    {
        return view('whatsapp-connect.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'display_name' => 'required|string|max:255',
        ]);

        $instanceName = WhatsappInstance::generateInstanceName();
        $result = $this->evolutionAPI->createInstance($instanceName, $request->phone_number);

        if (isset($result['instance'])) {
            $pairingCode = $this->evolutionAPI->getPairingCode($instanceName);
            
            $webhookSlug = \Illuminate\Support\Str::slug($request->display_name) . '-' . \Illuminate\Support\Str::random(8);

            $instance = WhatsappInstance::create([
                'user_id' => auth()->id(),
                'instance_name' => $instanceName,
                'phone_number' => $request->phone_number,
                'display_name' => $request->display_name,
                'status' => 'disconnected',
                'pairing_code' => $pairingCode['pairingCode'] ?? null,
                'api_token' => bin2hex(random_bytes(32)),
                'webhook_slug' => $webhookSlug,
            ]);

            // Configura webhook para receber mensagens
            $webhookUrl = config('app.url') . "/api/webhooks/whatsapp/{$webhookSlug}";
            $this->evolutionAPI->setupWebhook($instanceName, $webhookUrl);


            return redirect()->route('whatsapp.show', $instance)->with('success', 'Instância criada! Use o código de pareamento.');
        }

        Log::debug('Erro ao criar instância WhatsApp', ['response' => $result]);
        return back()->with('error', 'Erro ao criar instância.');
    }

    public function show(WhatsappInstance $whatsapp)
    {
        $this->authorize('view', $whatsapp);
        
        $status = $this->evolutionAPI->getInstanceStatus($whatsapp->instance_name);
        $whatsapp->update(['status' => $status['state'] ?? 'disconnected']);

        return view('whatsapp-connect.show', compact('whatsapp'));
    }

    public function destroy(WhatsappInstance $whatsapp)
    {
        $this->authorize('delete', $whatsapp);

        $this->evolutionAPI->deleteInstance($whatsapp->instance_name);
        $whatsapp->delete();

        return redirect()->route('whatsapp.index')->with('success', 'Instância removida.');
    }

    public function logout(WhatsappInstance $whatsapp)
    {
        $this->authorize('update', $whatsapp);

        $this->evolutionAPI->logoutInstance($whatsapp->instance_name);
        $whatsapp->update(['status' => 'disconnected', 'pairing_code' => null]);

        return back()->with('success', 'Desconectado com sucesso.');
    }

    public function refreshQrCode(WhatsappInstance $whatsapp)
    {
        $this->authorize('update', $whatsapp);

        try {
            // Primeiro verifica o status
            $statusData = $this->evolutionAPI->getInstanceStatus($whatsapp->instance_name);
            Log::debug($statusData);
            $currentStatus = $statusData['state'] ?? 'disconnected';
            
            // Se já está conectado, redireciona
            if ($currentStatus === 'open') {
                $whatsapp->update(['status' => 'open']);
                return redirect()->route('whatsapp.show', $whatsapp)->with('success', 'Instância já conectada!');
            }
            
            // Se não está conectado, tenta gerar novo código
            $result = $this->evolutionAPI->getPairingCode($whatsapp->instance_name);
            Log::debug($result);
            if (isset($result['pairingCode']) && $result['pairingCode'] != null) {
                $whatsapp->update(['pairing_code' => $result['pairingCode'], 'status' => $currentStatus]);
                return back()->with('success', 'Código de pareamento atualizado.');
            }

            $errorMsg = $result['message'] ?? 'Erro ao gerar código.';
            return back()->with('error', $errorMsg);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar código de pareamento: ' . $e->getMessage());
            return back()->with('error', 'Erro: ' . $e->getMessage());
        }
    }

    public function checkStatus(WhatsappInstance $whatsapp)
    {
        $this->authorize('view', $whatsapp);
        
        $status = $this->evolutionAPI->getInstanceStatus($whatsapp->instance_name);
        Log::debug('Status completo da instância', ['response' => $status]);
        
        $newStatus = $status['state'] ?? 'disconnected';
        $whatsapp->update(['status' => $newStatus]);
        
        $qrData = $this->evolutionAPI->getQRCode($whatsapp->instance_name);
        
        return response()->json([
            'status' => $newStatus,
            'connected' => $newStatus === 'open',
            'qrcode' => $qrData['base64'] ?? $qrData['qrcode']['base64'] ?? null
        ]);
    }

    public function profile(WhatsappInstance $whatsapp)
    {
        $this->authorize('view', $whatsapp);
        
        $profileData = $this->evolutionAPI->getProfileInfo($whatsapp->instance_name);
        
        return response()->json($profileData);
    }

    public function updateDelay(Request $request, WhatsappInstance $whatsapp)
    {
        $this->authorize('update', $whatsapp);
        
        $request->validate(['message_delay_seconds' => 'required|integer|min:1|max:300']);
        
        $whatsapp->update(['message_delay_seconds' => $request->message_delay_seconds]);
        
        return back()->with('success', 'Delay atualizado com sucesso!');
    }
}