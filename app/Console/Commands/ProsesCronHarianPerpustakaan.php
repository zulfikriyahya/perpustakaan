<?php

namespace App\Console\Commands;

use App\Services\PeminjamanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper Artisan command untuk PeminjamanService::prosesCronHarian().
 * Logic perhitungan/transisi status TIDAK diduplikasi di sini - lihat
 * Aturan poin 3 (Prinsip DRY), seluruh logic tetap di PeminjamanService.
 */
class ProsesCronHarianPerpustakaan extends Command
{
    protected $signature = 'perpustakaan:cron-harian';

    protected $description = 'Jalankan cron harian Peminjaman: reminder H-3/H-1 dan transisi status Terlambat.';

    public function handle(PeminjamanService $peminjamanService): int
    {
        $mulai = now();

        $stat = $peminjamanService->prosesCronHarian();

        $durasiMs = now()->diffInMilliseconds($mulai);

        $this->info(sprintf(
            'Cron harian selesai: reminder_h3=%d, reminder_h1=%d, jadi_terlambat=%d (%d ms)',
            $stat['reminder_h3'],
            $stat['reminder_h1'],
            $stat['jadi_terlambat'],
            $durasiMs,
        ));

        Log::info('ProsesCronHarianPerpustakaan selesai.', [
            'reminder_h3' => $stat['reminder_h3'],
            'reminder_h1' => $stat['reminder_h1'],
            'jadi_terlambat' => $stat['jadi_terlambat'],
            'durasi_ms' => $durasiMs,
        ]);

        return self::SUCCESS;
    }
}
