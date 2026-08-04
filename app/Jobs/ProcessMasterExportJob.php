<?php

namespace App\Jobs;

use App\Enums\StatusBulkJob;
use App\Filament\Exports\MasterDataExporter;
use App\Models\BulkDataJob;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessMasterExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // dataset besar - lihat Aturan konfirmasi "wajib queue"

    public function __construct(protected string $bulkDataJobId) {}

    public function handle(): void
    {
        $job = BulkDataJob::query()->findOrFail($this->bulkDataJobId);
        $job->update(['status' => StatusBulkJob::Diproses]);

        try {
            $path = 'bulk-exports/master-data-'.now()->format('Ymd_His').'.xlsx';
            Excel::store(new MasterDataExporter, $path, 'local');

            $job->update([
                'status' => StatusBulkJob::Selesai,
                'file_path' => $path,
            ]);

            $this->notifikasi($job, success: true);
        } catch (Throwable $e) {
            $job->update([
                'status' => StatusBulkJob::Gagal,
                'laporan' => ['error' => $e->getMessage()],
            ]);

            $this->notifikasi($job, success: false, pesan: $e->getMessage());
        }
    }

    protected function notifikasi(BulkDataJob $job, bool $success, ?string $pesan = null): void
    {
        $user = User::find($job->diproses_oleh);
        if (! $user) {
            return;
        }

        $notif = Notification::make()
            ->title($success ? 'Export Master Data selesai' : 'Export Master Data gagal')
            ->body($success ? 'File siap diunduh di halaman Import & Export Data.' : $pesan);

        $success ? $notif->success() : $notif->danger();

        $notif->sendToDatabase($user);
    }
}
