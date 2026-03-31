<?php

namespace App\Filament\Admin\Pages;

use App\Models\WhatsappInstance;
use App\Models\WhatsappWebhookMessage;
use Filament\Pages\Page;

use BackedEnum;

class WhatsappChat extends Page
{
    protected string $view = 'filament.admin.pages.whatsapp-chat';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationLabel = 'Chat WhatsApp';

    protected static ?string $title = 'Chat WhatsApp';

    protected static string|\UnitEnum|null $navigationGroup = 'Integrações';

    public ?int $instanceId = null;

    public array $instances = [];

    public array $messages = [];

    public string $search = '';

    public function mount(): void
    {
        $this->instances = WhatsappInstance::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->get(['id', 'display_name', 'instance_name'])
            ->toArray();

        if (!empty($this->instances)) {
            $this->instanceId = $this->instances[0]['id'];
        }

        $this->refreshMessages();
    }

    public function updatedInstanceId(): void
    {
        $this->refreshMessages();
    }

    public function updatedSearch(): void
    {
        $this->refreshMessages();
    }

    public function refreshMessages(): void
    {
        $query = WhatsappWebhookMessage::query()->latest();

        if ($this->instanceId) {
            $query->where('whatsapp_instance_id', $this->instanceId);
        }

        if ($this->search !== '') {
            $query->where('message_text', 'like', '%' . $this->search . '%');
        }

        $rows = $query->limit(120)->get()->reverse()->values();
        $this->messages = $rows->toArray();
    }
}

