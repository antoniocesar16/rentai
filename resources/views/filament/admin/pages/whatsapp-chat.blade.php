<x-filament-panels::page>
    <div class="max-w-5xl mx-auto space-y-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow border border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label for="instance_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Instancia</label>
                    <select id="instance_id" wire:model.live="instanceId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Todas</option>
                        @foreach($this->instances as $instance)
                            <option value="{{ $instance['id'] }}">{{ $instance['display_name'] }} ({{ $instance['instance_name'] }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="search_text" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Buscar texto</label>
                    <input
                        id="search_text"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Digite parte da mensagem..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>
            </div>

            <div class="mt-3">
                <x-filament::button wire:click="refreshMessages" color="gray" size="sm">
                    Atualizar
                </x-filament::button>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow border border-gray-200 dark:border-gray-700 min-h-[420px]">
            <div class="space-y-3">
                @forelse($this->messages as $message)
                    @php
                        $isInbound = ($message['direction'] ?? '') === 'inbound';
                    @endphp

                    <div class="flex {{ $isInbound ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-3xl rounded-2xl px-4 py-3 {{ $isInbound ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100' : 'bg-emerald-500 text-white' }}">
                            <div class="text-xs opacity-80 mb-1">
                                {{ $isInbound ? 'Cliente' : 'Bot' }}
                                @if(!empty($message['sender_number']))
                                    • {{ $message['sender_number'] }}
                                @endif
                                • {{ \Illuminate\Support\Carbon::parse($message['created_at'])->format('d/m/Y H:i:s') }}
                            </div>

                            <div class="whitespace-pre-wrap break-words">{{ $message['message_text'] ?: '[sem texto]' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Nenhuma mensagem armazenada ainda.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>

