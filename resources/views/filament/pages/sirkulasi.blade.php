<x-filament-panels::page>
    <style>
        .fi-sidebar {
            display: none !important;
        }

        .fi-main-ctn {
            margin-inline-start: 0 !important;
        }

        .fi-topbar .fi-sidebar-open-btn {
            display: none !important;
        }

        /* BARU (gap iterasi ini) - heading halaman ("Sirkulasi") sudah
           dikosongkan lewat getHeading() di Sirkulasi.php, tapi container
           header tetap dirender (kosong) oleh layout panel dan
           menyisakan spacing kosong di atas konten - fallback CSS ini
           menyembunyikan container tsb sepenuhnya, murni kosmetik
           konsisten dgn pola sembunyi-sidebar di atas. */
        .fi-header {
            display: none !important;
        }

        .sirkulasi-section {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.04);
            padding: 1.25rem;
        }

        html.dark .sirkulasi-section {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.16);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.04),
                0 20px 50px -12px rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(6px);
        }

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

        .jam-analog-svg circle.dial {
            fill: none;
            stroke-width: 3;
        }

        .jam-analog-svg line {
            stroke-linecap: round;
        }

        /* BARU: kolom ditukar (1fr utama, 320px jam) - SEBELUMNYA tetap
           "320px 1fr" walau urutan visual sudah ditukar lewat `order`,
           akibatnya section utama (order: 1, masuk track pertama) malah
           kepencet ke 320px dan jam (order: 2) melebar 1fr - kebalik dari
           yang dimaksud. Ukuran track sekarang ikut ditukar supaya
           section utama benar-benar lebar (1fr) dan jam tetap ringkas
           (320px), konsisten dengan posisi visualnya. */
        .sirkulasi-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 1.25rem;
            align-items: stretch;
        }

        @media (max-width: 900px) {
            .sirkulasi-grid {
                grid-template-columns: 1fr;
            }
        }

        .sirkulasi-grid > .jam-analog-wrapper {
            order: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .sirkulasi-grid > .transaksi-cepat-card {
            order: 1;
        }

        @media (max-width: 900px) {
            .sirkulasi-grid > .jam-analog-wrapper,
            .sirkulasi-grid > .transaksi-cepat-card {
                order: initial;
            }
        }

        /* Wrapper - min-height percobaan sebelumnya DIHAPUS (nilainya
           tidak akurat, menyebabkan footer terdorong ke luar layar).
           Footer sekarang fixed ke viewport (lihat app-footer--fixed-
           sirkulasi), jadi wrapper cukup diberi padding-bottom supaya
           konten paling bawah tidak tertutup footer fixed tersebut. */
        .sirkulasi-page-wrapper {
            display: flex;
            flex-direction: column;
            padding-bottom: 3.5rem;
        }

        .sirkulasi-page-content {
            flex: 1 0 auto;
        }

        /* BARU: jam analog dibuat lebih "berkelas" - dial kaca dengan
           gradient halus, index jam+menit bertingkat, jarum meruncing
           dengan drop-shadow tipis, cap tengah metalik. Murni visual,
           TIDAK mengubah binding Alpine (derajatJam/Menit/Detik tetap
           sama). */
        .jam-analog-wrapper {
            position: relative;
        }

        .jam-analog-svg {
            filter: drop-shadow(0 8px 20px rgba(0, 0, 0, 0.12));
        }

        html.dark .jam-analog-svg {
            filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.5));
        }

        .jam-analog-svg .dial-bg {
            fill: url(#jamGradientLight);
        }

        html.dark .jam-analog-svg .dial-bg {
            fill: url(#jamGradientDark);
        }

        .jam-analog-svg .dial-ring {
            fill: none;
            stroke: var(--gray-300);
            stroke-width: 1.5;
        }

        html.dark .jam-analog-svg .dial-ring {
            stroke: rgba(255, 255, 255, 0.18);
        }

        .jam-analog-svg .tick-jam {
            stroke: var(--gray-500);
            stroke-width: 2.5;
            stroke-linecap: round;
        }

        .jam-analog-svg .tick-menit {
            stroke: var(--gray-300);
            stroke-width: 1;
            stroke-linecap: round;
        }

        html.dark .jam-analog-svg .tick-menit {
            stroke: rgba(255, 255, 255, 0.12);
        }

        .jam-analog-svg .tangan-jam {
            stroke: var(--gray-950);
            stroke-width: 5;
            stroke-linecap: round;
        }

        html.dark .jam-analog-svg .tangan-jam {
            stroke: #fff;
        }

        .jam-analog-svg .tangan-menit {
            stroke: var(--gray-700);
            stroke-width: 3.5;
            stroke-linecap: round;
        }

        html.dark .jam-analog-svg .tangan-menit {
            stroke: rgba(255, 255, 255, 0.75);
        }

        .jam-analog-svg .tangan-detik {
            stroke: var(--primary-500);
            stroke-width: 1.5;
            stroke-linecap: round;
        }

        .jam-analog-svg .cap-tengah-luar {
            fill: var(--primary-500);
            opacity: 0.18;
        }

        .jam-analog-svg .cap-tengah-dalam {
            fill: var(--primary-500);
        }

        .modal-selamat-datang-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
        }

        .modal-selamat-datang-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 3rem 2.5rem;
            text-align: center;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }

        html.dark .modal-selamat-datang-card {
            background: #1e293b;
        }

        .modal-selamat-datang-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 88px;
            height: 88px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--success-400), var(--success-600));
        }
    </style>

