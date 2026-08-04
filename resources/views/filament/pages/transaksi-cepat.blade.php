<x-filament-panels::page>
    <style>
        .transaksi-cepat-card {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.04);
        }

        html.dark .transaksi-cepat-card {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.16);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.04),
                0 20px 50px -12px rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(6px);
        }

        .transaksi-cepat-avatar-wrap {
            position: relative;
            width: 88px;
            height: 88px;
            margin-bottom: 0.75rem;
        }

        .transaksi-cepat-ring {
            position: absolute;
            inset: 0;
            transform: rotate(-90deg);
            pointer-events: none;
        }

        .transaksi-cepat-ring circle {
    fill: none;
    stroke-width: 3;
    transition: stroke-dashoffset 0.1s linear, stroke 0.3s ease;
}
    </style>

    {{-- Komponen Alpine idle-timer transaksi mandiri/otonom - didaftarkan
         sebagai named component (bukan inline x-init string) supaya tidak
         rentan bug parsing/quoting, dan supaya progress-nya bisa dipakai
         reaktif untuk UI countdown ring di sekitar avatar. --}}
    <script>
        document.addEventListener('alpine:init', () => {
    Alpine.data('transaksiCepatIdleTimer', () => ({
        idleTimeoutMs: 10000,
        tickMs: 100,
        msLeft: 10000,
        timerId: null,
        listenersAttached: false,

        init() {
            this.resetTimer();

            if (! this.listenersAttached) {
                const activityEvents = ['keydown', 'input', 'click', 'mousemove'];
                this._onActivity = () => this.resetTimer();
                activityEvents.forEach(evt => document.addEventListener(evt, this._onActivity));

                document.addEventListener('livewire:navigating', () => {
                    this.stopTimer();
                    activityEvents.forEach(evt => document.removeEventListener(evt, this._onActivity));
                }, { once: true });

                this.listenersAttached = true;
            }
        },

        resetTimer() {
            this.msLeft = this.idleTimeoutMs;

            if (this.timerId) {
                clearInterval(this.timerId);
            }

            this.timerId = setInterval(() => {
                this.msLeft -= this.tickMs;

                if (this.msLeft <= 0) {
                    this.stopTimer();
                    this.msLeft = this.idleTimeoutMs;
                    this.$wire.selesai();
                }
            }, this.tickMs);
        },

        stopTimer() {
            if (this.timerId) {
                clearInterval(this.timerId);
                this.timerId = null;
            }
        },

        get progress() {
            return Math.max(0, Math.min(1, this.msLeft / this.idleTimeoutMs));
        },

        get secondsLeft() {
            return Math.ceil(Math.max(0, this.msLeft) / 1000);
        },

        // circumference lingkaran r=42 -> 2 * PI * 42
        get ringDashoffset() {
            const circumference = 263.89;

            return circumference * (1 - this.progress);
        },

        // Hijau -> kuning -> merah seiring waktu habis. Threshold dipilih
        // supaya "kuning" (peringatan) sudah mulai terlihat saat sisa
        // waktu tinggal 40% (4 detik dari total 10 detik), dan "merah"
        // (mendesak) di 6 detik terakhir (20% dari total).
        get ringColor() {
            if (this.progress > 0.4) {
                return '#22c55e'; // hijau (success)
            }

            if (this.progress > 0.2) {
                return '#eab308'; // kuning (warning)
            }

            return '#ef4444'; // merah (danger)
        },
    }));
});
    </script>

    <div style="display: flex; justify-content: center; padding: 2rem 1rem;">
        <div
            class="transaksi-cepat-card"
            style="width: 100%; max-width: 460px; border-radius: 20px; padding: 2rem;"
            x-data="transaksiCepatIdleTimer()"
        >
            @if (! $user)
                {{-- Identifikasi user: satu input, auto-deteksi kartu/NISN vs nama --}}
                <div
                    x-data
                    x-init="$nextTick(() => $refs.kartu.focus())"
                    style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 1rem 0;"
                >
                    <div
                        style="display: flex; align-items: center; justify-content: center; width: 88px; height: 88px; border-radius: 50%; margin-bottom: 1.25rem; background: linear-gradient(135deg, var(--primary-400), var(--primary-600));"
                    >
                        <x-filament::icon icon="heroicon-o-credit-card" style="width: 40px; height: 40px; color: #fff;" />
                    </div>

                    <h2 class="text-gray-950 dark:text-white" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Tempelkan kartu atau ketik nama</h2>
                    <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.875rem; margin-bottom: 1.5rem;">
                        Scan kartu RFID, ketik NISN, atau ketik nama siswa/pegawai.
                    </p>

                    <div style="width: 100%; position: relative; text-align: left;">
                        <input
                            x-ref="kartu"
                            type="text"
                            wire:model.live.debounce.400ms="kartuInput"
                            wire:keydown.enter="scanKartu($event.target.value)"
                            autofocus
                            class="fi-input"
                            style="width: 100%; border-radius: 9999px; text-align: center; padding: 0.75rem 1.5rem;"
                            placeholder="Scan kartu / NISN / nama..."
                        />

                        @if (mb_strlen(trim((string) $kartuInput)) >= 2 && $this->hasilCariUser->isNotEmpty())
                            <div
                                x-data="{ show: false }"
                                x-init="requestAnimationFrame(() => show = true)"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                style="margin-top: 0.75rem; display: flex; flex-direction: column; gap: 0.375rem; max-height: 280px; overflow-y: auto;"
                            >
                                @foreach ($this->hasilCariUser as $hasil)
                                    <button
                                        type="button"
                                        wire:click="pilihUser('{{ $hasil->id }}')"
                                        wire:key="hasil-user-{{ $hasil->id }}"
                                        class="bg-gray-50 dark:bg-white/5 hover:bg-primary-50 dark:hover:bg-primary-500/10"
                                        style="display: flex; align-items: center; gap: 0.625rem; padding: 0.6rem 0.75rem; border-radius: 12px; border: none; cursor: pointer; text-align: left; width: 100%;"
                                    >
                                        <div style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; font-weight: 600; font-size: 13px; color: #fff; background: var(--primary-500); flex-shrink: 0;">
                                            {{ collect(explode(' ', $hasil->nama))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                                        </div>
                                        <div style="min-width: 0;">
                                            <p class="text-gray-950 dark:text-white" style="font-weight: 500; font-size: 0.875rem; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $hasil->nama }}</p>
                                            <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 0;">{{ $hasil->nisn ? "NISN {$hasil->nisn}" : ($hasil->nip ? "NIP {$hasil->nip}" : '-') }}</p>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div
                    x-data="{ show: false }"
                    x-init="requestAnimationFrame(() => show = true)"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                >
                    {{-- Profil user --}}
                    <div style="display: flex; flex-direction: column; align-items: center; text-align: center;">
                        <div class="transaksi-cepat-avatar-wrap">
                            @if ($user->avatar)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar) }}"
                                    alt="{{ $user->nama }}"
                                    width="80"
                                    height="80"
                                    style="display: block; width: 80px; height: 80px; margin: 4px; border-radius: 50%; object-fit: cover; border: 3px solid {{ $user->status_suspend ? 'var(--danger-500)' : 'var(--primary-500)' }};"
                                />
                            @else
                                <div style="display: flex; align-items: center; justify-content: center; width: 80px; height: 80px; margin: 4px; border-radius: 50%; font-weight: 600; font-size: 22px; color: #fff; background: {{ $user->status_suspend ? 'var(--danger-500)' : 'var(--primary-500)' }};">
                                    {{ collect(explode(' ', $user->nama))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                                </div>
                            @endif

                            {{-- Countdown ring: mengelilingi avatar, penuh saat user baru
                                 dimuat, mengecil linear ke nol dalam 5 detik idle. Warna
                                 memakai token Filament yang sama dgn border avatar supaya
                                 konsisten dgn status suspend/aktif. --}}
                            <svg class="transaksi-cepat-ring" viewBox="0 0 88 88">
                                <circle
                                    cx="44"
                                    cy="44"
                                    r="42"
                                    :stroke="ringColor"
                                    stroke-dasharray="263.89"
                                    :stroke-dashoffset="ringDashoffset"
                                    stroke-linecap="round"
                                ></circle>
                            </svg>

                            <span style="position: absolute; bottom: 2px; right: 2px; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; border: 2px solid #fff; background: {{ $user->status_suspend ? 'var(--danger-500)' : 'var(--success-500)' }};">
                                <x-filament::icon
                                    :icon="$user->status_suspend ? 'heroicon-s-lock-closed' : 'heroicon-s-check'"
                                    style="width: 14px; height: 14px; color: #fff;"
                                />
                            </span>
                        </div>

                        <p class="text-gray-400 dark:text-gray-500" style="font-size: 0.6875rem; margin: 0 0 0.25rem; font-variant-numeric: tabular-nums;" x-text="'Reset otomatis dalam ' + secondsLeft + ' detik'"></p>

                        <h2 class="text-gray-950 dark:text-white" style="font-size: 1.125rem; font-weight: 600; line-height: 1.3; margin: 0;">{{ $user->nama }}</h2>
                        @if ($user->nisn || $user->nip)
                            <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0;">
                                {{ $user->nisn ? "NISN {$user->nisn}" : "NIP {$user->nip}" }}
                            </p>
                        @endif

                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.75rem; margin-bottom: 1.5rem;">
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
                    </div>

                    {{-- Input buku: satu input, auto-deteksi barcode/ISBN vs judul --}}
                    <div class="dark:border-white/10" style="border-top: 1px solid rgba(0,0,0,0.08); padding-top: 1.5rem; margin-top: 0.5rem;">
                        <div x-data x-init="$refs.kode.focus()" style="width: 100%; position: relative; text-align: left;">
                            <label class="text-gray-950 dark:text-white" style="display: block; text-align: center; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">
                                Scan Barcode / ISBN / Ketik Judul Buku
                            </label>
                            <input
                                x-ref="kode"
                                type="text"
                                wire:model.live.debounce.400ms="kodeInput"
                                wire:keydown.enter="scanKode($event.target.value)"
                                autofocus
                                class="fi-input"
                                style="width: 100%; border-radius: 9999px; text-align: center; padding: 0.75rem 1.5rem; font-size: 1rem;"
                                placeholder="Scan barcode/ISBN atau ketik judul buku..."
                            />
                            <p class="text-gray-400 dark:text-gray-500" style="text-align: center; font-size: 0.75rem; margin-top: 0.5rem;">
                                Sistem otomatis mendeteksi pinjam / kembali per eksemplar.
                            </p>

                            @if (mb_strlen(trim((string) $kodeInput)) >= 2 && $this->hasilCariBuku->isNotEmpty())
                                <div
                                    x-data="{ show: false }"
                                    x-init="requestAnimationFrame(() => show = true)"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    style="margin-top: 0.75rem; display: flex; flex-direction: column; gap: 0.375rem; max-height: 240px; overflow-y: auto;"
                                >
                                    @foreach ($this->hasilCariBuku as $hasil)
                                        <button
                                            type="button"
                                            wire:click="pilihBuku('{{ $hasil->id }}')"
                                            wire:key="hasil-buku-{{ $hasil->id }}"
                                            class="bg-gray-50 dark:bg-white/5 hover:bg-primary-50 dark:hover:bg-primary-500/10"
                                            style="display: flex; align-items: center; gap: 0.625rem; padding: 0.6rem 0.75rem; border-radius: 12px; border: none; cursor: pointer; text-align: left; width: 100%;"
                                        >
                                            <div style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: var(--primary-100); flex-shrink: 0;">
                                                <x-filament::icon icon="heroicon-o-book-open" style="width: 16px; height: 16px; color: var(--primary-600);" />
                                            </div>
                                            <div style="min-width: 0;">
                                                <p class="text-gray-950 dark:text-white" style="font-weight: 500; font-size: 0.875rem; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $hasil->judul }}</p>
                                                <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 0;">{{ $hasil->penulis ?: '-' }} &middot; stok tersedia: {{ $hasil->stokTersedia() }}</p>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div style="display: flex; justify-content: center; margin-top: 1.25rem;">
                            <x-filament::button
                                wire:click="selesai"
                                color="gray"
                                icon="heroicon-o-arrow-path"
                                size="sm"
                            >
                                Ganti user
                            </x-filament::button>
                        </div>
                    </div>

                    {{-- Statistik sesi --}}
                    @php
                        $totalDipinjam = collect($riwayatScan)->where('aksi', 'dipinjamkan')->where('sukses', true)->count();
                        $totalDikembalikan = collect($riwayatScan)->where('aksi', 'dikembalikan')->where('sukses', true)->count();
                        $totalGagal = collect($riwayatScan)->where('sukses', false)->count();
                    @endphp
                    @if (count($riwayatScan) > 0)
                        <div class="dark:border-white/10" style="display: flex; align-items: center; justify-content: center; gap: 1.5rem; border-top: 1px solid rgba(0,0,0,0.08); padding-top: 1.5rem; margin-top: 1.5rem;">
                            <div style="text-align: center;">
                                <p class="text-primary-600 dark:text-primary-400" style="font-size: 1.5rem; font-weight: 600; margin: 0;">{{ $totalDipinjam }}</p>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0;">Dipinjamkan</p>
                            </div>
                            <div class="bg-gray-200 dark:bg-gray-700" style="height: 32px; width: 1px;"></div>
                            <div style="text-align: center;">
                                <p class="text-success-600 dark:text-success-400" style="font-size: 1.5rem; font-weight: 600; margin: 0;">{{ $totalDikembalikan }}</p>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0;">Dikembalikan</p>
                            </div>
                            <div class="bg-gray-200 dark:bg-gray-700" style="height: 32px; width: 1px;"></div>
                            <div style="text-align: center;">
                                <p class="text-danger-600 dark:text-danger-400" style="font-size: 1.5rem; font-weight: 600; margin: 0;">{{ $totalGagal }}</p>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0;">Gagal</p>
                            </div>
                        </div>
                    @endif

                    {{-- Riwayat scan --}}
                    <div class="dark:border-white/10" style="border-top: 1px solid rgba(0,0,0,0.08); padding-top: 1.5rem; margin-top: 1.5rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                            <p class="text-gray-950 dark:text-white" style="font-size: 0.875rem; font-weight: 600; margin: 0;">Riwayat Scan</p>
                            @if (count($riwayatScan) > 0)
                                <x-filament::badge color="gray">{{ count($riwayatScan) }} item</x-filament::badge>
                            @endif
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 260px; overflow-y: auto;">
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
                                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                                            <p class="text-gray-950 dark:text-white" style="font-weight: 500; font-size: 0.875rem; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['judul'] }}</p>
                                            @if ($item['sukses'])
                                                <x-filament::badge :color="$item['aksi'] === 'dipinjamkan' ? 'primary' : 'success'" size="sm">
                                                    {{ ucfirst($item['aksi']) }}
                                                </x-filament::badge>
                                            @endif
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['barcode'] }} &middot; {{ $item['pesan'] }}</p>
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
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
