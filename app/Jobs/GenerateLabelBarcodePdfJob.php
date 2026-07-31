<?php

namespace App\Jobs;

use App\Models\Eksemplar;
use App\Models\User;
use App\Services\LabelBarcodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Action;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Job generate PDF label barcode untuk banyak Buku sekaligus (bulk action
 * BukuResource) - dijalankan di queue 'default' agar tidak timeout HTTP
 * request Livewire (Aturan poin 3 - reuse LabelBarcodeService, jangan
 * duplikasi logic generate barcode di sini).
 *
 * PENTING (Aturan poin 17): $timeout di bawah WAJIB <= --timeout worker
 * queue 'default' di supervisor config - lihat catatan perubahan
 * supervisor yang mengikuti perubahan ini.
 */
class GenerateLabelBarcodePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /**
     * Konsisten dengan --timeout=180 pada supervisor worker queue
     * 'default' (WAJIB diupdate manual, lihat catatan di respons ini).
     */
    public int $timeout = 170;

    public function __construct(
        protected array $bukuIds,
        protected string $userId,
    ) {}

    public function handle(LabelBarcodeService $service): void
    {
        $eksemplars = Eksemplar::query()
            ->whereIn('buku_id', $this->bukuIds)
            ->with('buku')
            ->get();

        $user = User::query()->find($this->userId);

        if (! $user) {
            Log::error("GenerateLabelBarcodePdfJob: user id '{$this->userId}' tidak ditemukan, notifikasi dibatalkan.");

            return;
        }

        if ($eksemplars->isEmpty()) {
            Notification::make()
                ->warning()
                ->title('Tidak ada Eksemplar')
                ->body('Buku yang dipilih belum punya Eksemplar untuk dicetak labelnya.')
                ->sendToDatabase($user);

            return;
        }

        $labels = $service->generateData($eksemplars);

        $pdf = Pdf::loadView('pdf.label-barcode', ['labels' => $labels])
            ->setPaper('a4', 'portrait');

        $filename = 'label-barcode-'.now()->format('Ymd-His').'-'.substr(md5(uniqid()), 0, 6).'.pdf';
        $path = "labels/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        Notification::make()
            ->success()
            ->title('Label barcode siap diunduh')
            ->body(count($labels).' label dari '.count($this->bukuIds).' buku berhasil dibuat.')
            ->actions([
                Action::make('download')
                    ->label('Download PDF')
                    ->url(Storage::disk('public')->url($path))
                    ->openUrlInNewTab(),
            ])
            ->sendToDatabase($user);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateLabelBarcodePdfJob: gagal generate label. Buku IDs: '.implode(',', $this->bukuIds).". Error: {$exception->getMessage()}");

        $user = User::query()->find($this->userId);

        if ($user) {
            Notification::make()
                ->danger()
                ->title('Gagal membuat label barcode')
                ->body('Terjadi kesalahan saat memproses PDF. Coba lagi atau hubungi Admin.')
                ->sendToDatabase($user);
        }
    }
}
