<x-filament-panels::page>
    <div class="max-w-4xl mx-auto space-y-6">

        @if($this->instance && $this->connectionStatus === 'open')
            {{-- Connected State --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 shadow border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <x-heroicon-s-check-circle class="w-7 h-7 text-green-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">WhatsApp Conectado</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->instance->display_name }}</p>
                    </div>
                    <span class="ml-auto inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-medium">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        Online
                    </span>
                </div>

                <div class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <span class="text-gray-500 dark:text-gray-400">Número</span>
                        <p class="font-medium text-gray-900 dark:text-white mt-1">{{ $this->instance->phone_number }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <span class="text-gray-500 dark:text-gray-400">Instância</span>
                        <p class="font-medium text-gray-900 dark:text-white mt-1 font-mono text-xs">{{ $this->instance->instance_name }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 mt-6">
                    <x-filament::button wire:click="refreshStatus" color="gray" size="sm">
                        <x-heroicon-o-arrow-path class="w-4 h-4 mr-1" />
                        Atualizar Status
                    </x-filament::button>
                    <x-filament::button wire:click="disconnectInstance" color="warning" size="sm">
                        <x-heroicon-o-power class="w-4 h-4 mr-1" />
                        Desconectar
                    </x-filament::button>
                    <x-filament::button wire:click="deleteInstance" color="danger" size="sm"
                        wire:confirm="Tem certeza que deseja remover esta instância?">
                        <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                        Remover
                    </x-filament::button>
                </div>
            </div>

        @elseif($this->instance)
            {{-- Disconnected / Pairing State --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 shadow border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                        <x-heroicon-o-signal class="w-7 h-7 text-yellow-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Aguardando Conexão</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->instance->display_name }} — {{ $this->instance->phone_number }}</p>
                    </div>
                    <span class="ml-auto inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 text-sm font-medium">
                        <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                        {{ ucfirst($this->connectionStatus) }}
                    </span>
                </div>

                @if($this->pairingCode ?? $this->instance->pairing_code)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 text-center mb-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Código de Pareamento</p>
                        <p class="text-3xl font-mono font-bold tracking-widest text-gray-900 dark:text-white">
                            {{ $this->pairingCode ?? $this->instance->pairing_code }}
                        </p>
                        <p class="text-xs text-gray-400 mt-3">
                            Abra o WhatsApp no celular → Dispositivos Conectados → Conectar com número de telefone
                        </p>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3">
                    <x-filament::button wire:click="refreshPairingCode" color="primary" size="sm">
                        <x-heroicon-o-arrow-path class="w-4 h-4 mr-1" />
                        Novo Código
                    </x-filament::button>
                    <x-filament::button wire:click="refreshStatus" color="gray" size="sm">
                        <x-heroicon-o-signal class="w-4 h-4 mr-1" />
                        Verificar Conexão
                    </x-filament::button>
                    <x-filament::button wire:click="deleteInstance" color="danger" size="sm"
                        wire:confirm="Tem certeza que deseja remover esta instância?">
                        <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                        Remover
                    </x-filament::button>
                </div>
            </div>

        @else
            {{-- No Instance - Create Form --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 lg:p-8 shadow border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <x-heroicon-o-chat-bubble-left-right class="w-7 h-7 text-gray-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Conectar WhatsApp</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Crie uma instância para começar a enviar mensagens.</p>
                    </div>
                </div>

                <form wire:submit="createInstance" class="space-y-6">
                    {{ $this->form }}

                    <x-filament::button type="submit" size="lg" class="w-full sm:w-auto">
                        <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                        Criar Instância
                    </x-filament::button>
                </form>
            </div>
        @endif

    </div>
</x-filament-panels::page>
