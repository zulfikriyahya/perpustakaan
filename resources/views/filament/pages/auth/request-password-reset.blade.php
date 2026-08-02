<div>
    @include('filament.partials.auth-styles')

    <x-filament-panels::page.simple>

        <form wire:submit="kirim">
            {{ $this->form }}

            <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                <x-filament::button
                    tag="a"
                    :href="route('filament.dashboard.auth.login')"
                    color="gray"
                    outlined
                    icon="heroicon-o-arrow-left"
                    class="flex-1"
                >
                    Kembali
                </x-filament::button>

                <x-filament::button type="submit" class="flex-1" icon="heroicon-o-paper-airplane">
                    Kirim OTP ke WhatsApp
                </x-filament::button>
            </div>
        </form>

        @include('filament.partials.app-footer', ['authTop' => true])

    </x-filament-panels::page.simple>
</div>