<script>
    function registerAlpineComponentsSirkulasi() {
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
                if (this.timerId) clearInterval(this.timerId);
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

            get ringDashoffset() {
                const circumference = 263.89;
                return circumference * (1 - this.progress);
            },

            get ringColor() {
                if (this.progress > 0.4) return '#22c55e';
                if (this.progress > 0.2) return '#eab308';
                return '#ef4444';
            },
        }));

        Alpine.data('jamSirkulasi', () => ({
            now: new Date(),
            timerId: null,

            init() {
                this.timerId = setInterval(() => { this.now = new Date(); }, 1000);
                document.addEventListener('livewire:navigating', () => {
                    if (this.timerId) clearInterval(this.timerId);
                }, { once: true });
            },

            get jamDigital() {
                return this.now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            },

            get tanggalHariIni() {
                return this.now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            },

            get derajatJam() {
                return (this.now.getHours() % 12) * 30 + (this.now.getMinutes() * 0.5);
            },

            get derajatMenit() {
                return this.now.getMinutes() * 6 + (this.now.getSeconds() * 0.1);
            },

            get derajatDetik() {
                return this.now.getSeconds() * 6;
            },
        }));

        Alpine.data('sirkulasiAutoFocus', () => ({
            init() {
                const refocus = () => {
                    const el = document.querySelector('[data-sirkulasi-scan-input]');
                    if (el && document.activeElement !== el) {
                        el.focus();
                    }
                };

                this._refocus = refocus;
                document.addEventListener('livewire:morphed', refocus);
                document.addEventListener('click', () => setTimeout(refocus, 60));
                setTimeout(refocus, 150);

                document.addEventListener('livewire:navigating', () => {
                    document.removeEventListener('livewire:morphed', this._refocus);
                }, { once: true });

                // BARU (gap: "Uncaught (in promise) Object {status:null,...}"
                // di layar Sirkulasi standby) - reload PENUH halaman (bukan
                // wire:navigate) setelah kiosk benar-benar idle dalam waktu
                // lama, supaya session/CSRF selalu segar SEBELUM sempat
                // expired di tengah request otomatis (idle-timer
                // transaksiCepatIdleTimer / poll notifikasi Filament yang
                // tetap jalan di background walau kiosk tidak disentuh).
                //
                // TODO: ASUMSI - threshold 15 menit (jauh di bawah
                // SESSION_LIFETIME 120 menit di config/session.php) dipilih
                // sebagai margin aman default; sesuaikan jika pola pemakaian
                // kiosk di lapangan berbeda (mis. jeda antar siswa lebih
                // lama dari ini secara normal, bukan berarti "idle").
                this.initReloadIdleKiosk();
            },

            initReloadIdleKiosk() {
                const idleReloadMs = 15 * 60 * 1000;
                const activityEvents = ['keydown', 'input', 'click', 'mousemove'];
                let timerId = null;

                const jadwalkanReload = () => {
                    if (timerId) clearTimeout(timerId);
                    timerId = setTimeout(() => window.location.reload(), idleReloadMs);
                };

                const onActivity = () => jadwalkanReload();

                activityEvents.forEach(evt => document.addEventListener(evt, onActivity));
                // livewire:morphed dihitung sebagai "aktivitas" juga - kalau
                // transaksi memang sedang berjalan (scan berhasil dsb.),
                // kiosk TIDAK dianggap idle walau tidak ada keydown/klik
                // dalam window waktu tertentu (mis. antrean beberapa siswa
                // scan berurutan tanpa jeda mengetik).
                document.addEventListener('livewire:morphed', onActivity);

                document.addEventListener('livewire:navigating', () => {
                    if (timerId) clearTimeout(timerId);
                    activityEvents.forEach(evt => document.removeEventListener(evt, onActivity));
                    document.removeEventListener('livewire:morphed', onActivity);
                }, { once: true });

                jadwalkanReload();
            },
        }));
    }

    if (window.Alpine) {
        registerAlpineComponentsSirkulasi();
    } else {
        document.addEventListener('alpine:init', registerAlpineComponentsSirkulasi);
    }
