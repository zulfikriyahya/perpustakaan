<x-filament-panels::page>
    <form wire:submit.prevent="proses">
        {{ $this->form }}

        <div style="margin-top: 1.5rem;">
            <x-filament::button type="submit" icon="heroicon-o-check">
                Proses Kenaikan Kelas
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
