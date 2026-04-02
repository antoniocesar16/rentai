<?php

namespace App\Console\Commands;

use App\Services\GeminiService;
use Illuminate\Console\Command;

class TestGeminiCommand extends Command
{
    protected $signature = 'gemini:test
                            {--model= : Modelo do Gemini a usar no teste}
                            {--prompt= : Prompt de teste. Se vazio, usa um prompt padrão}
                            {--no-ssl-verify : Desativa a verificação SSL (use apenas para teste local)}
                            {--show-json : Mostra a resposta completa da API}';

    protected $description = 'Testa a chave do Gemini e exibe a resposta do modelo escolhido';

    public function __construct(private GeminiService $geminiService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $model = $this->option('model') ?: config('services.gemini.model', 'gemini-3-flash-preview');
        $prompt = $this->option('prompt') ?: 'Responda com uma frase curta dizendo que o teste do Gemini funcionou.';

        $this->info('Iniciando teste do Gemini...');
        $this->line('Modelo: ' . $model);

        $verifySsl = !$this->option('no-ssl-verify');
        if (!$verifySsl) {
            $this->warn('SSL verification desativada para este teste.');
        }

        $result = $this->geminiService->testPrompt($prompt, $model, $verifySsl);

        if (!($result['successful'] ?? false)) {
            $this->error('Falha ao testar o Gemini.');
            $this->line('Status: ' . ($result['status'] ?? 0));

            if (!empty($result['error'])) {
                $this->line('Erro: ' . $result['error']);
                if (str_contains((string) $result['error'], 'SSL/conexão falhou')) {
                    $this->line('Dica: tente rodar novamente com --no-ssl-verify ou configure o CA bundle do cURL no Windows.');
                }
            }

            if ($this->option('show-json') && isset($result['raw'])) {
                $this->newLine();
                $this->line(json_encode($result['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            return Command::FAILURE;
        }

        $this->info('Chave válida e resposta recebida.');
        $this->line('Status: ' . ($result['status'] ?? 200));
        $this->newLine();
        $this->line('Prompt usado:');
        $this->line($prompt);
        $this->newLine();
        $this->line('Resposta do modelo:');
        $this->line(trim((string) ($result['content'] ?? '')));

        if ($this->option('show-json') && isset($result['raw'])) {
            $this->newLine();
            $this->line('Resposta bruta da API:');
            $this->line(json_encode($result['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return Command::SUCCESS;
    }
}
