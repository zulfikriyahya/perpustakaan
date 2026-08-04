<x-filament-panels::page>
    <style>
        .iem-wrapper {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .iem-box {
            padding: 28px;
        }

        .iem-head {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }

        .iem-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .iem-icon svg {
            width: 22px;
            height: 22px;
        }

        .iem-icon--export {
            background: rgba(56, 189, 248, 0.12);
            color: rgb(56, 189, 248);
        }

        .iem-icon--import {
            background: rgba(52, 211, 153, 0.12);
            color: rgb(52, 211, 153);
        }

        .iem-icon--history {
            background: rgba(167, 139, 250, 0.12);
            color: rgb(167, 139, 250);
        }

        .iem-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 4px 0;
        }

        .iem-desc {
            font-size: 0.8125rem;
            line-height: 1.5;
            color: rgb(148 163 184);
            margin: 0;
        }

        .iem-action {
            margin-top: 4px;
        }

        /* Riwayat */
        .iem-list {
            display: flex;
            flex-direction: column;
        }

        .iem-row {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 18px 0 18px 24px;
            border-left: 2px solid rgba(255, 255, 255, 0.08);
        }

        .iem-row::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 24px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgb(100 116 139);
            border: 2px solid var(--iem-dot-bg, #18181b);
        }

        .iem-row--selesai::before {
            background: rgb(52, 211, 153);
        }

        .iem-row--gagal::before {
            background: rgb(248, 113, 113);
        }

        .iem-row--diproses::before {
            background: rgb(250, 204, 21);
        }

        .iem-row:last-child {
            padding-bottom: 4px;
        }

        .iem-row-info {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .iem-row-tipe {
            font-weight: 600;
            font-size: 0.875rem;
        }

        .iem-row-time {
            font-size: 0.75rem;
            color: rgb(148 163 184);
        }

        .iem-row-time::before {
            content: '·';
            margin-right: 10px;
            color: rgb(100 116 139);
        }

        .iem-row-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .iem-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: rgb(56, 189, 248);
        }

        .iem-link svg {
            width: 14px;
            height: 14px;
        }

        .iem-report {
            font-size: 0.75rem;
        }

        .iem-report-summary {
            cursor: pointer;
            font-weight: 500;
            color: rgb(167, 139, 250);
            list-style: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .iem-report-summary::-webkit-details-marker {
            display: none;
        }

        .iem-report-summary::after {
            content: '▾';
            font-size: 0.65rem;
            transition: transform 0.15s ease;
        }

        details[open] .iem-report-summary::after {
            transform: rotate(180deg);
        }

        .iem-report-body {
            margin-top: 12px;
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .iem-report-item {
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .iem-report-item strong {
            color: rgb(226 232 240);
        }

        .iem-report-errors {
            margin: 6px 0 0 16px;
            list-style: disc;
            color: rgb(248 113 113);
        }

        .iem-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 40px 0;
            text-align: center;
            color: rgb(100 116 139);
        }

        .iem-empty svg {
            width: 32px;
            height: 32px;
            opacity: 0.5;
        }

        .iem-empty p {
            font-size: 0.8125rem;
            margin: 0;
        }

        @media (min-width: 640px) {
            .iem-row {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }
    </style>

    <div wire:poll.5s class="iem-wrapper">
        <x-filament::section>
            <div class="iem-box">
                <div class="iem-head">
                    <div class="iem-icon iem-icon--export">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="iem-title">Export Semua Data</h3>
                        <p class="iem-desc">
                            Menghasilkan SATU file .xlsx berisi {{ count($this->getDaftarModel()) }} sheet (satu per jenis data), sesuai urutan baku sistem. Proses berjalan di latar belakang.
                        </p>
                    </div>
                </div>

                <div class="iem-action">
                    <x-filament::button wire:click="mulaiExport" icon="heroicon-o-arrow-down-tray">
                        Mulai Export Semua
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="iem-box">
                <div class="iem-head">
                    <div class="iem-icon iem-icon--import">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="iem-title">Import Semua Data</h3>
                        <p class="iem-desc">
                            Upload file .xlsx (wajib hasil Export Semua di atas). Data yang berhasil tetap tersimpan meski ada baris lain yang gagal.
                        </p>
                    </div>
                </div>

                <div class="iem-action">
                    {{ $this->mulaiImportAction() }}
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="iem-box">
                <div class="iem-head">
                    <div class="iem-icon iem-icon--history">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="iem-title">Riwayat Proses</h3>
                        <p class="iem-desc">10 aktivitas terakhir, diperbarui otomatis tiap 5 detik.</p>
                    </div>
                </div>

                <div class="iem-list">
                    @forelse ($this->getRiwayatJobs() as $job)
                        <div class="iem-row iem-row--{{ $job->status->value }}">
                            <div class="iem-row-info">
                                <span class="iem-row-tipe">{{ ucfirst($job->tipe->value) }}</span>

                                <x-filament::badge :color="match($job->status->value) {
                                    'selesai' => 'success',
                                    'gagal' => 'danger',
                                    'diproses' => 'warning',
                                    default => 'gray',
                                }">
                                    {{ ucfirst($job->status->value) }}
                                </x-filament::badge>

                                <span class="iem-row-time">{{ $job->created_at->diffForHumans() }}</span>
                            </div>

                            <div class="iem-row-actions">
                                @if ($url = $this->unduhUrl($job))
                                    <a href="{{ $url }}" class="fi-link iem-link" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        Unduh Hasil
                                    </a>
                                @endif

                                @if ($job->laporan && $job->tipe->value === 'import')
                                    <details class="iem-report">
                                        <summary class="iem-report-summary">Lihat Laporan</summary>
                                        <div class="iem-report-body">
                                            @foreach ($job->laporan as $key => $ringkasan)
                                                @if (is_array($ringkasan) && isset($ringkasan['total']))
                                                    <div class="iem-report-item">
                                                        <strong>{{ $key }}</strong>:
                                                        {{ $ringkasan['sukses'] }} sukses,
                                                        {{ $ringkasan['gagal'] }} gagal dari {{ $ringkasan['total'] }}

                                                        @if (! empty($ringkasan['errors']))
                                                            <ul class="iem-report-errors">
                                                                @foreach ($ringkasan['errors'] as $err)
                                                                    <li>{{ $err }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="iem-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-15H8.25A2.25 2.25 0 0 0 6 4.5v15A2.25 2.25 0 0 0 8.25 21.75H15.75A2.25 2.25 0 0 0 18 19.5V9L12.75 3z" />
                            </svg>
                            <p>Belum ada proses.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
