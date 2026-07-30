<x-filament-panels::page.simple>
    <form wire:submit="kirim">
        {{ $this->form }}

        <div style="margin-top: 1.5rem;">
            <x-filament::button type="submit" class="w-full">
                Kirim OTP ke WhatsApp
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page.simple>
