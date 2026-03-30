<?php

namespace App\Filament\Admin\Pages;

use App\Models\WhatsappInstance;
use App\Services\EvolutionAPIService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;

use BackedEnum;

class WhatsappConnection extends Page
{
    protected string $view = 'filament.admin.pages.whatsapp-connection';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'WhatsApp';

    protected static ?string $title = 'Conexão WhatsApp';

    protected static string|\UnitEnum|null $navigationGroup = 'Integrações';

    public ?array $data = [];
    public ?WhatsappInstance $instance = null;
    public ?string $pairingCode = null;
    public ?string $qrCode = null;
    public string $connectionStatus = 'disconnected';

    public function mount(): void
    {
        $this->instance = WhatsappInstance::where('user_id', auth()->id())->latest()->first();

        if ($this->instance) {
            $this->refreshStatus();
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('phone_number')
                    ->label('Número do WhatsApp')
                    ->tel()
                    ->required()
                    ->placeholder('5541999999999')
                    ->helperText('Número com código do país, sem espaços ou traços.'),

                TextInput::make('display_name')
                    ->label('Nome de Exibição')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ex: Imobiliária Central'),
            ])
            ->statePath('data');
    }

    public function createInstance(): void
    {
        $data = $this->form->getState();
        $evolution = app(EvolutionAPIService::class);

        $instanceName = WhatsappInstance::generateInstanceName();
        $result = $evolution->createInstance($instanceName, $data['phone_number']);

        if (isset($result['instance'])) {
            $pairingResult = $evolution->getPairingCode($instanceName);

            $this->instance = WhatsappInstance::create([
                'user_id' => auth()->id(),
                'instance_name' => $instanceName,
                'phone_number' => $data['phone_number'],
                'display_name' => $data['display_name'],
                'status' => 'disconnected',
                'pairing_code' => $pairingResult['pairingCode'] ?? null,
            ]);

            $this->pairingCode = $this->instance->pairing_code;
            $this->connectionStatus = 'disconnected';

            Notification::make()
                ->title('Instância criada')
                ->body('Use o código de pareamento para conectar.')
                ->success()
                ->send();
        } else {
            Log::error('Erro ao criar instância WhatsApp', ['response' => $result]);
            Notification::make()
                ->title('Erro ao criar instância')
                ->body($result['message'] ?? 'Tente novamente.')
                ->danger()
                ->send();
        }
    }

    public function refreshStatus(): void
    {
        if (! $this->instance) return;

        $evolution = app(EvolutionAPIService::class);
        $status = $evolution->getInstanceStatus($this->instance->instance_name);

        $this->connectionStatus = $status['state'] ?? 'disconnected';
        $this->instance->update(['status' => $this->connectionStatus]);
    }

    public function refreshPairingCode(): void
    {
        if (! $this->instance) return;

        $evolution = app(EvolutionAPIService::class);
        $result = $evolution->getPairingCode($this->instance->instance_name);

        if (isset($result['pairingCode'])) {
            $this->pairingCode = $result['pairingCode'];
            $this->instance->update(['pairing_code' => $this->pairingCode]);

            Notification::make()
                ->title('Código atualizado')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Erro ao gerar código')
                ->body($result['message'] ?? 'Tente novamente.')
                ->danger()
                ->send();
        }
    }

    public function disconnectInstance(): void
    {
        if (! $this->instance) return;

        $evolution = app(EvolutionAPIService::class);
        $evolution->logoutInstance($this->instance->instance_name);

        $this->instance->update(['status' => 'disconnected', 'pairing_code' => null]);
        $this->connectionStatus = 'disconnected';
        $this->pairingCode = null;

        Notification::make()
            ->title('WhatsApp desconectado')
            ->success()
            ->send();
    }

    public function deleteInstance(): void
    {
        if (! $this->instance) return;

        $evolution = app(EvolutionAPIService::class);
        $evolution->deleteInstance($this->instance->instance_name);

        $this->instance->delete();
        $this->instance = null;
        $this->pairingCode = null;
        $this->connectionStatus = 'disconnected';

        Notification::make()
            ->title('Instância removida')
            ->success()
            ->send();
    }
}
