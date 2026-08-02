<?php

namespace App\Console\Commands;

use App\Services\PeminjamanService;
use App\Services\SnapshotHarianService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper Artisan command untuk PeminjamanService::prosesCronHarian().
 * Logic perhitungan/transisi status TIDAK diduplikasi di sini - lihat
 * Aturan poin 3 (Prinsip DRY), seluruh logic tetap di PeminjamanService.
 *
 * BARU (iterasi ini): setelah reminder/transisi status selesai, catat
 * SnapshotHarian untuk HARI INI - dijalankan SETELAH prosesCronHarian()
 * supaya transisi status Terlambat hari ini ikut tercermin di snapshot
 * (Aturan poin 3 - reuse SnapshotHarianService, jangan duplikasi
 * perhitungan agregat di sini).
 */
class ProsesCronHarianPerpustakaan extends Command
{
    protected $signature = 'perpustakaan:cron-harian';

    protected $description = 'Jalankan cron harian Peminjaman: reminder H-3/H-1, transisi status Terlambat, dan catat snapshot harian.';

    public function handle(PeminjamanService $peminjamanService, SnapshotHarianService $snapshotHarianService): int
    {
        $mulai = now();

        $stat = $peminjamanService->prosesCronHarian();

        $snapshot = $snapshotHarianService->catatUntukTanggal(now());

        $durasiMs = now()->diffInMilliseconds($mulai);

        $this->info(sprintf(
            'Cron harian selesai: reminder_h3=%d, reminder_h1=%d, jadi_terlambat=%d, snapshot_tanggal=%s (%d ms)',
            $stat['reminder_h3'],
            $stat['reminder_h1'],
            $stat['jadi_terlambat'],
            $snapshot->tanggal->toDateString(),
            $durasiMs,
        ));

        Log::info('ProsesCronHarianPerpustakaan selesai.', [
            'reminder_h3' => $stat['reminder_h3'],
            'reminder_h1' => $stat['reminder_h1'],
            'jadi_terlambat' => $stat['jadi_terlambat'],
            'snapshot_tanggal' => $snapshot->tanggal->toDateString(),
            'durasi_ms' => $durasiMs,
        ]);

        return self::SUCCESS;
    }
}
