<x-filament-panels::page>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Welcome Header --}}
        <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 sm:p-8 lg:p-12 shadow-xl mb-8 border border-gray-100 dark:border-gray-700">
            <div class="lg:flex lg:items-center lg:justify-between lg:gap-12">
                {{-- Left: Welcome text --}}
                <div class="text-center lg:text-left lg:flex-1 mb-8 lg:mb-0">
                    <div class="inline-flex items-center justify-center w-16 h-16 lg:w-20 lg:h-20 rounded-full bg-primary-50 dark:bg-primary-900/20 mb-4">
                        <x-heroicon-o-home class="w-8 h-8 lg:w-10 lg:h-10 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-3">
                        Bem-vindo ao Apartamento WhatsApp!
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 text-base lg:text-lg max-w-xl mx-auto lg:mx-0">
                        Vamos começar cadastrando seu primeiro imóvel. Preencha os dados abaixo e nosso robô começará a divulgar para você.
                    </p>
                </div>

                {{-- Right: Steps indicator --}}
                <div class="flex flex-col items-center lg:items-end gap-3 text-sm shrink-0">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium">
                        <span class="w-5 h-5 rounded-full bg-primary-600 text-white text-xs flex items-center justify-center font-bold">1</span>
                        Criar conta
                        <x-heroicon-s-check-circle class="w-4 h-4 text-primary-600" />
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary-600 text-white font-medium">
                        <span class="w-5 h-5 rounded-full bg-white text-primary-600 text-xs flex items-center justify-center font-bold">2</span>
                        Adicionar imóvel
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 font-medium">
                        <span class="w-5 h-5 rounded-full bg-gray-300 dark:bg-gray-600 text-white text-xs flex items-center justify-center font-bold">3</span>
                        Pronto!
                    </span>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form wire:submit="create">
            {{ $this->form }}

            <div class="mt-8 flex flex-col-reverse sm:flex-row items-center justify-between gap-4">
                <button
                    type="button"
                    wire:click="skip"
                    class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition underline"
                >
                    Pular por enquanto
                </button>

                <x-filament::button type="submit" size="xl" class="w-full sm:w-auto">
                    <x-heroicon-o-rocket-launch class="w-5 h-5 mr-2" />
                    Cadastrar Imóvel e Começar
                </x-filament::button>
            </div>
        </form>

        <p class="text-center mt-6 text-xs text-gray-400 uppercase font-bold tracking-widest">
            Você poderá adicionar mais imóveis depois no painel.
        </p>
    </div>
</x-filament-panels::page>
