<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}
        <div style="margin-top: 1.5rem;">
            <x-filament::button type="submit" icon="heroicon-o-document-arrow-down">
                Generate & Download PDF
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
