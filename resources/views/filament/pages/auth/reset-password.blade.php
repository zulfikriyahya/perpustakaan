<x-filament-panels::page.simple>
    <form wire:submit="prosesReset">
        {{ $this->form }}

        <div style="margin-top: 1.5rem;">
            <x-filament::button type="submit" class="w-full">
                Reset Password
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page.simple>
