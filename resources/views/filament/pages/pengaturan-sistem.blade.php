<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}

        <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem;">
            <x-filament::button
                type="button"
                wire:click="simpanUmum"
                icon="heroicon-o-check"
            >
                Simpan Pengaturan Umum
            </x-filament::button>

            <x-filament::button
                type="button"
                color="warning"
                icon="heroicon-o-exclamation-triangle"
                x-on:click.prevent="
                    if (confirm('Perubahan ini mempengaruhi device RFID yang sudah aktif di lapangan. Lanjutkan menyimpan?')) {
                        $wire.simpanDevice()
                    }
                "
            >
                Simpan Pengaturan Device
            </x-filament::button>

            <x-filament::button
                type="button"
                color="danger"
                icon="heroicon-o-key"
                x-on:click.prevent="
                    if (confirm('Perubahan kredensial ini TIDAK otomatis mengubah panel gateway WhatsApp atau firmware device RFID. Jika nilai baru belum disinkronkan di kedua sisi, notifikasi WA dan/atau autentikasi device akan GAGAL. Lanjutkan menyimpan?')) {
                        $wire.simpanKredensial()
                    }
                "
            >
                Simpan Kredensial Sensitif
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
