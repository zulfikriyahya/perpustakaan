<x-filament-panels::page>
    <div wire:poll.5s>
        <x-filament::section>
            <x-slot name="heading">Export Semua Data</x-slot>
            <x-slot name="description">
                Menghasilkan SATU file .xlsx berisi {{ count($this->getDaftarModel()) }} sheet (satu per jenis data), sesuai urutan baku sistem. Proses berjalan di latar belakang.
            </x-slot>

            <x-filament::button wire:click="mulaiExport" icon="heroicon-o-arrow-down-tray">
                Mulai Export Semua
            </x-filament::button>
        </x-filament::section>

        <x-filament::section class="mt-6">
            <x-slot name="heading">Import Semua Data</x-slot>
            <x-slot name="description">
                Upload file .xlsx (wajib hasil Export Semua di atas). Data yang berhasil tetap tersimpan meski ada baris lain yang gagal.
            </x-slot>

            {{ $this->mulaiImportAction() }}
        </x-filament::section>

        <x-filament::section class="mt-6">
            <x-slot name="heading">Riwayat Proses (10 terakhir)</x-slot>

            <x-filament::grid>
                @forelse ($this->getRiwayatJobs() as $job)
                    <div class="flex items-center justify-between border-b py-2">
                        <div>
                            <span class="font-medium">{{ ucfirst($job->tipe->value) }}</span>
                            —
                            <x-filament::badge :color="match($job->status->value) {
                                'selesai' => 'success',
                                'gagal' => 'danger',
                                'diproses' => 'warning',
                                default => 'gray',
                            }">
                                {{ ucfirst($job->status->value) }}
                            </x-filament::badge>
                            <span class="text-xs text-gray-500">{{ $job->created_at->diffForHumans() }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($url = $this->unduhUrl($job))
                                <a href="{{ $url }}" class="fi-link text-sm" target="_blank">Unduh Hasil</a>
                            @endif

                            @if ($job->laporan && $job->tipe->value === 'import')
                                <details class="text-xs">
                                    <summary class="cursor-pointer text-primary-600">Lihat Laporan</summary>
                                    <div class="mt-2 space-y-1">
                                        @foreach ($job->laporan as $key => $ringkasan)
                                            @if (is_array($ringkasan) && isset($ringkasan['total']))
                                                <div>
                                                    <strong>{{ $key }}</strong>:
                                                    {{ $ringkasan['sukses'] }} sukses,
                                                    {{ $ringkasan['gagal'] }} gagal dari {{ $ringkasan['total'] }}
                                                    @if (! empty($ringkasan['errors']))
                                                        <ul class="ml-4 list-disc text-danger-600">
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
                    <p class="text-sm text-gray-500">Belum ada proses.</p>
                @endforelse
            </x-filament::grid>
        </x-filament::section>
    </div>
</x-filament-panels::page>
