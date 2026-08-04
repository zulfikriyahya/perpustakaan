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
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

/**
 * Sheet dipetakan ke model berdasarkan POSISI/URUTAN fisik di file, SAMA
 * PERSIS dengan urutan MasterDataRegistry::items() - SEKARANG (iterasi
 * ini) divalidasi eksplisit lewat validasiUrutanSheet() SEBELUM baris
 * manapun diproses (Aturan poin 8/12 - sebelumnya hanya TODO: ASUMSI
 * tanpa pengecekan nyata, berisiko baris ter-import diam-diam ke model
 * yang salah kalau urutan/sheet tidak sesuai).
 *
 * File yang diupload WAJIB berasal dari hasil "Export Semua" TERBARU
 * (setelah perubahan kontrak sheet 'user'/'kelas_tahun_pelajaran') - file
 * export lama sebelum perubahan itu TIDAK KOMPATIBEL dan sekarang akan
 * GAGAL TOTAL di validasiUrutanSheet() dengan pesan jelas (bukan diproses
 * diam-diam dengan pemetaan yang salah).
 *
 * Kegagalan SATU baris (setelah validasi struktur lolos) tidak
 * membatalkan baris lain (partial success) - setiap baris dibungkus
 * DB::transaction sendiri.
 *
 * closure 'import' di registry BOLEH mengembalikan array meta (mis.
 * ['kartu_dihapus' => 1]) - dijumlahkan per key ke dalam
 * laporan['<sheet>']['meta'], dipakai untuk notifikasi kartu RFID
 * terhapus pada sheet 'user'.
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
            $absolutePath = storage_path('app/' . $job->file_path);
            $registry = MasterDataRegistry::items();

            // BARU (iterasi ini) - validasi struktur file SEBELUM baris
            // manapun diproses. Jika nama/urutan sheet tidak cocok,
            // seluruh job GAGAL TOTAL dengan pesan jelas - mencegah baris
            // ter-import diam-diam ke model yang salah karena pemetaan
            // by-index posisi.
            $this->validasiUrutanSheet($absolutePath, $registry);

            $rawSheets = Excel::toArray(new class {}, $absolutePath);
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
     * Membandingkan nama sheet FISIK di file (dibaca langsung dari
     * workbook, bukan ditebak dari heading kolom) terhadap urutan 'label'
     * di MasterDataRegistry::items() - HARUS identik urutan dan nama,
     * karena ProcessMasterImportJob memetakan sheet berikutnya by index
     * posisi, bukan nama.
     *
     * TODO: verifikasi signature terhadap versi phpoffice/phpspreadsheet
     * yang benar-benar terpasang (dependency dari maatwebsite/excel
     * ^3.1 di composer.json, versi pasti belum diverifikasi terhadap
     * composer.lock) - IOFactory::load()->getSheetNames() diasumsikan
     * stabil di rilis phpspreadsheet yang umum dipakai versi ini.
     *
     * @throws RuntimeException jika nama/urutan sheet tidak cocok.
     */
    protected function validasiUrutanSheet(string $absolutePath, array $registry): void
    {
        $namaSheetFile = IOFactory::load($absolutePath)->getSheetNames();
        $namaSheetDiharapkan = array_map(fn(array $item) => $item['label'], $registry);

        if ($namaSheetFile !== $namaSheetDiharapkan) {
            throw new RuntimeException(
                'File tidak sesuai format hasil "Export Semua" terbaru - urutan atau nama sheet tidak cocok. '
                    . 'Sheet ditemukan di file: [' . implode(', ', $namaSheetFile) . ']. '
                    . 'Sheet yang diharapkan sistem: [' . implode(', ', $namaSheetDiharapkan) . ']. '
                    . 'Silakan export ulang lewat "Mulai Export Semua" di halaman ini, lalu gunakan file hasilnya (tanpa diedit strukturnya) untuk Import Semua.'
            );
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

    /**
     * REFACTOR (iterasi ini): logika warna notifikasi sebelumnya
     * mengandalkan urutan short-circuit dua baris terpisah
     * ($success && $totalGagal === 0 ? ... ; $success || $notif->danger();)
     * - "bekerja" tapi rapuh, gampang salah kalau di-refactor tanpa sadar
     * urutannya penting. Diganti match() eksplisit, perilaku IDENTIK:
     * - gagal total -> danger
     * - sukses tapi ada baris gagal -> warning
     * - sukses semua -> success
     */
    protected function notifikasi(BulkDataJob $job, bool $success, ?string $pesan = null): void
    {
        $user = User::find($job->diproses_oleh);
        if (! $user) {
            return;
        }

        $totalGagal = $success ? (int) collect($job->laporan)->sum('gagal') : null;
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

        $warna = match (true) {
            ! $success => 'danger',
            $totalGagal > 0 => 'warning',
            default => 'success',
        };

        $notif = Notification::make()
            ->title($success ? 'Import Master Data selesai' : 'Import Master Data gagal')
            ->body($success ? implode(' ', $bodyParts) : $pesan);

        match ($warna) {
            'danger' => $notif->danger(),
            'warning' => $notif->warning(),
            'success' => $notif->success(),
        };

        $notif->sendToDatabase($user);
    }
}
