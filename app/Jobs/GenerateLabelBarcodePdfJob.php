<?php

namespace App\Jobs;

use App\Models\Eksemplar;
use App\Models\User;
use App\Services\LabelBarcodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateLabelBarcodePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 170;

    public function __construct(
        protected array $eksemplarIds,
        protected string $userId,
    ) {}

    public function handle(LabelBarcodeService $service): void
    {
        $eksemplars = Eksemplar::query()
            ->whereIn('id', $this->eksemplarIds)
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
                ->body('Tidak ada Eksemplar yang ditemukan untuk dicetak labelnya.')
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
            ->body(count($labels).' label berhasil dibuat.')
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
        Log::error('GenerateLabelBarcodePdfJob: gagal generate label. EksemplarIDs: '.implode(',', $this->eksemplarIds).". Error: {$exception->getMessage()}");

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
