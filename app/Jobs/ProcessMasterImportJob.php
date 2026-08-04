<?php

namespace App\Jobs;

use App\Enums\StatusBulkJob;
use App\Models\BulkDataJob;
use App\Models\User;
use App\Support\MasterDataRegistry;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * TODO: ASUMSI (WAJIB DIKONFIRMASI, belum berubah dari sebelumnya) -
 * sheet dipetakan ke model berdasarkan POSISI/URUTAN fisik di file,
 * SAMA PERSIS dengan urutan MasterDataRegistry::items(). File yang
 * diupload WAJIB berasal dari hasil "Export Semua" TERBARU (setelah
 * perubahan kontrak sheet 'user'/'kelas_tahun_pelajaran' iterasi ini) -
 * file export lama sebelum perubahan ini TIDAK KOMPATIBEL.
 *
 * Kegagalan SATU baris tidak membatalkan baris lain (partial success) -
 * setiap baris dibungkus DB::transaction sendiri.
 *
 * BARU (iterasi ini): closure 'import' di registry BOLEH mengembalikan
 * array meta (mis. ['kartu_dihapus' => 1]) - dijumlahkan per key ke
 * dalam laporan['<sheet>']['meta'], dipakai untuk notifikasi kartu RFID
 * terhapus pada sheet 'user' (jalur ini tidak lewat model Import
 * Filament, jadi tidak bisa pakai pola Cache "import-{id}-..." seperti
 * UserImporter).
 */
class ProcessMasterImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public function __construct(protected string $bulkDataJobId) {}

    public function handle(): void
    {
        $job = BulkDataJob::query()->findOrFail($this->bulkDataJobId);
        $job->update(['status' => StatusBulkJob::Diproses]);

        try {
            $rawSheets = Excel::toArray(new class {}, storage_path('app/' . $job->file_path));
            $registry = MasterDataRegistry::items();
            $laporan = [];

            foreach ($registry as $index => $item) {
                $sheetRows = $rawSheets[$index] ?? null;

                if (! $item['importable'] || $sheetRows === null) {
                    continue; // read-only atau sheet tidak ada di file - dilewati, bukan error
                }

                $laporan[$item['key']] = $this->prosesSheet($item, $sheetRows);
            }

            $job->update(['status' => StatusBulkJob::Selesai, 'laporan' => $laporan]);
            $this->notifikasi($job, success: true);
        } catch (Throwable $e) {
            $job->update([
                'status' => StatusBulkJob::Gagal,
                'laporan' => ['error' => $e->getMessage()],
            ]);
            $this->notifikasi($job, success: false, pesan: $e->getMessage());
        }
    }

    /**
     * @return array{total: int, sukses: int, gagal: int, errors: array<int, string>, meta: array<string, int>}
     */
    protected function prosesSheet(array $item, array $rawRows): array
    {
        if (empty($rawRows)) {
            return ['total' => 0, 'sukses' => 0, 'gagal' => 0, 'errors' => [], 'meta' => []];
        }

        $headings = array_map(fn($h) => Str::slug((string) $h, '_'), array_shift($rawRows));
        $sukses = 0;
        $errors = [];
        $meta = [];

        foreach ($rawRows as $nomorBaris => $rawRow) {
            if (empty(array_filter($rawRow, fn($v) => $v !== null && $v !== ''))) {
                continue; // baris kosong - dilewati, tidak dihitung total
            }

            $row = array_combine($headings, array_pad($rawRow, count($headings), null));

            try {
                $hasilMeta = DB::transaction(function () use ($item, $row) {
                    return ($item['import'])($row);
                });

                if (is_array($hasilMeta)) {
                    foreach ($hasilMeta as $metaKey => $metaValue) {
                        if (is_numeric($metaValue)) {
                            $meta[$metaKey] = ($meta[$metaKey] ?? 0) + $metaValue;
                        }
                    }
                }

                $sukses++;
            } catch (RowImportFailedException $e) {
                $errors[] = 'Baris ' . ($nomorBaris + 2) . ": {$e->getMessage()}"; // +2: heading + index 0-based
            } catch (Throwable $e) {
                $errors[] = 'Baris ' . ($nomorBaris + 2) . ": Gagal tidak terduga - {$e->getMessage()}";
            }
        }

        return [
            'total' => $sukses + count($errors),
            'sukses' => $sukses,
            'gagal' => count($errors),
            'errors' => $errors,
            'meta' => $meta,
        ];
    }

    protected function notifikasi(BulkDataJob $job, bool $success, ?string $pesan = null): void
    {
        $user = User::find($job->diproses_oleh);
        if (! $user) {
            return;
        }

        $totalGagal = $success ? collect($job->laporan)->sum('gagal') : null;
        $kartuDihapus = $success ? (int) (collect($job->laporan)->pluck('meta.kartu_dihapus')->filter()->sum()) : 0;

        $bodyParts = [];
        if ($success) {
            $bodyParts[] = $totalGagal > 0
                ? "{$totalGagal} baris gagal - lihat laporan di halaman Import & Export Data."
                : 'Semua baris berhasil diimpor.';
            if ($kartuDihapus > 0) {
                $bodyParts[] = "PERHATIAN: {$kartuDihapus} kartu RFID dihapus dari user (kolom dikosongkan di file) - user tersebut tidak bisa tap RFID sampai didaftarkan ulang.";
            }
        }

        $notif = Notification::make()
            ->title($success ? 'Import Master Data selesai' : 'Import Master Data gagal')
            ->body($success ? implode(' ', $bodyParts) : $pesan);

        $success && $totalGagal === 0 ? $notif->success() : $notif->warning();
        $success || $notif->danger();

        $notif->sendToDatabase($user);
    }
}
