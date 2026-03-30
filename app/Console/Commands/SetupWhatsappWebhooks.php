<?php

namespace App\Console\Commands;

use App\Models\WhatsappInstance;
use App\Services\EvolutionAPIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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

        // Determina quais instâncias processar
        if ($this->option('instance-id')) {
            $instances = WhatsappInstance::find($this->option('instance-id'));
            $instances = $instances ? collect([$instances]) : collect();
        } else {
            $instances = WhatsappInstance::all();
        }

        if ($instances->isEmpty()) {
            $this->warn('❌ Nenhuma instância encontrada.');
            return Command::FAILURE;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($instances as $instance) {
            $this->line("📱 Processando: {$instance->display_name} ({$instance->instance_name})");

            try {
                $webhookUrl = config('app.url') . "/api/webhooks/whatsapp/{$instance->webhook_slug}";
                
                $result = $this->evolutionAPI->setupWebhook($instance->instance_name, $webhookUrl);

                if (isset($result['error']) && !$this->option('force')) {
                    $this->warn("⚠️  Erro ao configurar: " . ($result['message'] ?? 'Erro desconhecido'));
                    $errorCount++;
                } else {
                    $this->info("✅ Webhook configurado: {$webhookUrl}");
                    $successCount++;
                }
            } catch (\Exception $e) {
                $this->error("❌ Exceção: " . $e->getMessage());
                Log::error("Erro ao configurar webhook: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->line('');
        $this->info("Resumo: {$successCount} sucesso(s), {$errorCount} erro(s)");

        return $successCount > 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
