<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form wire:submit="generate">
                {{ $this->form }}

                <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem;">
                    <x-filament::button
                        type="submit"
                        icon="heroicon-o-document-arrow-down"
                        size="lg"
                        wire:loading.attr="disabled"
                        wire:target="generate"
                    >
                        <span wire:loading.remove wire:target="generate">
                            Generate &amp; Download PDF
                        </span>
                        <span wire:loading wire:target="generate">
                            Menyusun laporan...
                        </span>
                    </x-filament::button>

                    <x-filament::loading-indicator
                        wire:loading
                        wire:target="generate"
                        class="h-5 w-5 text-primary-500"
                    />
                </div>
            </form>
        </div>

</x-filament-panels::page>