</script>

    <div class="sirkulasi-page-wrapper" x-data="sirkulasiAutoFocus">

        @if ($tampilkanModalSelamatDatang)
            <div
                class="modal-selamat-datang-overlay"
                x-data
                x-init="setTimeout(() => $wire.tutupModalSelamatDatang(), {{ \App\Filament\Pages\Sirkulasi::DURASI_MODAL_SELAMAT_DATANG_MS }})"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
            >
                <div class="modal-selamat-datang-card">
                    <div class="modal-selamat-datang-icon">
                        <x-filament::icon icon="heroicon-o-check" style="width: 44px; height: 44px; color: #fff;" />
                    </div>
                    <h2 class="text-gray-950 dark:text-white" style="font-size: 1.375rem; font-weight: 600; margin: 0 0 0.5rem;">
                        Selamat datang, {{ $namaModalSelamatDatang }}!
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.9375rem; margin: 0 0 1rem;">
                        Data kunjungan Anda hari ini sudah tercatat.
                    </p>
                    <x-filament::badge color="warning" size="lg">
                        Total {{ $pointModalSelamatDatang }} Point
                    </x-filament::badge>
                </div>
            </div>
        @endif

        <div class="sirkulasi-page-content" style="display: flex; flex-direction: column; gap: 1.25rem; padding: 1.25rem 1rem;">

            <div class="sirkulasi-grid">
                <div class="sirkulasi-section jam-analog-wrapper" x-data="jamSirkulasi">
                    <svg class="jam-analog-svg" viewBox="0 0 200 200" width="200" height="200">
                        <defs>
                            <radialGradient id="jamGradientLight" cx="50%" cy="42%" r="75%">
                                <stop offset="0%" stop-color="#ffffff" />
                                <stop offset="100%" stop-color="#f1f5f9" />
                            </radialGradient>
                            <radialGradient id="jamGradientDark" cx="50%" cy="42%" r="75%">
                                <stop offset="0%" stop-color="rgba(255,255,255,0.09)" />
                                <stop offset="100%" stop-color="rgba(255,255,255,0.02)" />
                            </radialGradient>
                        </defs>

                        <circle class="dial-bg" cx="100" cy="100" r="94" />
                        <circle class="dial-ring" cx="100" cy="100" r="94" />
                        <circle class="dial-ring" cx="100" cy="100" r="86" opacity="0.5" />

                        {{-- Index menit (60, tipis) - statis, dirender server-side. --}}
                        @for ($i = 0; $i < 60; $i++)
                            @continue($i % 5 === 0)
                            @php $sudut = $i * 6 * M_PI / 180; @endphp
                            <line class="tick-menit"
                                x1="{{ 100 + 88 * sin($sudut) }}" y1="{{ 100 - 88 * cos($sudut) }}"
                                x2="{{ 100 + 92 * sin($sudut) }}" y2="{{ 100 - 92 * cos($sudut) }}" />
                        @endfor

                        {{-- Index jam (12, tebal) - statis, dirender server-side (FIX: sebelumnya
                             x-for di dalam <svg> menyebabkan Alpine crash karena <template>
                             kehilangan properti .content di dalam SVG foreign-content). --}}
                        @for ($i = 1; $i <= 12; $i++)
                            @php $sudut = $i * 30 * M_PI / 180; @endphp
                            <line class="tick-jam"
                                x1="{{ 100 + 80 * sin($sudut) }}" y1="{{ 100 - 80 * cos($sudut) }}"
                                x2="{{ 100 + 92 * sin($sudut) }}" y2="{{ 100 - 92 * cos($sudut) }}" />
                        @endfor

                        <line class="tangan-jam" x1="100" y1="100" :x2="100 + 48 * Math.sin(derajatJam * Math.PI / 180)" :y2="100 - 48 * Math.cos(derajatJam * Math.PI / 180)"></line>
                        <line class="tangan-menit" x1="100" y1="100" :x2="100 + 68 * Math.sin(derajatMenit * Math.PI / 180)" :y2="100 - 68 * Math.cos(derajatMenit * Math.PI / 180)"></line>
                        <line class="tangan-detik" x1="100" y1="100" :x2="100 + 78 * Math.sin(derajatDetik * Math.PI / 180)" :y2="100 - 78 * Math.cos(derajatDetik * Math.PI / 180)"></line>

                        <circle class="cap-tengah-luar" cx="100" cy="100" r="7" />
                        <circle class="cap-tengah-dalam" cx="100" cy="100" r="3.5" />
                    </svg>
                    <p class="text-gray-950 dark:text-white" style="font-size: 1rem; font-weight: 600; margin-top: 0.75rem; font-variant-numeric: tabular-nums;" x-text="jamDigital"></p>
                    <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin-top: 0.25rem; text-align: center;" x-text="tanggalHariIni"></p>
                </div>

                <div class="transaksi-cepat-card" style="border-radius: 20px; padding: 1.75rem;" x-data="transaksiCepatIdleTimer()">
                    @if (! $user)
                        <div x-data x-init="$nextTick(() => $refs.kartu.focus())" style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 1rem 0;">
                            <div style="display: flex; align-items: center; justify-content: center; width: 88px; height: 88px; border-radius: 50%; margin-bottom: 1.25rem; background: linear-gradient(135deg, var(--primary-400), var(--primary-600));">
                                <x-filament::icon icon="heroicon-o-credit-card" style="width: 40px; height: 40px; color: #fff;" />
                            </div>

                            <h2 class="text-gray-950 dark:text-white" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Tempelkan kartu atau ketik nama</h2>
                            <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.875rem; margin-bottom: 1.5rem;">Scan kartu RFID, ketik NISN, atau ketik nama siswa/pegawai.</p>

                            <div style="width: 100%; position: relative; text-align: left;">
                                <input
                                    x-ref="kartu"
                                    type="text"
                                    data-sirkulasi-scan-input
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
                                            <button type="button" wire:click="pilihUser('{{ $hasil->id }}')" wire:key="hasil-user-{{ $hasil->id }}" class="bg-gray-50 dark:bg-white/5 hover:bg-primary-50 dark:hover:bg-primary-500/10" style="display: flex; align-items: center; gap: 0.625rem; padding: 0.6rem 0.75rem; border-radius: 12px; border: none; cursor: pointer; text-align: left; width: 100%;">
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
                            <div style="display: flex; flex-direction: column; align-items: center; text-align: center;">
                                <div class="transaksi-cepat-avatar-wrap">
                                    @if ($user->avatar)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar) }}" alt="{{ $user->nama }}" width="80" height="80" style="display: block; width: 80px; height: 80px; margin: 4px; border-radius: 50%; object-fit: cover; border: 3px solid {{ $user->status_suspend ? 'var(--danger-500)' : 'var(--primary-500)' }};" />
                                    @else
                                        <div style="display: flex; align-items: center; justify-content: center; width: 80px; height: 80px; margin: 4px; border-radius: 50%; font-weight: 600; font-size: 22px; color: #fff; background: {{ $user->status_suspend ? 'var(--danger-500)' : 'var(--primary-500)' }};">
                                            {{ collect(explode(' ', $user->nama))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                                        </div>
                                    @endif

                                    <svg class="transaksi-cepat-ring" viewBox="0 0 88 88">
                                        <circle cx="44" cy="44" r="42" :stroke="ringColor" stroke-dasharray="263.89" :stroke-dashoffset="ringDashoffset" stroke-linecap="round"></circle>
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
                                    <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0;">{{ $user->nisn ? "NISN {$user->nisn}" : "NIP {$user->nip}" }}</p>
                                @endif

                                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                                    <x-filament::badge :color="$user->status_suspend ? 'danger' : 'success'">{{ $user->status_suspend ? 'Suspend' : 'Aktif' }}</x-filament::badge>
                                    <x-filament::badge :color="$bisaMeminjam ? 'success' : 'gray'">{{ $bisaMeminjam ? 'Bisa meminjam' : 'Tidak bisa meminjam baru' }}</x-filament::badge>
                                    <x-filament::badge color="warning">{{ $user->akumulasi_point }} Point</x-filament::badge>
                                </div>

                                @if ($user->status_suspend)
                                    <div class="bg-warning-50 dark:bg-warning-500/10 text-warning-600 dark:text-warning-400" style="display: flex; align-items: flex-start; gap: 0.5rem; border-radius: 12px; padding: 0.75rem; font-size: 0.875rem; margin-bottom: 1.5rem; text-align: left; width: 100%;">
                                        <x-filament::icon icon="heroicon-o-exclamation-triangle" style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;" />
                                        <span>User masih bisa mengembalikan buku, tapi tidak bisa meminjam baru sampai Denda lunas.</span>
                                    </div>
                                @endif
                            </div>

                            <div class="dark:border-white/10" style="border-top: 1px solid rgba(0,0,0,0.08); padding-top: 1.5rem; margin-top: 0.5rem;">
                                <div x-data x-init="$nextTick(() => $refs.kode.focus())" style="width: 100%; position: relative; text-align: left;">
                                    <label class="text-gray-950 dark:text-white" style="display: block; text-align: center; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Scan Barcode / ISBN / Ketik Judul Buku</label>
                                    <input
                                        x-ref="kode"
                                        type="text"
                                        data-sirkulasi-scan-input
                                        wire:model.live.debounce.400ms="kodeInput"
                                        wire:keydown.enter="scanKode($event.target.value)"
                                        autofocus
                                        class="fi-input"
                                        style="width: 100%; border-radius: 9999px; text-align: center; padding: 0.75rem 1.5rem; font-size: 1rem;"
                                        placeholder="Scan barcode/ISBN atau ketik judul buku..."
                                    />
                                    <p class="text-gray-400 dark:text-gray-500" style="text-align: center; font-size: 0.75rem; margin-top: 0.5rem;">Sistem otomatis mendeteksi pinjam / kembali per eksemplar.</p>

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
                                                <button type="button" wire:click="pilihBuku('{{ $hasil->id }}')" wire:key="hasil-buku-{{ $hasil->id }}" class="bg-gray-50 dark:bg-white/5 hover:bg-primary-50 dark:hover:bg-primary-500/10" style="display: flex; align-items: center; gap: 0.625rem; padding: 0.6rem 0.75rem; border-radius: 12px; border: none; cursor: pointer; text-align: left; width: 100%;">
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
                                    <x-filament::button wire:click="selesai" color="gray" icon="heroicon-o-arrow-path" size="sm">Ganti user</x-filament::button>
                                </div>
                            </div>

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

                            <div class="dark:border-white/10" style="border-top: 1px solid rgba(0,0,0,0.08); padding-top: 1.5rem; margin-top: 1.5rem;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                                    <p class="text-gray-950 dark:text-white" style="font-size: 0.875rem; font-weight: 600; margin: 0;">Riwayat Scan (sesi ini)</p>
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
                                                        <x-filament::badge :color="$item['aksi'] === 'dipinjamkan' ? 'primary' : 'success'" size="sm">{{ ucfirst($item['aksi']) }}</x-filament::badge>
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

            <livewire:riwayat-sirkulasi-harian />

        </div>
    </div>
</x-filament-panels::page>
