<x-filament-panels::page>
    <div style="max-width: 480px; margin: 0 auto;">

        @if (! $user)
            <div
                x-data
                x-init="$nextTick(() => $refs.kartu.focus())"
                style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 4rem 1.5rem;"
            >
                <div style="display: flex; align-items: center; justify-content: center; width: 96px; height: 96px; border-radius: 50%; background: var(--primary-50); margin-bottom: 1.5rem;">
                    <x-filament::icon icon="heroicon-o-credit-card" style="width: 44px; height: 44px; color: var(--primary-600);" />
                </div>

                <h2 class="text-gray-950 dark:text-white" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Tempelkan kartu RFID</h2>
                <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.875rem; margin-bottom: 1.5rem;">Scan kartu siswa atau pegawai untuk memulai transaksi.</p>

                <input
                    x-ref="kartu"
                    type="text"
                    wire:model="kartuInput"
                    wire:keydown.enter="scanKartu"
                    autofocus
                    class="fi-input"
                    style="width: 100%; max-width: 280px; border-radius: 9999px; text-align: center; padding: 0.75rem 1.5rem;"
                    placeholder="Tempelkan/scan kartu..."
                />
            </div>
        @else
            <div
                x-data="{ show: false }"
                x-init="requestAnimationFrame(() => show = true)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                style="display: flex; flex-direction: column; align-items: center; text-align: center;"
            >
                <div style="display: flex; align-items: center; justify-content: center; width: 72px; height: 72px; border-radius: 50%; font-weight: 600; font-size: 22px; color: #fff; background: {{ $user->status_suspend ? 'var(--danger-500)' : 'var(--primary-500)' }}; margin-bottom: 0.75rem;">
                    {{ collect(explode(' ', $user->nama))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                </div>

                <h2 class="text-gray-950 dark:text-white" style="font-size: 1.125rem; font-weight: 600; line-height: 1.3;">{{ $user->nama }}</h2>

                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.5rem; margin-bottom: 1.5rem;">
                    <x-filament::badge :color="$user->status_suspend ? 'danger' : 'success'">
                        {{ $user->status_suspend ? 'Suspend' : 'Aktif' }}
                    </x-filament::badge>
                    <x-filament::badge :color="$bisaMeminjam ? 'success' : 'gray'">
                        {{ $bisaMeminjam ? 'Bisa meminjam' : 'Tidak bisa meminjam baru' }}
                    </x-filament::badge>
                </div>

                @if ($user->status_suspend)
                    <div class="bg-warning-50 dark:bg-warning-500/10 text-warning-600 dark:text-warning-400" style="display: flex; align-items: flex-start; gap: 0.5rem; border-radius: 12px; padding: 0.75rem; font-size: 0.875rem; margin-bottom: 1.5rem; text-align: left; width: 100%;">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;" />
                        <span>User masih bisa mengembalikan buku, tapi tidak bisa meminjam baru sampai Denda lunas.</span>
                    </div>
                @endif

                <div x-data x-init="$refs.kode.focus()" style="width: 100%; margin-bottom: 1rem;">
                    <input
                        x-ref="kode"
                        type="text"
                        wire:model="kodeInput"
                        wire:keydown.enter="scanKode"
                        autofocus
                        class="fi-input"
                        style="width: 100%; border-radius: 9999px; text-align: center; padding: 0.75rem 1.5rem; font-size: 1rem;"
                        placeholder="Scan barcode eksemplar atau ISBN buku..."
                    />
                </div>

                <div style="margin-bottom: 2rem;">
                    <x-filament::button
                        wire:click="selesai"
                        color="gray"
                        icon="heroicon-o-arrow-path"
                        size="sm"
                    >
                        Ganti user
                    </x-filament::button>
                </div>

                @php
                    $totalDipinjam = collect($riwayatScan)->where('aksi', 'dipinjamkan')->where('sukses', true)->count();
                    $totalDikembalikan = collect($riwayatScan)->where('aksi', 'dikembalikan')->where('sukses', true)->count();
                @endphp
                @if (count($riwayatScan) > 0)
                    <div style="display: flex; align-items: center; justify-content: center; gap: 2.5rem; margin-bottom: 2rem;">
                        <div style="text-align: center;">
                            <p class="text-primary-600 dark:text-primary-400" style="font-size: 1.5rem; font-weight: 600; margin: 0;">{{ $totalDipinjam }}</p>
                            <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0;">Dipinjamkan</p>
                        </div>
                        <div class="bg-gray-200 dark:bg-gray-700" style="height: 32px; width: 1px;"></div>
                        <div style="text-align: center;">
                            <p class="text-success-600 dark:text-success-400" style="font-size: 1.5rem; font-weight: 600; margin: 0;">{{ $totalDikembalikan }}</p>
                            <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0;">Dikembalikan</p>
                        </div>
                    </div>
                @endif

                <div style="width: 100%; text-align: left; display: flex; flex-direction: column; gap: 0.5rem;">
                    @forelse ($riwayatScan as $item)
                        <div
                            x-data="{ show: false }"
                            x-init="requestAnimationFrame(() => show = true)"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            class="bg-gray-50 dark:bg-white/5"
                            style="display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px;"
                        >
                            <div style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0; background: {{ $item['sukses'] ? 'var(--success-100)' : 'var(--danger-100)' }}; color: {{ $item['sukses'] ? 'var(--success-700)' : 'var(--danger-700)' }};">
                                @if (! $item['sukses'])
                                    <x-filament::icon icon="heroicon-o-x-mark" style="width: 16px; height: 16px;" />
                                @elseif ($item['aksi'] === 'dipinjamkan')
                                    <x-filament::icon icon="heroicon-o-arrow-up-circle" style="width: 16px; height: 16px;" />
                                @else
                                    <x-filament::icon icon="heroicon-o-arrow-down-circle" style="width: 16px; height: 16px;" />
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <p class="text-gray-950 dark:text-white" style="font-weight: 500; font-size: 0.875rem; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['judul'] }}</p>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['pesan'] }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-400 dark:text-gray-500" style="text-align: center; padding: 2rem 0;">
                            <x-filament::icon icon="heroicon-o-book-open" style="width: 32px; height: 32px; margin: 0 auto 0.5rem;" />
                            <p style="font-size: 0.875rem; margin: 0;">Belum ada buku yang di-scan.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
