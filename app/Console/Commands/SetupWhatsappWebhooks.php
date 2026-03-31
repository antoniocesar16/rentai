<?php

namespace App\Console\Commands;

use App\Models\WhatsappInstance;
use App\Services\EvolutionAPIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SetupWhatsappWebhooks extends Command
{
    protected $signature = 'whatsapp:setup-webhooks
                          {--instance-id= : ID de instância específica}
                          {--force : Reconfigurar mesmo se já existente}';

    protected $description = 'Configura webhooks para instâncias WhatsApp existentes';

    public function __construct(private EvolutionAPIService $evolutionAPI)
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('🔧 Configurando webhooks para instâncias WhatsApp...');

        $instances = $this->getInstancesToProcess();

        if ($instances->isEmpty()) {
            $this->warn('❌ Nenhuma instância encontrada.');
            return Command::FAILURE;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($instances as $instance) {
            $this->line("📱 Processando: {$instance->display_name} ({$instance->instance_name})");

            if ($this->processInstanceWebhook($instance)) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        $this->line('');
        $this->info("Resumo: {$successCount} sucesso(s), {$errorCount} erro(s)");

        return $successCount > 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function getInstancesToProcess()
    {
        if (!$this->option('instance-id')) {
            return WhatsappInstance::all();
        }

        $instance = WhatsappInstance::find($this->option('instance-id'));
        return $instance ? collect([$instance]) : collect();
    }

    private function processInstanceWebhook(WhatsappInstance $instance): bool
    {
        try {
            $this->ensureWebhookSlug($instance);

            $webhookUrl = config('app.url') . "/api/webhooks/whatsapp/{$instance->webhook_slug}";
            $result = $this->evolutionAPI->setupWebhook($instance->instance_name, $webhookUrl);

            if (isset($result['error']) && !$this->option('force')) {
                $this->warn("⚠️  Erro ao configurar: " . ($result['message'] ?? 'Erro desconhecido'));
                return false;
            }

            $this->info("✅ Webhook configurado: {$webhookUrl}");
            return true;
        } catch (\Exception $e) {
            $this->error("❌ Exceção: " . $e->getMessage());
            Log::error("Erro ao configurar webhook: " . $e->getMessage());
            return false;
        }
    }

    private function ensureWebhookSlug(WhatsappInstance $instance): void
    {
        if (!empty($instance->webhook_slug)) {
            return;
        }

        $instance->webhook_slug = Str::slug($instance->display_name ?: $instance->instance_name) . '-' . Str::random(8);
        $instance->save();
        $this->line("ℹ️ webhook_slug gerado automaticamente: {$instance->webhook_slug}");
    }
}
