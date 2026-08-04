<?php

namespace App\Filament\Pages;

use App\Enums\StatusBulkJob;
use App\Enums\TipeBulkJob;
use App\Jobs\ProcessMasterExportJob;
use App\Jobs\ProcessMasterImportJob;
use App\Models\BulkDataJob;
use App\Support\MasterDataRegistry;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

/**
 * Halaman terpusat Import/Export Master Data - HANYA super_admin
 * (dikonfirmasi Aturan poin 3 gap ini, bukan permission Shield per-model
 * seperti Resource lain). Proses BERAT (queue, lihat ProcessMasterExportJob/
 * ProcessMasterImportJob) - halaman ini hanya trigger + poll status via
 * wire:poll, TIDAK memproses apapun secara sinkron.
 *
 * Tidak menggantikan ImportAction/ExportAction per-Resource yang sudah
 * ada (dikonfirmasi keduanya tetap ada, Aturan poin 4 gap ini).
 *
 * TODO: ASUMSI (dikonfirmasi) - urutan sheet Export = urutan Import,
 * mengikuti MasterDataRegistry::items(). File yang diupload untuk Import
 * WAJIB berasal dari hasil "Export Semua" halaman ini (posisi sheet
 * dipetakan by INDEX, bukan nama - lihat ProcessMasterImportJob).
 */
class ImportExportMaster extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static ?string $navigationLabel = 'Import & Export Data';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected string $view = 'filament.pages.import-export-master';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function getHeading(): string|HtmlString
    {
        return 'Import & Export Data (Master)';
    }

    public function getDaftarModel(): array
    {
        return MasterDataRegistry::items();
    }

    public function getRiwayatJobs()
    {
        return BulkDataJob::query()->latest()->limit(10)->get();
    }

    public function mulaiExport(): void
    {
        $job = BulkDataJob::create([
            'tipe' => TipeBulkJob::Export,
            'status' => StatusBulkJob::Pending,
            'diproses_oleh' => auth()->id(),
        ]);

        ProcessMasterExportJob::dispatch($job->id);

        Notification::make()
            ->info()
            ->title('Export Master Data dimulai')
            ->body('Proses berjalan di latar belakang - Anda akan menerima notifikasi begitu selesai.')
            ->send();
    }

    public function mulaiImportAction(): Action
    {
        return Action::make('mulai_import')
            ->label('Upload & Mulai Import')
            ->icon('heroicon-o-arrow-up-tray')
            ->requiresConfirmation()
            ->modalDescription('File WAJIB hasil "Export Semua" TERBARU (atau punya urutan & jumlah sheet identik) - sheet dipetakan berdasarkan posisi, bukan nama. PERHATIAN: format sheet "User" (kini wajib kolom jurusan_kode + tahun_pelajaran, bukan hanya "kelas") dan "KelasTahunPelajaran" (kini wajib wali_kelas_nip, bukan nama) telah berubah - file hasil Export Semua LAMA sebelum pembaruan ini tidak bisa dipakai untuk Import Semua lagi, silakan export ulang terlebih dahulu. Baris yang gagal akan dilaporkan di akhir, baris yang sukses tetap tersimpan (partial success).')
            ->schema([
                FileUpload::make('file_import')
                    ->label('File Master Data (.xlsx)')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->disk('local')
                    ->directory('bulk-imports')
                    ->required()
                    ->validationMessages([
                        'required' => 'File wajib diunggah.',
                    ]),
            ])
            ->action(function (array $data) {
                $job = BulkDataJob::create([
                    'tipe' => TipeBulkJob::Import,
                    'status' => StatusBulkJob::Pending,
                    'nama_file_asli' => $data['file_import'],
                    'file_path' => $data['file_import'],
                    'diproses_oleh' => auth()->id(),
                ]);

                ProcessMasterImportJob::dispatch($job->id);

                Notification::make()
                    ->info()
                    ->title('Import Master Data dimulai')
                    ->body('Proses berjalan di latar belakang - Anda akan menerima notifikasi begitu selesai.')
                    ->send();
            });
    }

    public function unduhUrl(BulkDataJob $job): ?string
    {
        return $job->status === StatusBulkJob::Selesai && $job->tipe === TipeBulkJob::Export
            ? route('bulk-data-job.download', $job)
            : null;
    }
}
