<x-filament-panels::page>
    @if (! $user)
        <div class="rounded-xl border p-6 space-y-3" x-data x-init="$refs.kartu.focus()">
            <label class="font-medium">Scan Kartu RFID</label>
            <input
                x-ref="kartu"
                type="text"
                wire:model="kartuInput"
                wire:keydown.enter="scanKartu"
                autofocus
                class="fi-input w-full rounded-lg"
                placeholder="Tempelkan/scan kartu..."
            />
        </div>
    @else
        <div class="rounded-xl border p-6 space-y-2">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-lg font-semibold">{{ $user->nama }}</p>
                    <p class="text-sm text-gray-500">
                        Status: {{ $user->status_suspend ? 'SUSPEND' : 'Aktif' }}
                        &middot;
                        {{ $bisaMeminjam ? 'Bisa meminjam' : 'Tidak bisa meminjam baru' }}
                    </p>
                </div>
                <x-filament::button color="gray" wire:click="selesai">Selesai / Ganti User</x-filament::button>
            </div>
        </div>

        <div class="rounded-xl border p-6 space-y-3 mt-4" x-data x-init="$refs.barcode.focus()">
            <label class="font-medium">Scan Barcode Buku</label>
            <input
                x-ref="barcode"
                type="text"
                wire:model="barcodeInput"
                wire:keydown.enter="scanBarcode"
                autofocus
                class="fi-input w-full rounded-lg"
                placeholder="Scan barcode buku..."
            />
        </div>

        <div class="mt-4 space-y-2">
            @foreach ($riwayatScan as $item)
                <div @class([
                    'rounded-lg border p-3 flex items-center justify-between',
                    'border-success-300 bg-success-50' => $item['sukses'],
                    'border-danger-300 bg-danger-50' => ! $item['sukses'],
                ])>
                    <div>
                        <p class="font-medium">{{ $item['judul'] }}</p>
                        <p class="text-sm text-gray-600">{{ $item['pesan'] }}</p>
                    </div>
                    <span class="text-xs uppercase font-semibold">{{ $item['aksi'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
