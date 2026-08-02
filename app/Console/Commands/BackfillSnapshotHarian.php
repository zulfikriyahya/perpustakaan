<?php

namespace App\Console\Commands;

use App\Services\SnapshotHarianService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Command SEKALI-JALAN (manual, TIDAK didaftarkan di routes/console.php
 * Schedule) - dijalankan sekali oleh Admin setelah migration
 * snapshot_harians pertama kali di-deploy, untuk mengisi histori
 * sebelum tabel ini ada. Idempotent - aman dijalankan ulang (SnapshotHarianService
 * pakai updateOrCreate per tanggal).
 *
 * TODO: ASUMSI - default 30 hari ke belakang (--hari). Ganti sesuai
 * kebutuhan retensi trend yang diinginkan.
 */
class BackfillSnapshotHarian extends Command
{
    protected $signature = 'perpustakaan:snapshot-harian-backfill {--hari=30 : Jumlah hari ke belakang dari hari ini}';

    protected $description = 'Isi ulang SnapshotHarian untuk N hari ke belakang (dipakai sekali setelah deploy migration snapshot_harians).';

    public function handle(SnapshotHarianService $snapshotHarianService): int
    {
        $jumlahHari = (int) $this->option('hari');

        if ($jumlahHari < 1) {
            $this->error('Opsi --hari harus >= 1.');

            return self::FAILURE;
        }

        $this->info("Membackfill snapshot untuk {$jumlahHari} hari ke belakang...");

        $bar = $this->output->createProgressBar($jumlahHari);

        for ($i = $jumlahHari - 1; $i >= 0; $i--) {
            $tanggal = Carbon::now()->subDays($i)->startOfDay();
            $snapshotHarianService->catatUntukTanggal($tanggal);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill selesai.');

        return self::SUCCESS;
    }
}
