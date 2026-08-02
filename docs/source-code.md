# SOURCE CODE - perpustakaan

## app/Console/Commands/BackfillSnapshotHarian.php
```php
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

```
---

## app/Console/Commands/ProsesCronHarianPerpustakaan.php
```php
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

```
---

## app/Enums/EventTypePoint.php
```php
<?php

namespace App\Enums;

enum EventTypePoint: string
{
    case Kunjungan = 'kunjungan';
    case Peminjaman = 'peminjaman';
    case Pengembalian = 'pengembalian';
    case Kerusakan = 'kerusakan';
    case Kehilangan = 'kehilangan';
}

```
---

## app/Enums/GroupSetting.php
```php
<?php

namespace App\Enums;

enum GroupSetting: string
{
    case Peminjaman = 'peminjaman';
    case Point = 'point';
    case Notifikasi = 'notifikasi';
    case Denda = 'denda';
    case Device = 'device';
    case Whatsapp = 'whatsapp';
}

```
---

## app/Enums/JenisKelamin.php
```php
<?php

namespace App\Enums;

enum JenisKelamin: string
{
    case LakiLaki = 'L';
    case Perempuan = 'P';

    public function label(): string
    {
        return match ($this) {
            self::LakiLaki => 'Laki-laki',
            self::Perempuan => 'Perempuan',
        };
    }
}

```
---

## app/Enums/JenisTransaksi.php
```php
<?php

namespace App\Enums;

enum JenisTransaksi: string
{
    case Peminjaman = 'peminjaman';
    case Kunjungan = 'kunjungan';
    case PembayaranDenda = 'pembayaran_denda';
}

```
---

## app/Enums/KondisiBuku.php
```php
<?php

namespace App\Enums;

enum KondisiBuku: string
{
    case Baik = 'baik';
    case Rusak = 'rusak';
    case Hilang = 'hilang';
}

```
---

## app/Enums/RoleUser.php
```php
<?php

namespace App\Enums;

enum RoleUser: string
{
    case Siswa = 'siswa';
    case Pegawai = 'pegawai';
    case Pustakawan = 'pustakawan';
    case Admin = 'super_admin';
}

```
---

## app/Enums/SourceKunjungan.php
```php
<?php

namespace App\Enums;

enum SourceKunjungan: string
{
    case Rfid = 'rfid';
    case Manual = 'manual';
}

```
---

## app/Enums/StatusAkademik.php
```php
<?php

namespace App\Enums;

enum StatusAkademik: string
{
    case Aktif = 'aktif';
    case Lulus = 'lulus';
    case Keluar = 'keluar';
}

```
---

## app/Enums/StatusEksemplar.php
```php
<?php

namespace App\Enums;

enum StatusEksemplar: string
{
    case Tersedia = 'tersedia';
    case Dipinjam = 'dipinjam';
    case Rusak = 'rusak';
    case Hilang = 'hilang';
}

```
---

## app/Enums/StatusOtaFirmware.php
```php
<?php

namespace App\Enums;

/**
 * Value PERSIS string yang dikirim device di field "status" pada endpoint
 * POST /api/perpustakaan/firmware/report (kontrak baru, lihat
 * PerpustakaanDeviceController::firmwareReport()). Jangan ubah value tanpa
 * menyesuaikan firmware juga.
 */
enum StatusOtaFirmware: string
{
    case Sukses = 'success';
    case Gagal = 'failed';
}

```
---

## app/Enums/StatusPeminjaman.php
```php
<?php

namespace App\Enums;

enum StatusPeminjaman: string
{
    case Aktif = 'aktif';
    case Terlambat = 'terlambat';
    case Selesai = 'selesai';
    case Hilang = 'hilang';
}

```
---

## app/Enums/StatusRefund.php
```php
<?php

namespace App\Enums;

enum StatusRefund: string
{
    case TidakPerlu = 'tidak_perlu';
    case PerluRefund = 'perlu_refund';
    case SudahDirefund = 'sudah_direfund';
}

```
---

## app/Enums/StatusRiwayatKelas.php
```php
<?php

namespace App\Enums;

enum StatusRiwayatKelas: string
{
    case Aktif = 'aktif';       // sedang berjalan di KTP ini
    case Naik = 'naik';         // selesai, siswa naik ke KTP berikutnya
    case Tinggal = 'tinggal';   // selesai, siswa tinggal kelas (KTP tingkat sama, tahun baru)
    case Lulus = 'lulus';       // selesai, siswa lulus dari KTP ini
    case Keluar = 'keluar';     // selesai, siswa keluar/pindah sekolah
}

```
---

## app/Enums/TipeDenda.php
```php
<?php

namespace App\Enums;

enum TipeDenda: string
{
    case Keterlambatan = 'keterlambatan';
    case Kerusakan = 'kerusakan';
    case Kehilangan = 'kehilangan';
}

```
---

## app/Exceptions/WhatsappGatewayException.php
```php
<?php

namespace App\Exceptions;

use Exception;

class WhatsappGatewayException extends Exception
{
    public function __construct(
        public readonly int $statusCode,
        string $pesanError,
    ) {
        parent::__construct("Gateway WhatsApp mengembalikan status {$statusCode}: {$pesanError}");
    }
}

```
---

## app/Filament/Exports/BukuExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Buku;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

/**
 * BUG FIX (iterasi ini, pola sama dengan bug 'kategoris' sebelumnya):
 * kolom 'rak.nama' dan 'stok' sudah tidak ada lagi di tabel/model Buku
 * sejak migration 2026_08_02_000002-000004 (rak & stok pindah jadi
 * per-Eksemplar, bukan per-judul-buku) - keduanya dihapus dari sini.
 *
 * TODO: GAP-SPEC - kolom 'rak' hasil export sekarang menampilkan daftar
 * nama Rak DISTINCT dari semua eksemplar buku ini (bisa lebih dari satu
 * kalau eksemplar tersebar di rak berbeda), dipisah '; ' sama seperti
 * 'kategori'. TAPI ini informasional saja - BukuImporter hanya menerima
 * SATU nama rak per baris (dipakaikan ke SEMUA eksemplar baru dari
 * selisih stok import itu), jadi hasil export TIDAK bisa diimpor ulang
 * mentah-mentah kalau satu judul buku punya eksemplar di rak berbeda-beda.
 * Admin perlu edit manual jadi satu nama rak sebelum import ulang.
 *
 * PERFORMA (BARU iterasi ini): modifyQuery() eager-load 'kategoris' dan
 * 'eksemplars.rak' supaya kolom 'rak'/'kategoris' di bawah tidak memicu
 * query terpisah per baris (N+1) saat export ratusan/ribuan judul buku -
 * TODO: verifikasi signature modifyQuery() terhadap filament/filament
 * ^5.7 di composer.json (dikonfirmasi ada di dokumentasi resmi untuk
 * versi 3.x, method statis override di kelas Exporter; belum diverifikasi
 * langsung terhadap changelog 5.7 apakah signature berubah).
 */
class BukuExporter extends Exporter
{
    protected static ?string $model = Buku::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['kategoris', 'eksemplars.rak']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('judul'),
            ExportColumn::make('penulis'),
            ExportColumn::make('penerbit'),
            ExportColumn::make('isbn')->label('ISBN'),
            ExportColumn::make('tahun_terbit')->label('Tahun Terbit'),
            ExportColumn::make('eksemplars')
                ->label('Jumlah Eksemplar')
                ->formatStateUsing(fn (Buku $record) => (string) $record->eksemplars->count()),
            ExportColumn::make('rak')
                ->label('Rak (distinct, lihat catatan)')
                ->formatStateUsing(fn (Buku $record) => $record->eksemplars
                    ->pluck('rak.nama')
                    ->filter()
                    ->unique()
                    ->implode('; ')),
            ExportColumn::make('kategoris')
                ->label('Kategori')
                ->formatStateUsing(fn (Buku $record) => $record->kategoris->pluck('nama')->implode('; ')),
            ExportColumn::make('harga_ganti')->label('Harga Ganti'),
            ExportColumn::make('deskripsi'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Buku selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/DendaExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Denda;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class DendaExporter extends Exporter
{
    protected static ?string $model = Denda::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('User'),
            ExportColumn::make('peminjaman.eksemplar.buku.judul')->label('Buku'), // FIX: relasi buku dipindah ke eksemplar (lihat migration alter_peminjamans_table_buku_to_eksemplar)
            ExportColumn::make('tipe'),
            ExportColumn::make('nominal'),
            ExportColumn::make('status_lunas'),
            ExportColumn::make('tanggal_lunas'),
            ExportColumn::make('status_refund'),
            ExportColumn::make('keterangan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Denda selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/EksemplarExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Eksemplar;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EksemplarExporter extends Exporter
{
    protected static ?string $model = Eksemplar::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('barcode'),
            ExportColumn::make('buku.isbn')
                ->label('ISBN Buku'),
            ExportColumn::make('buku.judul')
                ->label('Judul Buku'),
            ExportColumn::make('rak.nama')
                ->label('Rak'),
            ExportColumn::make('status')
                ->formatStateUsing(fn (Eksemplar $record) => $record->status->value),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Eksemplar selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/JurusanExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Jurusan;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class JurusanExporter extends Exporter
{
    protected static ?string $model = Jurusan::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('kode'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Jurusan selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/KategoriExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Kategori;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class KategoriExporter extends Exporter
{
    protected static ?string $model = Kategori::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('deskripsi'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Kategori selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/KelasExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Kelas;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class KelasExporter extends Exporter
{
    protected static ?string $model = Kelas::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('tingkat'),
            ExportColumn::make('jurusan.kode')->label('Kode Jurusan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Kelas selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/KelasTahunPelajaranExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\KelasTahunPelajaran;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class KelasTahunPelajaranExporter extends Exporter
{
    protected static ?string $model = KelasTahunPelajaran::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('kelas.nama')->label('Kelas'),
            // BARU iterasi ini - KelasTahunPelajaranImporter MEWAJIBKAN
            // kolom jurusan_kode (lihat catatan "PERUBAHAN KONTRAK" di
            // Importer). Tanpa kolom ini di hasil export, admin tidak
            // bisa langsung mengimpor ulang file yang sama - harus
            // mencari kode jurusan secara manual dulu di resource lain.
            ExportColumn::make('kelas.jurusan.kode')->label('Kode Jurusan'),
            ExportColumn::make('tahunPelajaran.nama')->label('Tahun Pelajaran'),
            ExportColumn::make('waliKelas.nama')->label('Wali Kelas'),
            ExportColumn::make('waliKelas.nip')->label('NIP Wali Kelas'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Kelas per Tahun Pelajaran selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/KunjunganExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Kunjungan;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class KunjunganExporter extends Exporter
{
    protected static ?string $model = Kunjungan::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('Pengunjung'),
            ExportColumn::make('tanggal'),
            ExportColumn::make('jam_tap'),
            ExportColumn::make('source'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Kunjungan selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/LevelBadgeExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\LevelBadge;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LevelBadgeExporter extends Exporter
{
    protected static ?string $model = LevelBadge::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama_badge'),
            ExportColumn::make('min_point'),
            ExportColumn::make('max_point'),
            ExportColumn::make('urutan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Level Badge selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/LevelBadgeLogExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\LevelBadgeLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LevelBadgeLogExporter extends Exporter
{
    protected static ?string $model = LevelBadgeLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('User'),
            ExportColumn::make('levelBadge.nama_badge')->label('Badge'),
            ExportColumn::make('tanggal_didapat'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Riwayat Badge selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/PeminjamanExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Peminjaman;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PeminjamanExporter extends Exporter
{
    protected static ?string $model = Peminjaman::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('Peminjam'),
            ExportColumn::make('eksemplar.buku.judul')
                ->label('Buku')
                ->formatStateUsing(fn ($state) => $state ?? '(eksemplar sudah dihapus permanen)'),
            ExportColumn::make('tanggal_pinjam'),
            ExportColumn::make('tanggal_jatuh_tempo'),
            ExportColumn::make('status'),
            ExportColumn::make('diprosesOleh.nama')->label('Diproses Oleh'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Peminjaman selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/PengembalianExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Pengembalian;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PengembalianExporter extends Exporter
{
    protected static ?string $model = Pengembalian::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('peminjaman.user.nama')->label('Peminjam'),
            ExportColumn::make('peminjaman.eksemplar.buku.judul')->label('Buku'),
            ExportColumn::make('tanggal_kembali'),
            ExportColumn::make('kondisi'),
            ExportColumn::make('catatan'),
            ExportColumn::make('diprosesOleh.nama')->label('Diproses Oleh'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Pengembalian selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/PunishmentExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Punishment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PunishmentExporter extends Exporter
{
    protected static ?string $model = Punishment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('deskripsi'),
            ExportColumn::make('threshold_point_minus'),
            ExportColumn::make('durasi_suspend_hari'),
            ExportColumn::make('aktif'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Punishment selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/PunishmentLogExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\PunishmentLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PunishmentLogExporter extends Exporter
{
    protected static ?string $model = PunishmentLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('User'),
            ExportColumn::make('punishment.nama')->label('Punishment'),
            ExportColumn::make('tanggal_diterapkan'),
            ExportColumn::make('tanggal_berakhir'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Riwayat Punishment selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/RakExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Rak;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class RakExporter extends Exporter
{
    protected static ?string $model = Rak::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('lokasi'),
            /**
             * TODO: verifikasi signature formatStateUsing() terhadap versi
             * filament/filament yang terpasang (composer.json: ^5.7).
             *
             * BUG FIX (ditemukan iterasi ini): sama kasus dengan
             * BukuExporter::kategoris - dipaksa pemisah ';' supaya cocok
             * dengan parser RakImporter (kolom 'kategori', Aturan poin 3),
             * mencegah kategori rak ter-sync kosong diam-diam saat file
             * hasil export diimpor ulang tanpa diedit.
             */
            ExportColumn::make('kategoris')
                ->label('Kategori Terkait')
                ->formatStateUsing(fn (Rak $record) => $record->kategoris->pluck('nama')->implode('; ')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Rak selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/RewardExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Reward;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class RewardExporter extends Exporter
{
    protected static ?string $model = Reward::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('deskripsi'),
            ExportColumn::make('threshold_point'),
            ExportColumn::make('aktif'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Reward selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/RewardLogExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\RewardLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class RewardLogExporter extends Exporter
{
    protected static ?string $model = RewardLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('User'),
            ExportColumn::make('reward.nama')->label('Reward'),
            ExportColumn::make('tanggal_didapat'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Riwayat Reward selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/TahunPelajaranExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\TahunPelajaran;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TahunPelajaranExporter extends Exporter
{
    protected static ?string $model = TahunPelajaran::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('tanggal_mulai'),
            ExportColumn::make('tanggal_selesai'),
            ExportColumn::make('aktif'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Tahun Pelajaran selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/TransaksiExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\Transaksi;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TransaksiExporter extends Exporter
{
    protected static ?string $model = Transaksi::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('User'),
            ExportColumn::make('jenis'),
            ExportColumn::make('diprosesOleh.nama')->label('Diproses Oleh'),
            ExportColumn::make('tanggal'),
            ExportColumn::make('keterangan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Transaksi selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Exports/UserExporter.php
```php
<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * SENGAJA tidak menyertakan kolom 'password' - meski sudah $hidden di
 * Model, tetap dieksplisitkan di sini sebagai lapisan keamanan kedua.
 *
 * Kolom 'kelas' (string bebas) diganti relasi kelasTahunPelajaran sejak
 * migration 2026_08_01_000006 - lihat kolom di bawah.
 *
 * BUG FIX (ditemukan iterasi ini): sebelumnya export tidak menyertakan
 * kode Jurusan. UserImporter::resolveKtp() MEWAJIBKAN 'jurusan_kode'
 * setiap kali 'kelas_nama' diisi (Aturan poin 3, satu sumber kebenaran
 * kontrak data) - tanpa kolom ini, hasil export TIDAK bisa diimpor ulang
 * untuk siswa manapun yang sudah punya kelas: seluruh baris tersebut akan
 * GAGAL dengan pesan "jurusan_kode kosong", sama pola dengan bug
 * kategori yang sudah diperbaiki di BukuExporter/RakExporter.
 */
class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('role'),
            ExportColumn::make('nisn')->label('NISN'),
            ExportColumn::make('nip')->label('NIP'),
            ExportColumn::make('kelasTahunPelajaran.kelas.nama')->label('Kelas'),
            // BARU iterasi ini - lihat BUG FIX di atas.
            ExportColumn::make('kelasTahunPelajaran.kelas.jurusan.kode')->label('Kode Jurusan'),
            ExportColumn::make('kelasTahunPelajaran.tahunPelajaran.nama')->label('Tahun Pelajaran'),
            ExportColumn::make('status_akademik')->label('Status Akademik'),
            ExportColumn::make('jabatan'),
            ExportColumn::make('no_telepon')->label('No. Telepon'),
            ExportColumn::make('no_kartu_rfid')->label('No. Kartu RFID'),
            ExportColumn::make('status_suspend')->label('Suspend'),
            ExportColumn::make('akumulasi_point')->label('Point'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export User selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/BukuImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Enums\StatusEksemplar;
use App\Models\Buku;
use App\Models\Eksemplar;
use App\Models\Kategori;
use App\Models\Rak;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * resolveRecord() upsert berdasarkan 'isbn' (barcode kini per eksemplar,
 * bukan per judul buku - lihat migration 2026_08_02_000003/000004).
 * Baris tanpa ISBN selalu jadi Buku baru.
 *
 * BUG FIX (iterasi ini): kolom 'barcode' SEBELUMNYA requiredMapping tanpa
 * fillRecordUsing no-op, padahal kolom 'barcode' sudah di-drop dari tabel
 * bukus - Filament akan mencoba assign $record->barcode sebelum save()
 * dan menyebabkan SQL error "Unknown column". Kolom 'barcode' dihapus
 * total dari sini; barcode eksemplar HANYA digenerate otomatis di
 * afterSave() (lihat TODO: GAP-SPEC di bawah), tidak lagi diambil dari
 * file import.
 *
 * BUG FIX (pola sama, ditemukan sebelumnya): kolom 'rak' dan 'kategori'
 * adalah lookup-only (bukan kolom asli tabel 'bukus') - tetap pakai
 * ->fillRecordUsing() no-op supaya tidak di-assign ke $record sebelum
 * save().
 *
 * KEPUTUSAN dikonfirmasi:
 * - harga_ganti WAJIB diisi manual di file - baris kosong GAGAL TOTAL
 *   (bukan default 0).
 * - Duplikasi ISBN antar baris/antar import: STOK diakumulasi (tambah
 *   eksemplar baru sejumlah selisih), eksemplar existing tidak dikurangi
 *   meski stok di file diturunkan.
 */
class BukuImporter extends Importer
{
    protected static ?string $model = Buku::class;

    /**
     * @var array<int, string>|null ID Kategori hasil resolve nama di
     *                              beforeSave() - null berarti kolom 'kategori' kosong (tidak
     *                              ada perubahan relasi). Divalidasi SEBELUM save() supaya baris
     *                              dengan nama kategori typo/tidak ditemukan GAGAL TOTAL
     *                              (dikonfirmasi) - bukan tersimpan sebagian dengan kategori
     *                              yang salah/hilang diam-diam.
     */
    protected ?array $kategoriIdsTerresolve = null;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('judul')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Laskar Pelangi'),
            ImportColumn::make('penulis')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Andrea Hirata'),
            ImportColumn::make('penerbit')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Bentang Pustaka'),
            ImportColumn::make('isbn')
                ->label('ISBN')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('9789793062792'),
            ImportColumn::make('tahun_terbit')
                ->label('Tahun Terbit')
                ->numeric()
                ->rules(['nullable', 'integer', 'digits:4'])
                ->example('2008'),
            ImportColumn::make('rak')
                ->label('Rak (nama, opsional)')
                ->helperText('Isi persis sesuai nama Rak yang sudah ada diMaster Data > Rak. Jika tidak ditemukan, buku diimpor tanpa lokasi rak (bukan dibuatkan Rak baru otomatis).')
                ->rules(['nullable', 'string'])
                ->example('Rak A')
                // BUG FIX - lookup-only, lihat docblock class.
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('kategori')
                ->label('Kategori (nama, pisah titik-koma jika lebih dari satu)')
                ->helperText('Isi persis sesuai nama Kategori yang sudah ada di Master Data > Kategori. Contoh 2 kategori: "Fiksi;Sains". Kategori yang tidak ditemukan namanya akan membuat baris GAGAL.')
                ->rules(['nullable', 'string'])
                ->example('Fiksi;Sastra Indonesia')
                // BUG FIX - lookup-only, lihat docblock class.
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('harga_ganti')
                ->label('Harga Ganti')
                ->helperText('WAJIB diisi manual - dipakai sebagai basis perhitungan Denda kerusakan/kehilangan. Baris tanpa nilai ini akan GAGAL, tidak ada default otomatis.')
                ->numeric()
                ->rules(['required', 'numeric', 'min:0'])
                ->example('75000'),
            ImportColumn::make('stok')
                ->helperText('Jumlah eksemplar fisik untuk ISBN ini. Import ulang ISBN yang sama akan MENAMBAH eksemplar sejumlah selisih (stok baru - jumlah eksemplar existing), tidak pernah mengurangi eksemplar yang sudah ada.')
                ->numeric()
                ->rules(['required', 'integer', 'min:0'])
                ->example('3')
                // BUG FIX (ditemukan iterasi ini, PENYEBAB ERROR "Unknown
                // column 'stok'"): kolom 'stok' bukan kolom asli tabel
                // 'bukus' (di-drop migration 2026_08_02_000003) - ini
                // murni input agregat yang dikonsumsi manual di afterSave()
                // untuk menghitung selisih eksemplar. Sama pola dengan
                // 'rak'/'kategori' - HARUS lookup-only, kalau tidak
                // Filament mencoba assign $record->stok sebelum save() dan
                // memicu SQL error "Unknown column 'stok' in 'INSERT INTO'".
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string'])
                ->example('Novel tentang perjuangan anak-anak Belitung mengejar pendidikan.'),
        ];
    }

    public function resolveRecord(): ?Buku
    {
        if (empty($this->data['isbn'])) {
            return new Buku;
        }

        return Buku::query()->firstOrNew(['isbn' => $this->data['isbn']]);
    }

    /**
     * Dipanggil setelah field kolom dasar di-assign, sebelum save() -
     * dipakai untuk resolusi 'rak'/'kategori' by nama (bukan foreign key
     * mentah), karena kolom ini bukan field langsung di tabel bukus.
     */
    protected function beforeSave(): void
    {
        if (! empty($this->data['kategori'])) {
            $namaKategoris = array_values(array_filter(array_map('trim', explode(';', $this->data['kategori']))));
            $kategoris = Kategori::query()->whereIn('nama', $namaKategoris)->get(['id', 'nama']);

            $namaTidakDitemukan = array_diff($namaKategoris, $kategoris->pluck('nama')->all());

            if (! empty($namaTidakDitemukan)) {
                throw new RowImportFailedException('Kategori tidak ditemukan: "'.implode('", "', $namaTidakDitemukan).'". Cek ejaan atau tambahkan Kategori-nya dulu di Master Data > Kategori.');
            }

            $this->kategoriIdsTerresolve = $kategoris->pluck('id')->all();
        }
    }

    protected function afterSave(): void
    {
        if ($this->kategoriIdsTerresolve !== null) {
            $this->record->kategoris()->sync($this->kategoriIdsTerresolve);
        }

        // GAP-SPEC ditutup: format barcode auto-generate FINAL, kini
        // terpusat di Eksemplar::generateBarcodeUntuk() (Aturan poin 3).
        // Konfirmasi sebelumnya: stok diakumulasi (tambah eksemplar
        // sejumlah selisih), tidak pernah mengurangi eksemplar existing
        // meski stok di file diturunkan.
        $rak = ! empty($this->data['rak'])
            ? Rak::query()->where('nama', trim($this->data['rak']))->first()
            : null;

        $stokDiminta = (int) ($this->data['stok'] ?? 0);
        $eksemplarSaatIni = $this->record->eksemplars()->count();
        $selisih = $stokDiminta - $eksemplarSaatIni;

        for ($i = 0; $i < $selisih; $i++) {
            $this->record->eksemplars()->create([
                'barcode' => Eksemplar::generateBarcodeUntuk($this->record, $eksemplarSaatIni + $i + 1),
                'rak_id' => $rak?->id,
                'status' => StatusEksemplar::Tersedia,
            ]);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Buku selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/EksemplarImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Enums\StatusEksemplar;
use App\Models\Buku;
use App\Models\Eksemplar;
use App\Models\Rak;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert berdasarkan 'barcode' (satu baris = satu unit fisik). TERPISAH
 * dari BukuImporter (yang beroperasi per-judul/agregat stok) - lihat
 * keputusan di percakapan: menggabungkan keduanya akan mencampur dua
 * granularitas berbeda (per-judul vs per-unit fisik) dalam satu importer.
 *
 * ATURAN KERAS - tidak boleh bypass PeminjamanService/PointService (Aturan
 * poin 3, dikonfirmasi eksplisit):
 * - Baris TIDAK BOLEH set status ke/dari 'Dipinjam' - status ini HANYA
 *   boleh berubah lewat PeminjamanService::prosesPeminjaman()/
 *   prosesPengembalian(). Baris yang mencoba ini GAGAL TOTAL.
 * - Kalau eksemplar existing (ditemukan by barcode) statusnya SEDANG
 *   'Dipinjam', SELURUH baris ditolak (tidak ada field lain yang
 *   ter-update juga) - selaras persis dengan EksemplarsRelationManager
 *   yang men-disable Edit/Delete untuk status ini.
 */
class EksemplarImporter extends Importer
{
    protected static ?string $model = Eksemplar::class;

    protected ?string $bukuIdTerresolve = null;

    protected ?string $rakIdTerresolve = null;

    protected ?StatusEksemplar $statusTerresolve = null;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('barcode')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('9789793062792-1')
                ->helperText('Kunci upsert. Kalau barcode sudah ada, baris ini meng-update eksemplar tersebut (rak/status). Kalau belum ada, dibuat eksemplar baru (wajib isi ISBN Buku).'),
            ImportColumn::make('isbn')
                ->label('ISBN Buku')
                ->rules(['nullable', 'string'])
                ->example('9789793062792')
                ->helperText('WAJIB diisi untuk eksemplar BARU (barcode belum ada). Untuk eksemplar yang SUDAH ADA, kolom ini diabaikan - pemindahan eksemplar ke judul buku lain tidak didukung lewat import.')
                // lookup-only, buku_id di-assign manual di beforeSave().
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('rak')
                ->label('Rak (nama, opsional)')
                ->rules(['nullable', 'string'])
                ->example('Rak A')
                ->helperText('Isi persis sesuai nama Rak yang sudah ada. Kosongkan untuk tidak mengubah rak eksemplar existing, atau tidak memberi rak pada eksemplar baru.')
                // lookup-only, rak_id di-assign manual di beforeSave().
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('status')
                ->rules(['nullable', 'string'])
                ->example('tersedia')
                ->helperText("Hanya 'tersedia', 'rusak', atau 'hilang'. TIDAK BISA di-set/diubah ke/dari 'dipinjam' lewat import - itu hanya lewat proses Peminjaman/Pengembalian.")
                // divalidasi & di-assign manual di beforeSave(), bukan
                // langsung ->rules(['in:...']) supaya pesan errornya lebih
                // jelas via RowImportFailedException.
                ->fillRecordUsing(fn (?string $state) => null),
        ];
    }

    public function resolveRecord(): ?Eksemplar
    {
        $barcode = trim($this->data['barcode']);

        return Eksemplar::query()->where('barcode', $barcode)->first()
            ?? new Eksemplar(['barcode' => $barcode]);
    }

    protected function beforeSave(): void
    {
        $isEksemplarBaru = ! $this->record->exists;

        // GAP-SPEC ditutup: eksemplar existing statusnya Dipinjam -> baris
        // ditolak total, tidak ada field lain yang ikut ter-update.
        if (! $isEksemplarBaru && $this->record->status === StatusEksemplar::Dipinjam) {
            throw new RowImportFailedException(
                "Eksemplar dengan barcode '{$this->record->barcode}' sedang berstatus Dipinjam - tidak bisa diubah lewat import. Ubah hanya lewat proses Pengembalian."
            );
        }

        // Resolusi Buku (WAJIB untuk eksemplar baru, diabaikan untuk existing).
        if ($isEksemplarBaru) {
            $isbn = trim($this->data['isbn'] ?? '');

            if ($isbn === '') {
                throw new RowImportFailedException(
                    "Barcode '{$this->record->barcode}' belum terdaftar - kolom ISBN Buku wajib diisi untuk membuat eksemplar baru."
                );
            }

            $buku = Buku::query()->where('isbn', $isbn)->first();

            if (! $buku) {
                throw new RowImportFailedException(
                    "Buku dengan ISBN '{$isbn}' tidak ditemukan. Tambahkan Buku-nya dulu di Master Data > Buku."
                );
            }

            $this->bukuIdTerresolve = $buku->id;
        }

        // Resolusi Rak (opsional, berlaku untuk baru maupun existing).
        if (! empty($this->data['rak'])) {
            $namaRak = trim($this->data['rak']);
            $rak = Rak::query()->where('nama', $namaRak)->first();

            if (! $rak) {
                throw new RowImportFailedException(
                    "Rak '{$namaRak}' tidak ditemukan. Cek ejaan atau tambahkan Rak-nya dulu di Master Data > Rak."
                );
            }

            $this->rakIdTerresolve = $rak->id;
        }

        // Resolusi & validasi Status - TIDAK BOLEH 'dipinjam' sama sekali.
        if (! empty($this->data['status'])) {
            $statusMentah = strtolower(trim($this->data['status']));

            if ($statusMentah === StatusEksemplar::Dipinjam->value) {
                throw new RowImportFailedException(
                    "Status 'dipinjam' tidak bisa di-set lewat import - status ini hanya berubah otomatis lewat proses Peminjaman."
                );
            }

            $statusValid = collect(StatusEksemplar::cases())->firstWhere('value', $statusMentah);

            if (! $statusValid) {
                throw new RowImportFailedException(
                    "Status '{$this->data['status']}' tidak dikenal. Gunakan salah satu: tersedia, rusak, hilang."
                );
            }

            $this->statusTerresolve = $statusValid;
        } elseif ($isEksemplarBaru) {
            // default untuk eksemplar baru kalau kolom status dikosongkan
            $this->statusTerresolve = StatusEksemplar::Tersedia;
        }

        if ($this->bukuIdTerresolve !== null) {
            $this->record->buku_id = $this->bukuIdTerresolve;
        }

        if ($this->rakIdTerresolve !== null) {
            $this->record->rak_id = $this->rakIdTerresolve;
        }

        if ($this->statusTerresolve !== null) {
            $this->record->status = $this->statusTerresolve;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Eksemplar selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/JurusanImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Models\Jurusan;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

// Upsert berdasarkan 'kode' (unique) - sama pola dengan KategoriImporter.
class JurusanImporter extends Importer
{
    protected static ?string $model = Jurusan::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Ilmu Pengetahuan Alam'),
            ImportColumn::make('kode')
                ->helperText('Kode unik jurusan, dipakai sebagai acuan di import Kelas & Kelas per Tahun Pelajaran. Jika kode sudah ada, data Jurusan tersebut akan diperbarui.')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('IPA'),
        ];
    }

    public function resolveRecord(): ?Jurusan
    {
        return Jurusan::query()->firstOrNew(['kode' => $this->data['kode']]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Jurusan selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/KategoriImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Models\Kategori;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert case-insensitive berdasarkan 'nama' (dikonfirmasi) - "Fiksi"
 * dan "fiksi" dianggap kategori yang sama, mencegah duplikat akibat
 * ketidakkonsistenan pengetikan staf. Jika sudah ada baris cocok,
 * ejaan/kapitalisasi LAMA di database yang dipertahankan (baris di
 * file import tidak menimpa nama yang sudah ada) - hanya kolom lain
 * (deskripsi) yang ter-update.
 */
class KategoriImporter extends Importer
{
    protected static ?string $model = Kategori::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Fiksi'),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string'])
                ->example('Novel dan cerita rekaan'),
        ];
    }

    public function resolveRecord(): ?Kategori
    {
        $nama = trim($this->data['nama']);

        return Kategori::query()->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first()
            ?? new Kategori(['nama' => $nama]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Kategori selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/KelasImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Models\Jurusan;
use App\Models\Kelas;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert berdasarkan 'nama' SAJA (dikonfirmasi: nama Kelas unik secara
 * global lintas Jurusan - lihat migration
 * 2026_08_02_000001_add_unique_nama_to_kelas_table). Sebelumnya upsert
 * key adalah kombinasi (nama, jurusan_id) - DIUBAH sesuai konfirmasi ini.
 *
 * 'jurusan_kode' tetap direferensikan via kode unik Jurusan (bukan nama)
 * untuk menghindari ambiguitas nama Jurusan.
 *
 * BUG FIX (ditemukan iterasi ini): 'jurusan_kode' adalah kolom
 * lookup-only (bukan kolom asli tabel 'kelas' - lihat Schema kelas: id,
 * nama, tingkat, jurusan_id, timestamps). Tanpa ->fillRecordUsing(),
 * Filament otomatis meng-assign $record->jurusan_kode = state SEBELUM
 * save(), yang lolos dari validasi Eloquent (properti dinamis tetap
 * dianggap dirty attribute) dan menyebabkan SQLSTATE[42S22] "Unknown
 * column 'jurusan_kode'" saat INSERT/UPDATE - baris gagal total dengan
 * pesan generik "Terjadi kesalahan sistem". fillRecordUsing() no-op
 * memastikan resolusi HANYA lewat resolveRecord() (Aturan poin 3, DRY).
 */
class KelasImporter extends Importer
{
    protected static ?string $model = Kelas::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->label('Nama kelas (mis. X IPA 1)')
                ->helperText('Harus unik secara global - tidak boleh ada 2Kelas dengan nama sama meski beda Jurusan.')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('X IPA 1'),
            ImportColumn::make('tingkat')
                ->helperText('Angka tingkat, mis. 10, 11, 12 - dipakai untuk urutan proses Kenaikan Kelas.')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'min:1'])
                ->example('10'),
            ImportColumn::make('jurusan_kode')
                ->label('Kode Jurusan (opsional)')
                ->helperText('Lihat daftar kode di menu Master Data > Jurusan. Kosongkan jika kelas ini tidak terikat jurusan tertentu.')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('IPA')
                // BUG FIX - lihat docblock class. Kolom ini bukan kolom
                // asli tabel 'kelas', jangan biarkan Filament auto-assign.
                ->fillRecordUsing(fn (?string $state) => null),
        ];
    }

    public function resolveRecord(): ?Kelas
    {
        $record = Kelas::query()->firstOrNew(['nama' => trim($this->data['nama'])]);

        if (! empty($this->data['jurusan_kode'])) {
            $jurusan = Jurusan::query()->where('kode', $this->data['jurusan_kode'])->first();

            if (! $jurusan) {
                throw new RowImportFailedException("Jurusan dengan kode \"{$this->data['jurusan_kode']}\" tidak ditemukan.");
            }

            $record->jurusan_id = $jurusan->id;
        } else {
            // DIKONFIRMASI: kolom jurusan_kode dikosongkan (baik saat
            // create maupun UPDATE Kelas existing) -> jurusan_id
            // di-null-kan/dilepas, BUKAN dibiarkan tidak berubah. Admin
            // yang re-import Kelas untuk update field lain (mis. hanya
            // 'tingkat') WAJIB tetap mengisi jurusan_kode di file jika
            // tidak ingin assignment Jurusan-nya ikut terhapus.
            $record->jurusan_id = null;
        }

        return $record;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Kelas selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/KelasTahunPelajaranImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Enums\RoleUser;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasTahunPelajaran;
use App\Models\TahunPelajaran;
use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert berdasarkan (kelas_id, tahun_pelajaran_id) - sesuai unique
 * index di migration kelas_tahun_pelajarans.
 *
 * PERUBAHAN KONTRAK (dikonfirmasi): kolom 'jurusan_kode' WAJIB diisi -
 * sebelumnya Kelas dicocokkan hanya lewat nama, yang bisa ambigu karena
 * Kelas.nama TIDAK unik secara global (lihat catatan di KelasImporter,
 * dua kelas beda jurusan bisa punya nama sama, mis. "X-1"). Template
 * Excel/CSV lama TANPA kolom ini akan GAGAL divalidasi (required),
 * bukan diproses dengan asumsi keliru - sekolah wajib pakai template
 * baru (tombol "Unduh contoh" di wizard import sudah otomatis
 * memperbarui diri lewat ->example() di bawah).
 *
 * Wali kelas direferensikan via NIP, WAJIB bukan role super_admin.
 *
 * BUG FIX (ditemukan iterasi ini, sama pola dengan KelasImporter): SEMUA
 * kolom di sini (kelas_nama, jurusan_kode, tahun_pelajaran_nama,
 * wali_kelas_nip) adalah lookup-only - tabel 'kelas_tahun_pelajarans'
 * hanya punya kelas_id/tahun_pelajaran_id/wali_kelas_id. Tanpa
 * ->fillRecordUsing() no-op, Filament berisiko meng-assign atribut
 * dinamis ini ke $record dan menyebabkan SQL error "Unknown column"
 * saat save() - lihat detail penuh di docblock KelasImporter.
 */
class KelasTahunPelajaranImporter extends Importer
{
    protected static ?string $model = KelasTahunPelajaran::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('kelas_nama')
                ->label('Nama kelas')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('X-1')
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('jurusan_kode')
                ->label('Kode jurusan')
                ->helperText('Wajib diisi - lihat daftar kode di menu Master Data > Jurusan, supaya kelas dengan nama yang sama di jurusan berbeda tidak tertukar.')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('IPA')
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('tahun_pelajaran_nama')
                ->label('Tahun pelajaran')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('2025/2026')
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('wali_kelas_nip')
                ->label('NIP wali kelas (opsional)')
                ->helperText('Kosongkan jika belum ada wali kelas yang ditunjuk.')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('198501012010011001')
                ->fillRecordUsing(fn (?string $state) => null),
        ];
    }

    public function resolveRecord(): ?KelasTahunPelajaran
    {
        $jurusan = Jurusan::query()->where('kode', trim($this->data['jurusan_kode']))->first();

        if (! $jurusan) {
            throw new RowImportFailedException("Kode jurusan \"{$this->data['jurusan_kode']}\" tidak ditemukan. Cek ejaan atau tambahkan Jurusan-nya dulu di Master Data.");
        }

        $kelas = Kelas::query()
            ->where('nama', trim($this->data['kelas_nama']))
            ->where('jurusan_id', $jurusan->id)
            ->first();

        if (! $kelas) {
            throw new RowImportFailedException("Kelas \"{$this->data['kelas_nama']}\" dengan jurusan \"{$this->data['jurusan_kode']}\" tidak ditemukan. Cek ejaan atau tambahkan Kelas-nya dulu di Master Data.");
        }

        $tahun = TahunPelajaran::query()->where('nama', trim($this->data['tahun_pelajaran_nama']))->first();

        if (! $tahun) {
            throw new RowImportFailedException("Tahun pelajaran \"{$this->data['tahun_pelajaran_nama']}\" tidak ditemukan. Cek ejaan atau tambahkan dulu di Master Data.");
        }

        return KelasTahunPelajaran::query()->firstOrNew([
            'kelas_id' => $kelas->id,
            'tahun_pelajaran_id' => $tahun->id,
        ]);
    }

    protected function afterSave(): void
    {
        if (empty($this->data['wali_kelas_nip'])) {
            return;
        }

        $waliKelas = User::query()->where('nip', trim($this->data['wali_kelas_nip']))->first();

        if (! $waliKelas) {
            throw new RowImportFailedException("NIP wali kelas \"{$this->data['wali_kelas_nip']}\" tidak ditemukan. Pastikan user dengan NIP tersebutsudah terdaftar.");
        }

        if ($waliKelas->role === RoleUser::Admin) {
            throw new RowImportFailedException('User dengan NIP tersebut berperan sebagai admin, tidak bisa dijadikan wali kelas.');
        }

        $this->record->update(['wali_kelas_id' => $waliKelas->id]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Kelas per Tahun Pelajaran selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/LevelBadgeImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Models\LevelBadge;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert case-insensitive berdasarkan 'nama_badge' (dikonfirmasi).
 */
class LevelBadgeImporter extends Importer
{
    protected static ?string $model = LevelBadge::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama_badge')
                ->label('Nama badge')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Kutu Buku'),
            ImportColumn::make('min_point')
                ->label('Point minimal')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer'])
                ->example('0'),
            ImportColumn::make('max_point')
                ->label('Point maksimal (opsional)')
                ->helperText('Kosongkan jika badge ini adalah level tertinggi (tidak ada batas atas).')
                ->numeric()
                ->rules(['nullable', 'integer'])
                ->example('100'),
            ImportColumn::make('urutan')
                ->helperText('Angka lebih kecil ditampilkan lebih dulu.')
                ->numeric()
                ->rules(['nullable', 'integer'])
                ->example('1'),
        ];
    }

    public function resolveRecord(): ?LevelBadge
    {
        $nama = trim($this->data['nama_badge']);

        return LevelBadge::query()->whereRaw('LOWER(nama_badge) = ?', [mb_strtolower($nama)])->first()
            ?? new LevelBadge(['nama_badge' => $nama]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Level Badge selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/PunishmentImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Models\Punishment;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert case-insensitive berdasarkan 'nama' (dikonfirmasi).
 */
class PunishmentImporter extends Importer
{
    protected static ?string $model = Punishment::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Skorsing peminjaman 7 hari'),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string'])
                ->example('Diberikan jika point minus mencapai ambang batas'),
            ImportColumn::make('threshold_point_minus')
                ->label('Ambang batas point minus')
                ->helperText('Isi dengan angka negatif atau 0 (mis. -50).')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'max:0'])
                ->example('-50'),
            ImportColumn::make('durasi_suspend_hari')
                ->label('Durasi suspend (hari, opsional)')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:1'])
                ->example('7'),
            ImportColumn::make('aktif')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->example('1'),
        ];
    }

    public function resolveRecord(): ?Punishment
    {
        $nama = trim($this->data['nama']);

        return Punishment::query()->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first()
            ?? new Punishment(['nama' => $nama]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Punishment selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/RakImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Models\Kategori;
use App\Models\Rak;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert case-insensitive berdasarkan 'nama' (dikonfirmasi) - "Rak A"
 * dan "rak a" dianggap Rak yang sama. Jika sudah ada baris cocok,
 * ejaan/kapitalisasi LAMA di database yang dipertahankan - hanya kolom
 * lain (lokasi, kategori) yang ter-update.
 *
 * BUG FIX (ditemukan iterasi ini, sama pola dengan KelasImporter): kolom
 * 'kategori' lookup-only, lihat docblock BukuImporter/KelasImporter
 * untuk detail penuh.
 */
class RakImporter extends Importer
{
    protected static ?string $model = Rak::class;

    /**
     * @var array<int, string>|null ID Kategori hasil resolve nama di
     *                              beforeSave() - null berarti kolom 'kategori' kosong. Divalidasi
     *                              SEBELUM save() supaya baris dengan nama kategori tidak
     *                              ditemukan GAGAL TOTAL (dikonfirmasi, sama pola dengan
     *                              BukuImporter) - bukan diam-diam melepas kategori yang salah.
     */
    protected ?array $kategoriIdsTerresolve = null;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Rak A'),
            ImportColumn::make('lokasi')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Lantai 1, dekat pintu masuk'),
            ImportColumn::make('kategori')
                ->label('Kategori (nama, pisah titik-koma jika lebih dari satu)')
                ->helperText('Isi persis sesuai nama Kategori yang sudah ada di Master Data > Kategori. Jika salah satu nama tidak ditemukan, seluruhbaris ini akan GAGAL diimpor (tidak sebagian tersimpan).')
                ->rules(['nullable', 'string'])
                ->example('Fiksi;Sains')
                // BUG FIX - lookup-only, lihat docblock class.
                ->fillRecordUsing(fn (?string $state) => null),
        ];
    }

    public function resolveRecord(): ?Rak
    {
        $nama = trim($this->data['nama']);

        return Rak::query()->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first()
            ?? new Rak(['nama' => $nama]);
    }

    protected function beforeSave(): void
    {
        if (! empty($this->data['kategori'])) {
            $namaKategoris = array_values(array_filter(array_map('trim', explode(';', $this->data['kategori']))));
            $kategoris = Kategori::query()->whereIn('nama', $namaKategoris)->get(['id', 'nama']);

            $namaTidakDitemukan = array_diff($namaKategoris, $kategoris->pluck('nama')->all());

            if (! empty($namaTidakDitemukan)) {
                throw new RowImportFailedException('Kategori tidak ditemukan: "'.implode('", "', $namaTidakDitemukan).'". Cek ejaan atau tambahkan Kategori-nya dulu di Master Data > Kategori.');
            }

            $this->kategoriIdsTerresolve = $kategoris->pluck('id')->all();
        }
    }

    protected function afterSave(): void
    {
        if ($this->kategoriIdsTerresolve !== null) {
            $this->record->kategoris()->sync($this->kategoriIdsTerresolve);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Rak selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal- buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/RewardImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Models\Reward;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert case-insensitive berdasarkan 'nama' (dikonfirmasi).
 */
class RewardImporter extends Importer
{
    protected static ?string $model = Reward::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Voucher buku gratis'),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string'])
                ->example('Dapat menukar 1 buku baru dari katalog toko rekanan'),
            ImportColumn::make('threshold_point')
                ->label('Ambang batas point')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer'])
                ->example('500'),
            ImportColumn::make('aktif')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->example('1'),
        ];
    }

    public function resolveRecord(): ?Reward
    {
        $nama = trim($this->data['nama']);

        return Reward::query()->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first()
            ?? new Reward(['nama' => $nama]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Reward selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/TahunPelajaranImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Models\TahunPelajaran;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

// Upsert berdasarkan 'nama' (unique).
class TahunPelajaranImporter extends Importer
{
    protected static ?string $model = TahunPelajaran::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->label('Nama (mis. 2025/2026)')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('2025/2026'),
            ImportColumn::make('tanggal_mulai')
                ->helperText('Gunakan format tanggal YYYY-MM-DD (mis. 2025-07-14) supaya tidak salah baca oleh Excel/Google Sheets di komputer dengan format regional berbeda.')
                ->requiredMapping()
                ->rules(['required', 'date'])
                ->example('2025-07-14'),
            ImportColumn::make('tanggal_selesai')
                ->helperText('Format sama dengan Tanggal Mulai (YYYY-MM-DD), harus sama atau setelah Tanggal Mulai.')
                ->requiredMapping()
                ->rules(['required', 'date', 'after_or_equal:tanggal_mulai'])
                ->example('2026-06-30'),
        ];
    }

    public function resolveRecord(): ?TahunPelajaran
    {
        // 'aktif' SENGAJA tidak diimpor - perubahan Tahun Pelajaran aktif
        // hanya lewat action "Jadikan Aktif" di TahunPelajaranResource,
        // supaya logic "nonaktifkan yang lain" tetap terpusat di satu
        // tempat (Aturan poin 3).
        return TahunPelajaran::query()->firstOrNew(['nama' => $this->data['nama']]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Tahun Pelajaran selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}

```
---

## app/Filament/Imports/UserImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Enums\RoleUser;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasTahunPelajaran;
use App\Models\TahunPelajaran;
use App\Models\User;
use App\Rules\FormatKartuRfid;
use App\Services\KenaikanKelasService;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * TODO: GAP-SPEC - 'role' SENGAJA tidak termasuk kolom import (dikonfirmasi:
 * harus manual lewat form demi keamanan). User baru hasil import otomatis
 * role='siswa' (default migration/kolom).
 *
 * Upsert berdasarkan 'nisn' jika ada, fallback 'nip' - baris tanpa
 * keduanya akan gagal (lihat rules 'required_without').
 *
 * Kolom 'password' (dikonfirmasi masuk ke import, plaintext, di-hash
 * bcrypt otomatis lewat cast 'hashed' di Model User):
 * - Diisi -> password user (baru maupun existing) diganti sesuai isian.
 * - Dikosongkan, user BARU -> tetap auto-generate random 12 karakter
 *   (perilaku lama dipertahankan, TIDAK ada mekanisme kirim WA/email
 *   notifikasi password ke user baru dalam iterasi ini).
 * - Dikosongkan, user EXISTING -> password lama TIDAK diubah sama sekali.
 *
 * PERINGATAN KEAMANAN (dikonfirmasi, RISIKO DITERIMA SADAR - bukan
 * kealpaan): resolveAvatar() di bawah bisa (a) menyalin FILE APA PUN
 * yang bisa dibaca proses PHP di server ke folder publik jika diisi
 * path absolut (risiko path traversal / kebocoran file sensitif mis.
 * .env), dan (b) melakukan HTTP request ke alamat mana pun termasuk
 * jaringan internal jika diisi URL (risiko SSRF). Fitur ini SENGAJA
 * tidak dibatasi karena hanya super_admin yang punya akses Import User
 * (lihat authorize() di UserResource) - JANGAN perluas permission
 * import ini ke role lain tanpa meninjau ulang dua risiko ini.
 *
 * Kolom 'avatar' (dikonfirmasi masuk ke import, menerima URL ATAU
 * path - lihat resolveAvatar()):
 * - Diisi URL (http/https) -> file diunduh lalu disimpan ke disk 'public'
 *   folder 'user-avatar/' (SAMA dengan direktori FileUpload::make('avatar')
 *   di UserResource, Aturan poin 3 - satu sumber kebenaran lokasi file).
 * - Diisi path yang SUDAH ada di disk 'public' (mis. hasil upload manual
 *   sebelumnya) -> dipakai langsung sebagai nilai kolom avatar.
 * - Diisi path absolut yang ada di filesystem server (mis. hasil transfer
 *   file massal oleh admin sebelum import) -> disalin ke disk 'public'
 *   folder 'user-avatar/'.
 * - Tidak ditemukan di ketiga kemungkinan di atas -> baris GAGAL
 *   (RowImportFailedException), bukan diam-diam dilewati.
 * - Dikosongkan -> avatar lama (jika ada) TIDAK diubah.
 * TODO: GAP-SPEC - algoritma resolusi "path" di atas (cek disk 'public'
 * dulu, baru cek filesystem absolut) adalah ASUMSI untuk memudahkan admin
 * pemula (cukup isi nama file atau URL, tidak perlu tahu detail storage
 * disk). Perlu dikonfirmasi apakah ini sudah cukup atau butuh dukungan
 * sumber lain (mis. path relatif ke disk selain 'public').
 *
 * Kolom 'no_kartu_rfid' (dikonfirmasi masuk ke import, sebelumnya
 * sengaja dikeluarkan demi keamanan) - aturan MENGIKAT kontrak firmware
 * Attendance Machine (lihat FormatKartuRfid::class, wajib persis 10
 * digit angka) - dipakai lewat rule yang SAMA dengan form manual
 * (Aturan poin 3, satu sumber kebenaran validasi).
 *
 * Perilaku no_kartu_rfid (dikonfirmasi eksplisit):
 * - Diisi dan berbeda dari kartu user saat ini -> kartu di-assign,
 *   KECUALI nomor tersebut sudah dipakai user LAIN -> baris GAGAL
 *   (RowImportFailedException), user lain tidak diubah sama sekali.
 * - Dikosongkan, padahal user sudah punya kartu terdaftar -> kartu
 *   LAMA DIHAPUS (di-null-kan). User tersebut TIDAK BISA tap RFID lagi
 *   sampai didaftarkan ulang. Jumlah kartu yang terhapus direkap di
 *   notifikasi selesai import supaya tidak terjadi diam-diam.
 *
 * BUG FIX (ditemukan iterasi ini, sama pola dengan KelasImporter):
 * kolom 'kelas_nama', 'jurusan_kode', 'tahun_pelajaran_nama' adalah
 * lookup-only murni (dipakai di resolveKtp(), lalu efeknya lewat
 * KenaikanKelasService::assignKelas() di afterSave() - BUKAN kolom
 * tabel 'users'). 'avatar' juga lookup/transform-only (hasil
 * akhirnya ditulis ke kolom 'avatar', bukan 'avatar' - kolom
 * 'avatar' sendiri tidak ada di tabel users). Keempatnya diberi
 * ->fillRecordUsing() no-op supaya Filament tidak meng-assign atribut
 * dinamis ini ke $record, yang akan memicu SQL error "Unknown column"
 * saat save() - lihat detail penuh di docblock KelasImporter.
 *
 * 'no_kartu_rfid' dan 'password' TIDAK diberi fillRecordUsing() no-op -
 * keduanya kolom ASLI tabel 'users', assignment akhirnya tetap lewat
 * beforeSave()/resolvePassword() (override manual, aman dari bug ini).
 */
class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    protected ?KelasTahunPelajaran $ktpTerresolve = null;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Yahya Zulfikri'),
            ImportColumn::make('nisn')
                ->label('NISN')
                ->helperText('Isi salah satu: NISN (untuk siswa) atau NIP (untuk pegawai/pustakawan).')
                ->rules(['nullable', 'required_without:nip', 'string', 'max:10'])
                ->example('0000971291'),
            ImportColumn::make('nip')
                ->label('NIP')
                ->rules(['nullable', 'required_without:nisn', 'string', 'max:18'])
                ->example(''),
            ImportColumn::make('kelas_nama')
                ->label('Nama kelas (opsional, khusus siswa)')
                ->helperText('Kosongkan jika bukan siswa atau belum mau ditempatkan ke kelas.')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('VII A')
                // BUG FIX - lookup-only, lihat docblock class.
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('jurusan_kode')
                ->label('Kode jurusan (wajib jika kelas_nama diisi)')
                ->helperText('Lihat daftar kode di menu Master Data > Jurusan.')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Non_Jurusan')
                // BUG FIX - lookup-only, lihat docblock class.
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('tahun_pelajaran_nama')
                ->label('Tahun pelajaran (wajib jika kelas_nama diisi)')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('2025/2026')
                // BUG FIX - lookup-only, lihat docblock class.
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('jabatan')
                ->rules(['nullable', 'string', 'max:255'])
                ->example(''),
            ImportColumn::make('no_telepon')
                ->label('No. Telepon')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('081234567890'),
            ImportColumn::make('no_kartu_rfid')
                ->label('No. kartu RFID (opsional)')
                ->helperText('PERHATIAN: kosongkan HANYA jika memang inginmenghapus kartu yang sudah terdaftar untuk user ini - user tidak akan bisatap RFID lagi sampai didaftarkan ulang. Harus persis 10 digit angka.')
                ->rules(['nullable', new FormatKartuRfid])
                ->example('1234567890'),
            ImportColumn::make('password')
                ->label('Password (opsional)')
                ->helperText('Isi plaintext (otomatis di-hash saat disimpan). Kosongkan: user baru tetap dapat password random, user lama password TIDAK berubah.')
                ->rules(['nullable', 'string', 'min:8', 'max:255'])
                ->example(''),
            ImportColumn::make('avatar')
                ->label('Avatar - URL atau path (opsional)')
                ->helperText('Isi URL gambar (https://...) atau path file yang bisa diakses server. Kosongkan jika tidak ingin mengubah avatar.')
                ->rules(['nullable', 'string', 'max:2048'])
                ->example('https://contoh-sekolah.id/foto/siswa1.jpg')
                // BUG FIX - lookup/transform-only, hasil akhir ditulis ke
                // kolom 'avatar' (beda nama) lewat resolveAvatar(), lihat
                // docblock class.
                ->fillRecordUsing(fn (?string $state) => null),
        ];
    }

    public function resolveRecord(): ?User
    {
        $namaKelas = trim((string) ($this->data['kelas_nama'] ?? ''));

        if ($namaKelas !== '') {
            $this->ktpTerresolve = $this->resolveKtp(
                $namaKelas,
                trim((string) ($this->data['jurusan_kode'] ?? '')),
                trim((string) ($this->data['tahun_pelajaran_nama'] ?? '')),
            );
        }

        if (! empty($this->data['nisn'])) {
            $record = User::query()->firstOrNew(['nisn' => $this->data['nisn']]);
        } else {
            $record = User::query()->firstOrNew(['nip' => $this->data['nip']]);
        }

        if (! $record->exists) {
            $record->role = RoleUser::Siswa;
        }

        return $record;
    }

    /**
     * Uniqueness no_kartu_rfid dicek manual (bukan rule 'unique' di
     * getColumns()) karena butuh tahu ID record yang sedang di-upsert
     * dulu (supaya user meng-update kartunya sendiri dengan nilai yang
     * sama tidak dianggap konflik) - baru tersedia setelah resolveRecord().
     */
    protected function beforeSave(): void
    {
        $this->resolvePassword();
        $this->resolveAvatar();

        $nomorBaru = trim((string) ($this->data['no_kartu_rfid'] ?? ''));

        if ($nomorBaru === '') {
            if ($this->record->no_kartu_rfid !== null) {
                $this->record->no_kartu_rfid = null;
                Cache::increment("import-{$this->import->id}-kartu-dihapus");
            }

            return;
        }

        $dipakaiUserLain = User::query()
            ->where('no_kartu_rfid', $nomorBaru)
            ->when($this->record->exists, fn ($q) => $q->whereKeyNot($this->record->id))
            ->exists();

        if ($dipakaiUserLain) {
            throw new RowImportFailedException("Nomor kartu \"{$nomorBaru}\" sudah dipakai user lain. Cek kembali atau kosongkan kolom ini.");
        }

        $this->record->no_kartu_rfid = $nomorBaru;
    }

    /**
     * Password diisi -> dipakai apa adanya (di-hash otomatis via cast
     * 'hashed' saat $record->save()). Kosong & user baru -> random 12
     * karakter (perilaku lama). Kosong & user existing -> tidak disentuh.
     */
    protected function resolvePassword(): void
    {
        $passwordBaru = trim((string) ($this->data['password'] ?? ''));

        if ($passwordBaru !== '') {
            $this->record->password = $passwordBaru; // di-hash otomatis via cast 'hashed'

            return;
        }

        if (! $this->record->exists) {
            $this->record->password = Str::random(12); // di-hash otomatisvia cast 'hashed'
        }
    }

    /**
     * TODO: GAP-SPEC - lihat catatan algoritma resolusi di docblock class.
     * Urutan resolusi: (1) URL http/https -> unduh, (2) sudah ada di disk
     * 'public' -> pakai langsung, (3) path absolut di filesystem server ->
     * salin ke disk 'public'. Kosong -> avatar lama tidak diubah.
     *
     * BUG FIX (ditemukan iterasi ini): nama file SEBELUMNYA selalu
     * Str::uuid() - re-import avatar yang sama terus-menerus menumpuk
     * file baru di disk tanpa pernah menghapus yang lama (kebocoran
     * storage). Diubah jadi nama deterministik berbasis identitas user
     * (NISN, fallback NIP - konsisten dengan kunci upsert di
     * resolveRecord()): re-import MENIMPA file lama dengan nama sama,
     * sama pola dengan upsert barcode di BukuImporter (Aturan poin 3).
     * Ekstensi TETAP mengikuti sumber asli (bukan dipaksa .png) - konversi
     * format gambar butuh library tambahan (GD/Imagick) yang belum
     * diverifikasi terpasang di composer.json, lihat Aturan poin 7/15.
     */
    protected function resolveAvatar(): void
    {
        $nilai = trim((string) ($this->data['avatar'] ?? ''));

        if ($nilai === '') {
            return;
        }

        $namaFile = $this->namaFileAvatar($nilai);

        if (Str::startsWith($nilai, ['http://', 'https://'])) {
            try {
                $response = Http::timeout(15)->get($nilai);
            } catch (\Throwable $e) {
                throw new RowImportFailedException("Gagal mengunduh avatardari URL \"{$nilai}\": {$e->getMessage()}");
            }

            if (! $response->successful()) {
                throw new RowImportFailedException("URL avatar \"{$nilai}\" tidak bisa diakses (HTTP {$response->status()}).");
            }

            Storage::disk('public')->put($namaFile, $response->body());
            $this->record->avatar = $namaFile;

            return;
        }

        if (Storage::disk('public')->exists($nilai)) {
            // Sudah berupa path di disk 'public' - salin/rename ke nama
            // deterministik supaya konsisten dengan dua kasus lain di
            // bawah (bukan dipakai langsung dengan nama aslinya).
            Storage::disk('public')->copy($nilai, $namaFile);
            $this->record->avatar = $namaFile;

            return;
        }

        if (is_file($nilai)) {
            Storage::disk('public')->put($namaFile, file_get_contents($nilai));
            $this->record->avatar = $namaFile;

            return;
        }

        throw new RowImportFailedException("Avatar \"{$nilai}\" tidak ditemukan (bukan URL valid, bukan file di storage, bukan path lokal di server).");
    }

    /**
     * Nama file deterministik: '{nisn_atau_nip}.{ekstensi_sumber}'.
     * NISN diprioritaskan (konsisten dengan resolveRecord()), fallback
     * NIP jika NISN kosong - salah satu dijamin ada karena validasi
     * 'required_without' di getColumns().
     */
    protected function namaFileAvatar(string $sumber): string
    {
        $identitas = trim((string) ($this->data['nisn'] ?? '')) ?: trim((string) ($this->data['nip'] ?? ''));

        $ekstensi = pathinfo(parse_url($sumber, PHP_URL_PATH) ?? $sumber, PATHINFO_EXTENSION) ?: 'jpg';

        return 'user-avatar/'.$identitas.'.'.$ekstensi;
    }

    protected function afterSave(): void
    {
        if ($this->ktpTerresolve) {
            app(KenaikanKelasService::class)->assignKelas($this->record, $this->ktpTerresolve);
        }
    }

    protected function resolveKtp(string $namaKelas, string $kodeJurusan, string $namaTahun): KelasTahunPelajaran
    {
        if ($kodeJurusan === '' || $namaTahun === '') {
            throw new RowImportFailedException('Kelas diisi tapi kolom jurusan_kode atau tahun_pelajaran_nama kosong. Isi ketiganya, atau kosongkan ketiganya jika user ini belum mau ditempatkan ke kelas.');
        }

        $jurusan = Jurusan::query()->where('kode', $kodeJurusan)->first();

        if (! $jurusan) {
            throw new RowImportFailedException("Kode jurusan \"{$kodeJurusan}\" tidak ditemukan. Cek ejaan atau tambahkan Jurusan-nya dulu di Master Data.");
        }

        $kelas = Kelas::query()
            ->where('nama', $namaKelas)
            ->where('jurusan_id', $jurusan->id)
            ->first();

        if (! $kelas) {
            throw new RowImportFailedException("Kelas \"{$namaKelas}\" dengan jurusan \"{$kodeJurusan}\" tidak ditemukan. Cek ejaan atau tambahkan Kelas-nya dulu di Master Data.");
        }

        $tahun = TahunPelajaran::query()->where('nama', $namaTahun)->first();

        if (! $tahun) {
            throw new RowImportFailedException("Tahun pelajaran \"{$namaTahun}\" tidak ditemukan. Cek ejaan atau tambahkan dulu di Master Data.");
        }

        $ktp = KelasTahunPelajaran::query()
            ->where('kelas_id', $kelas->id)
            ->where('tahun_pelajaran_id', $tahun->id)
            ->first();

        if (! $ktp) {
            throw new RowImportFailedException("Kombinasi kelas \"{$namaKelas}\" ({$kodeJurusan}) dan tahun pelajaran \"{$namaTahun}\" belum terdaftar. Import Kelas per Tahun Pelajaran dulu sebelum import User.");
        }

        return $ktp;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import User selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal- buka riwayat import untuk lihat alasannya per baris.';
        }

        $kartuDihapus = (int) Cache::get("import-{$import->id}-kartu-dihapus", 0);

        if ($kartuDihapus > 0) {
            $body .= " PERHATIAN: {$kartuDihapus} kartu RFID dihapus dari user (kolom dikosongkan di file) - user tersebut tidak bisa tap RFID sampaididaftarkan ulang.";
        }

        Cache::forget("import-{$import->id}-kartu-dihapus");

        return $body;
    }
}

```
---

## app/Filament/Pages/Auth/Login.php
```php
<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\LoginOtpService;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

/**
 * TODO: verifikasi signature terhadap versi package yang terpasang
 * (filament/filament ^5.7). Override content() dan getFormActions() di
 * bawah didasarkan pada pembacaan langsung source
 * vendor/filament/filament/src/Auth/Pages/Login.php yang diberikan
 * pengguna - authenticate() override TOTAL (bukan extend logic
 * rate-limit/timebox/multi-factor bawaan) tetap ASUMSI BESAR untuk cabang
 * mode 'otp': rate-limit(5), Timebox, dan multi-factor challenge bawaan
 * SEMUA di-skip untuk mode OTP. Risiko diterima sadar sesuai konfirmasi
 * (setara reset password), tapi WAJIB diuji end-to-end (poin 12).
 *
 * TODO: GAP-SPEC/BUG FIX - sebelumnya authenticate() mode OTP memakai
 * $data['login'] (raw input, bisa NISN/NIP/No. Telepon) langsung sebagai
 * no_telepon ke LoginOtpService::verifikasiUntukLogin() yang mencari
 * berdasarkan no_telepon murni - OTP selalu gagal untuk user yang login
 * pakai NISN/NIP. Diperbaiki dengan menyimpan no_telepon hasil resolve
 * di property $noTeleponOtp saat kirimOtpLogin() berhasil, dipakai ulang
 * di authenticate() - bukan raw input field lagi.
 */
class Login extends BaseLogin
{
    public string $mode = 'password';

    public bool $otpTerkirim = false;

    /**
     * no_telepon ASLI hasil resolve dari input login (NISN/NIP/No.
     * Telepon) - disimpan terpisah dari raw field 'login' karena
     * LoginOtpService bekerja murni berbasis no_telepon (sama seperti
     * pola RequestPasswordReset menyimpan no_telepon di session, disini
     * cukup property Livewire karena same-page cycle).
     */
    public ?string $noTeleponOtp = null;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getLoginFormComponent(),
            TextInput::make('password')
                ->label('Password')
                ->hint(filament()->hasPasswordReset()
                    ? new HtmlString(Blade::render(
                        '<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="-1">Lupa kata sandi?</x-filament::link>'
                    ))
                    : null)
                ->password()
                ->revealable()
                ->required()
                ->visible(fn () => $this->mode === 'password')
                ->dehydrated(fn () => $this->mode === 'password'),
            TextInput::make('otp')
                ->label('Kode OTP')
                ->minLength(6)
                ->maxLength(6)
                ->required()
                ->visible(fn () => $this->mode === 'otp' && $this->otpTerkirim)
                ->dehydrated(fn () => $this->mode === 'otp'),
            Text::make('Kode OTP berlaku 5 menit. Tidak menerima? Klik "Password" lalu "OTP WhatsApp" lagi untuk kirim ulang.')
                ->size('xs')
                ->color('gray')
                ->visible(fn () => $this->mode === 'otp' && $this->otpTerkirim),
            $this->getRememberFormComponent(),
        ]);
    }

    protected function getLoginFormComponent(): TextInput
    {
        return TextInput::make('login')
            ->label('NISN / NIP / No. Telepon')
            ->required()
            ->autofocus()
            ->disabled(fn () => $this->mode === 'otp' && $this->otpTerkirim)
            ->extraInputAttributes(['tabindex' => 1]);
    }

    public function gantiMode(string $mode): void
    {
        $this->mode = $mode;
        $this->otpTerkirim = false;
        $this->noTeleponOtp = null;
    }

    protected function resolveUser(string $login): ?User
    {
        return User::query()
            ->where('nisn', $login)
            ->orWhere('nip', $login)
            ->orWhere('no_telepon', $login)
            ->first();
    }

    public function kirimOtpLogin(): void
    {
        $login = (string) ($this->form->getState()['login'] ?? '');
        $user = $this->resolveUser($login);

        if (! $user) {
            Notification::make()
                ->title('Akun tidak ditemukan')
                ->body('Pastikan NISN, NIP, atau No. Telepon sesuai yang terdaftar.')
                ->warning()
                ->send();

            return;
        }

        try {
            app(LoginOtpService::class)->kirimOtp($user);
        } catch (\RuntimeException $e) {
            Notification::make()->title('Belum bisa mengirim OTP')->body($e->getMessage())->warning()->send();

            return;
        }

        // simpan no_telepon ASLI hasil resolve - bukan raw input 'login',
        // supaya verifikasi OTP di authenticate() tetap benar walau user
        // login pakai NISN/NIP.
        $this->noTeleponOtp = $user->no_telepon;
        $this->otpTerkirim = true;

        Notification::make()->title('Kode OTP terkirim ke WhatsApp terdaftar.')->success()->send();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login = $data['login'];

        $field = match (true) {
            User::query()->where('nisn', $login)->exists() => 'nisn',
            User::query()->where('nip', $login)->exists() => 'nip',
            default => 'no_telepon',
        };

        return [
            $field => $login,
            'password' => $data['password'],
        ];
    }

    /**
     * TODO: GAP-SPEC - livewireSubmitHandler pada <form> di-hardcode ke
     * 'authenticate' oleh base class Filament\Auth\Pages\Login
     * (getFormContentComponent(), tidak di-override disini) - artinya
     * menekan Enter di field manapun dalam form SELALU memanggil
     * authenticate(), terlepas tombol mana yang visible. Di mode 'otp'
     * sebelum OTP terkirim, ini didelegasikan ke kirimOtpLogin() supaya
     * Enter berperilaku sama seperti klik tombol "Kirim OTP" - BUKAN
     * menampilkan galat "kirim OTP terlebih dahulu" (UX buruk, bukan bug
     * keamanan, karena kirimOtpLogin() sendiri sudah divalidasi/rate-limited).
     */
    public function authenticate(): ?LoginResponse
    {
        if ($this->mode === 'password') {
            return parent::authenticate();
        }

        if ($this->mode === 'otp' && ! $this->otpTerkirim) {
            $this->kirimOtpLogin();

            return null;
        }

        $data = $this->form->getState();

        if (! $this->noTeleponOtp) {
            // defensif: seharusnya tidak tercapai lagi via Enter (sudah
            // ditangani cabang di atas), tapi dipertahankan untuk kasus
            // race/edge lain (mis. otpTerkirim true tapi noTeleponOtp
            // ter-reset oleh sebab tak terduga).
            Notification::make()->title('Kirim OTP terlebih dahulu.')->warning()->send();

            return null;
        }

        try {
            $user = app(LoginOtpService::class)->verifikasiUntukLogin($this->noTeleponOtp, (string) $data['otp']);
        } catch (\RuntimeException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return null;
        }

        Auth::login($user, $data['remember'] ?? false);
        session()->regenerate();

        return app(LoginResponse::class);
    }

    /**
     * Override TOTAL dari content() bawaan - menyisipkan baris toggle mode
     * (Actions link) SEBELUM form, memakai Schema Component API asli
     * (bukan Blade custom) supaya konsisten dengan cara Filament merender
     * halaman ini. Struktur RenderHook before/after dipertahankan sama
     * seperti base class.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
            $this->getModeSwitcherComponent(),
            $this->getFormContentComponent(),
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
        ]);
    }

    protected function getModeSwitcherComponent(): Component
    {
        return Actions::make([
            Action::make('mode_password')
                ->label('Password')
                ->action(fn () => $this->gantiMode('password'))
                ->extraAttributes([
                    'class' => 'flex-1 !justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-colors '
                        .($this->mode === 'password'
                            ? 'bg-white text-gray-950 dark:bg-gray-700 dark:text-white'
                            : 'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'),
                ])
                ->color('gray'),
            Action::make('mode_otp')
                ->label('OTP WhatsApp')
                ->action(fn () => $this->gantiMode('otp'))
                ->extraAttributes([
                    'class' => 'flex-1 !justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-colors '
                        .($this->mode === 'otp'
                            ? 'bg-white text-gray-950 dark:bg-gray-700 dark:text-white'
                            : 'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'),
                ])
                ->color('gray'),
        ])
            ->fullWidth()
            ->extraAttributes([
                'class' => 'mb-6 gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800/60',
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('kirim_otp')
                ->label('Kirim OTP')
                ->action('kirimOtpLogin')
                ->visible(fn () => $this->mode === 'otp' && ! $this->otpTerkirim),
            Action::make('authenticate')
                ->label(fn () => $this->mode === 'otp' ? 'Verifikasi & Masuk' : 'Masuk')
                ->submit('authenticate')
                ->visible(fn () => $this->mode === 'password' || $this->otpTerkirim),
        ];
    }
}

```
---

## app/Filament/Pages/Auth/RequestPasswordReset.php
```php
<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\PasswordResetOtpService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

/**
 * Langkah 1: minta identifier (NISN/NIP/No. Telepon - sama seperti Login,
 * lihat App\Filament\Pages\Auth\Login), kirim OTP via WhatsApp ke
 * User.no_telepon, lalu redirect ke ResetPassword page. no_telepon ASLI
 * (bukan raw input) disimpan di session, supaya PasswordResetOtpService
 * (yang bekerja berbasis no_telepon) tetap konsisten walau user login
 * pakai NISN/NIP.
 */
class RequestPasswordReset extends SimplePage
{
    protected string $view = 'filament.pages.auth.request-password-reset';

    public ?array $data = [];

    public function getHeading(): string|HtmlString
    {
        return 'Lupa Kata Sandi';
    }

    public function getSubheading(): string|HtmlString|null
    {
        return 'Masukkan NISN, NIP, atau No. Telepon yang terdaftar - kode OTP akan dikirim via WhatsApp.';
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('login')
                ->label('NISN / NIP / No. Telepon')
                ->required(),
        ])->statePath('data');
    }

    /**
     * Resolusi identifier ke User - pola SAMA persis dengan
     * Login::getCredentialsFromFormData() (Aturan poin 3, DRY). Jika suatu
     * saat logic resolusi berubah (mis. tambah identifier baru), kedua
     * tempat ini wajib disinkronkan bersamaan (poin 11).
     */
    protected function resolveUser(string $login): ?User
    {
        return User::query()
            ->where('nisn', $login)
            ->orWhere('nip', $login)
            ->orWhere('no_telepon', $login)
            ->first();
    }

    public function kirim(PasswordResetOtpService $otpService): void
    {
        $data = $this->form->getState();

        $user = $this->resolveUser($data['login']);

        if (! $user) {
            // TODO: GAP-SPEC - notifikasi eksplisit "tidak terdaftar" atas
            // permintaan (trade-off: bisa dipakai enumerasi identifier
            // terdaftar). Lihat catatan sebelumnya jika ingin direvert ke
            // pesan generik.
            Notification::make()
                ->title('Akun tidak ditemukan')
                ->body('Pastikan NISN, NIP, atau No. Telepon yang dimasukkan sesuai dengan yang terdaftar di sistem perpustakaan.')
                ->warning()
                ->send();

            return;
        }

        try {
            $otpService->kirimOtp($user);
        } catch (\RuntimeException $e) {
            // rate limit OTP (lihat PasswordResetOtpService::kirimOtp) -
            // ditangkap disini, bukan dibiarkan jadi fatal error.
            Notification::make()
                ->title('Belum bisa mengirim OTP')
                ->body($e->getMessage())
                ->warning()
                ->send();

            return;
        }

        Session::put('reset_password_no_telepon', $user->no_telepon);

        $this->redirect(
            URL::signedRoute('filament.dashboard.auth.password-reset.reset')
        );
    }
}

```
---

## app/Filament/Pages/Auth/ResetPassword.php
```php
<?php

namespace App\Filament\Pages\Auth;

use App\Services\PasswordResetOtpService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\HtmlString;

/**
 * Langkah 2: user masukkan OTP yang diterima di WhatsApp + password baru.
 * no_telepon diambil dari session yang di-set RequestPasswordReset - jika
 * session kosong (mis. buka langsung URL ini tanpa lewat step 1), tendang
 * balik ke RequestPasswordReset.
 */
class ResetPassword extends SimplePage
{
    protected string $view = 'filament.pages.auth.reset-password';

    public ?array $data = [];

    public function getHeading(): string|HtmlString
    {
        return 'Reset Kata Sandi';
    }

    public function getSubheading(): string|HtmlString|null
    {
        return 'Masukkan kode OTP yang dikirim ke WhatsApp-mu.';
    }

    public function mount(): void
    {
        if (! Session::has('reset_password_no_telepon')) {
            $this->redirect(route('filament.dashboard.auth.password-reset.request'));

            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('otp')
                ->label('Kode OTP')
                ->required()
                ->minLength(6)
                ->maxLength(6),
            TextInput::make('password')
                ->label('Password Baru')
                ->password()
                ->required()
                ->minLength(8),
            TextInput::make('password_confirmation')
                ->label('Konfirmasi Password Baru')
                ->password()
                ->required()
                ->same('password'),
        ])->statePath('data');
    }

    public function prosesReset(PasswordResetOtpService $otpService): void
    {
        $data = $this->form->getState();
        $noTelepon = Session::get('reset_password_no_telepon');

        try {
            $otpService->verifikasiDanReset($noTelepon, $data['otp'], $data['password']);
        } catch (\RuntimeException $e) {
            Notification::make()->title($e->getMessage())->warning()->send();

            return;
        }

        Session::forget('reset_password_no_telepon');

        Notification::make()->title('Password berhasil direset, silakan login.')->success()->send();

        $this->redirect(route('filament.dashboard.auth.login'));
    }
}

```
---

## app/Filament/Pages/Dashboard.php
```php
<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int|array
    {
        return 3;
    }
}

```
---

## app/Filament/Pages/LaporanBulanan.php
```php
<?php

namespace App\Filament\Pages;

use App\Services\LaporanBulananService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LaporanBulanan extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Bulanan';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected string $view = 'filament.pages.laporan-bulanan';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:LaporanBulanan') ?? false;
    }

    public function getHeading(): string|HtmlString
    {
        return 'Laporan Bulanan';
    }

    public function mount(): void
    {
        $this->form->fill([
            'bulan' => (int) now()->format('n'),
            'tahun' => (int) now()->format('Y'),
        ]);
    }

    // TODO: verifikasi signature Section/Grid terhadap versi filament/filament
    // di composer.lock - keduanya diasumsikan tersedia di
    // Filament\Schemas\Components sejalan dengan Schema yang sudah dipakai
    // project ini (Filament v5.7), belum pernah dipakai di file lain project
    // untuk dikonfirmasi persis.
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pilih Periode')
                ->description('Laporan mencakup Peminjaman, Pengembalian, Denda, Kunjungan, Point, serta riwayat Badge/Reward/Punishment pada bulan yang dipilih.')
                ->icon('heroicon-o-calendar-days')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('bulan')
                                ->label('Bulan')
                                ->native(false)
                                ->options([
                                    1 => 'Januari',
                                    2 => 'Februari',
                                    3 => 'Maret',
                                    4 => 'April',
                                    5 => 'Mei',
                                    6 => 'Juni',
                                    7 => 'Juli',
                                    8 => 'Agustus',
                                    9 => 'September',
                                    10 => 'Oktober',
                                    11 => 'November',
                                    12 => 'Desember',
                                ])
                                ->required(),
                            Select::make('tahun')
                                ->label('Tahun')
                                ->native(false)
                                ->options(
                                    collect(range((int) now()->format('Y'), 2024))
                                        ->mapWithKeys(fn ($y) => [$y => $y])
                                )
                                ->required(),
                        ]),
                ]),
        ])->statePath('data');
    }

    public function generate(LaporanBulananService $service): mixed
    {
        $data = $this->form->getState();

        $laporan = $service->generate((int) $data['bulan'], (int) $data['tahun']);

        $pdf = Pdf::loadView('pdf.laporan-bulanan', $laporan)
            ->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            "laporan-bulanan-{$data['tahun']}-{$data['bulan']}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }
}

```
---

## app/Filament/Pages/PengaturanSistem.php
```php
<?php

namespace App\Filament\Pages;

use App\Enums\GroupSetting;
use App\Models\Setting;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Halaman Pengaturan Sistem: form terstruktur per GroupSetting, menulis
 * ke tabel `settings` (bukan generate Resource generik). Konsisten
 * dengan pola LaporanBulanan (custom Page + Schema->statePath('data')).
 *
 * Simpan dipisah 2 method (simpanUmum / simpanDevice) - BUKAN satu
 * Action generik - supaya konfirmasi dialog hanya wajib untuk grup
 * Device (poin 17: perubahan di sini menyentuh device fisik yang
 * sudah aktif di lapangan). Dialog konfirmasi di-trigger via Alpine
 * confirm() di Blade, bukan Filament Action::requiresConfirmation(),
 * karena signature Action pada Filament 5.7 belum diverifikasi penuh
 * untuk kasus non-Resource page ini.
 *
 * TODO: verifikasi signature terhadap versi package yang terpasang -
 * komponen Tabs/Tab/Grid diasumsikan berada di Filament\Schemas\Components
 * (mengikuti pola Schema/Select yang sudah dipakai LaporanBulanan),
 * cek ulang jika filament/filament ^5.7 punya lokasi berbeda.
 *
 * TODO: GAP-SPEC/UI - setiap Tab dibungkus Grid responsif (default 1
 * kolom mobile, 2 kolom tablet, 3 kolom desktop, 4 kolom layar lebar)
 * agar field pendek (angka jam, point, tarif) tidak memanjang penuh
 * satu baris - proporsional terhadap panjang label/nilai. Tab
 * "WhatsApp Template" tetap dibatasi maksimal 3 kolom (bukan 4) karena
 * label & helperText lebih panjang, 4 kolom akan membuat teks terpotong/
 * wrap berlebihan.
 */
class PengaturanSistem extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Sistem';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected string $view = 'filament.pages.pengaturan-sistem';

    public ?array $data = [];

    /**
     * Key yang HANYA dikelola otomatis oleh sistem (UserObserver) -
     * tidak boleh masuk fillable form, ditampilkan read-only saja.
     */
    protected const KEY_READONLY = ['rfid_db_ver'];

    /**
     * Key milik grup Device yang wajib lewat konfirmasi terpisah
     * karena berdampak langsung ke device fisik aktif.
     */
    protected const KEY_DEVICE_SENSITIVE = [
        'device_sleep_start_hour',
        'device_sleep_end_hour',
        'device_oled_dim_start_hour',
        'device_oled_dim_end_hour',
        'device_sync_interval_ms',
        'device_ota_check_interval_ms',
    ];

    /**
     * Kolom grid responsif standar untuk tab dengan field pendek
     * (Peminjaman/Denda, Point, Device) - proporsional di layar lebar.
     */
    protected const GRID_KOLOM_STANDAR = [
        'default' => 1,
        'sm' => 2,
        'lg' => 3,
        'xl' => 4,
    ];

    /**
     * Kolom grid untuk tab dengan label/helperText panjang (WhatsApp
     * Template) - dibatasi 3 kolom maksimal supaya tidak wrap berlebihan.
     */
    protected const GRID_KOLOM_PADAT = [
        'default' => 1,
        'sm' => 2,
        'lg' => 3,
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:PengaturanSistem') ?? false;
    }

    public function getHeading(): string|HtmlString
    {
        return 'Pengaturan Sistem';
    }

    public function mount(): void
    {
        $values = Setting::query()->pluck('value', 'key');

        $this->form->fill($values->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Grup')
                ->tabs([
                    Tab::make('Peminjaman & Denda')
                        ->schema([
                            Grid::make(self::GRID_KOLOM_STANDAR)
                                ->schema([
                                    TextInput::make('max_peminjaman_aktif')
                                        ->label('Maks. Peminjaman Aktif per User')
                                        ->numeric()->integer()->minValue(1)->required(),
                                    TextInput::make('lama_peminjaman_hari')
                                        ->label('Lama Masa Pinjam (hari)')
                                        ->numeric()->integer()->minValue(1)->required(),
                                    TextInput::make('tarif_denda_per_hari')
                                        ->label('Tarif Denda Keterlambatan / Hari (Rp)')
                                        ->numeric()->minValue(0)->required(),
                                    TextInput::make('persentase_denda_kerusakan')
                                        ->label('Persentase Denda Kerusakan (%)')
                                        ->numeric()->minValue(0)->maxValue(100)->required(),
                                ]),
                        ]),

                    Tab::make('Point')
                        ->schema([
                            Grid::make(self::GRID_KOLOM_STANDAR)
                                ->schema([
                                    TextInput::make('point_kunjungan')->label('Point Kunjungan')->numeric()->integer()->required(),
                                    TextInput::make('point_peminjaman')->label('Point Peminjaman')->numeric()->integer()->required(),
                                    TextInput::make('point_pengembalian')->label('Point Pengembalian')->numeric()->integer()->required(),
                                    TextInput::make('point_kerusakan')->label('Point Kerusakan (negatif)')->numeric()->integer()->maxValue(0)->required(),
                                    TextInput::make('point_kehilangan')->label('Point Kehilangan (negatif)')->numeric()->integer()->maxValue(0)->required(),
                                ]),
                        ]),

                    Tab::make('WhatsApp Template')
                        ->schema([
                            Grid::make(self::GRID_KOLOM_PADAT)
                                ->schema(
                                    collect([
                                        'wa_template_peminjaman_aktif' => 'Peminjaman Aktif',
                                        'wa_template_reminder_h3' => 'Reminder H-3',
                                        'wa_template_reminder_h1' => 'Reminder H-1',
                                        'wa_template_jadi_terlambat' => 'Jadi Terlambat',
                                        'wa_template_pengembalian_diproses' => 'Pengembalian Diproses',
                                        'wa_template_denda_dibuat' => 'Denda Dibuat',
                                        'wa_template_denda_lunas' => 'Denda Lunas',
                                        'wa_template_badge_naik' => 'Badge Naik',
                                        'wa_template_reward_didapat' => 'Reward Didapat',
                                        'wa_template_punishment_diterapkan' => 'Punishment Diterapkan',
                                        'wa_template_reset_password_otp' => 'Reset Password OTP',
                                        'wa_template_login_otp' => 'Login OTP',
                                        'wa_template_koreksi_kondisi_pengembalian' => 'Koreksi Kondisi Pengembalian',
                                        'wa_template_denda_dibatalkan_perlu_refund' => 'Denda Dibatalkan (Perlu Refund)',
                                    ])->map(
                                        fn (string $label, string $key) => TextInput::make($key)
                                            ->label($label)
                                            ->required()
                                            ->helperText('Wajib sama persis dengan template_code di panel gateway.')
                                    )->values()->all()
                                ),
                        ]),

                    Tab::make('Device')
                        ->schema([
                            Placeholder::make('rfid_db_ver')
                                ->label('Versi Daftar Kartu RFID (otomatis)')
                                ->content(fn () => (string) Setting::get('rfid_db_ver', 0))
                                ->columnSpanFull(),
                            Grid::make(self::GRID_KOLOM_STANDAR)
                                ->schema([
                                    TextInput::make('device_sleep_start_hour')
                                        ->label('Jam Mulai Sleep (0-23)')
                                        ->numeric()->integer()->minValue(0)->maxValue(23)->required(),
                                    TextInput::make('device_sleep_end_hour')
                                        ->label('Jam Bangun dari Sleep (0-23)')
                                        ->numeric()->integer()->minValue(0)->maxValue(23)->required(),
                                    TextInput::make('device_oled_dim_start_hour')
                                        ->label('Jam OLED Dimatikan Sementara (0-23)')
                                        ->numeric()->integer()->minValue(0)->maxValue(23)->required(),
                                    TextInput::make('device_oled_dim_end_hour')
                                        ->label('Jam OLED Menyala Kembali (0-23)')
                                        ->numeric()->integer()->minValue(0)->maxValue(23)->required(),
                                    TextInput::make('device_sync_interval_ms')
                                        ->label('Interval Sinkronisasi (ms)')
                                        ->numeric()->integer()->minValue(1000)->required()
                                        ->helperText('Minimum 1000ms - nilai terlalu kecil membebani device & jaringan.'),
                                    TextInput::make('device_ota_check_interval_ms')
                                        ->label('Interval Cek Firmware OTA (ms)')
                                        ->numeric()->integer()->minValue(1000)->required(),
                                ]),
                        ]),
                ]),
        ])->statePath('data');
    }

    /**
     * Simpan grup Peminjaman, Denda, Point, WhatsApp - tanpa konfirmasi,
     * tidak menyentuh kontrak device.
     */
    public function simpanUmum(): void
    {
        $state = $this->form->getState();

        $keys = array_diff(
            array_keys($state),
            self::KEY_DEVICE_SENSITIVE,
            self::KEY_READONLY,
        );

        $this->simpanKeys($state, $keys);

        Notification::make()->success()->title('Pengaturan umum disimpan.')->send();
    }

    /**
     * Simpan grup Device - dipanggil setelah konfirmasi Alpine di Blade.
     * TODO: GAP-SPEC - perubahan di sini baru dipakai device pada siklus
     * fetch config berikutnya (lihat PerpustakaanDeviceController), TIDAK
     * ada mekanisme push aktif ke device yang sedang online. Jika perlu
     * push real-time, perlu keputusan desain tambahan (mis. queue command
     * ke device via endpoint lain) - belum diimplementasikan.
     */
    public function simpanDevice(): void
    {
        $state = $this->form->getState();

        $this->simpanKeys($state, self::KEY_DEVICE_SENSITIVE);

        Notification::make()
            ->warning()
            ->title('Pengaturan device disimpan.')
            ->body('Device akan memakai nilai baru pada sinkronisasi berikutnya, bukan seketika.')
            ->send();
    }

    protected function simpanKeys(array $state, array $keys): void
    {
        $groupMap = [
            'max_peminjaman_aktif' => GroupSetting::Peminjaman,
            'lama_peminjaman_hari' => GroupSetting::Peminjaman,
            'tarif_denda_per_hari' => GroupSetting::Denda,
            'persentase_denda_kerusakan' => GroupSetting::Denda,
            'point_kunjungan' => GroupSetting::Point,
            'point_peminjaman' => GroupSetting::Point,
            'point_pengembalian' => GroupSetting::Point,
            'point_kerusakan' => GroupSetting::Point,
            'point_kehilangan' => GroupSetting::Point,
            'device_sleep_start_hour' => GroupSetting::Device,
            'device_sleep_end_hour' => GroupSetting::Device,
            'device_oled_dim_start_hour' => GroupSetting::Device,
            'device_oled_dim_end_hour' => GroupSetting::Device,
            'device_sync_interval_ms' => GroupSetting::Device,
            'device_ota_check_interval_ms' => GroupSetting::Device,
        ];

        foreach ($keys as $key) {
            if (in_array($key, self::KEY_READONLY, true) || ! array_key_exists($key, $state)) {
                continue;
            }

            $group = $groupMap[$key] ?? GroupSetting::Whatsapp; // sisanya wa_template_*

            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $state[$key], 'group' => $group],
            );
        }
    }
}

```
---

## app/Filament/Pages/ProsesKenaikanKelas.php
```php
<?php

namespace App\Filament\Pages;

use App\Enums\StatusRiwayatKelas;
use App\Filament\Resources\KelasTahunPelajaranResource;
use App\Models\KelasTahunPelajaran;
use App\Services\KenaikanKelasService;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use RuntimeException;

/**
 * Halaman kerja untuk memutuskan status kenaikan tiap siswa aktif di
 * satu KTP asal, lalu memanggil KenaikanKelasService::prosesKenaikan()
 * sekaligus (Aturan poin 3, DRY - tidak ada logic kalkulasi di sini).
 * Diakses lewat Action 'proses_kenaikan' di KelasTahunPelajaranResource.
 *
 * Sengaja TIDAK didaftarkan ke navigasi (excludeFromNavigation) - hanya
 * dapat diakses via URL dengan parameter route {ktp} dari Resource
 * (bukan query string - lihat $slug di bawah, wajib match dengan
 * ProsesKenaikanKelas::getUrl(['ktp' => ...]) di KelasTahunPelajaranResource).
 */
class ProsesKenaikanKelas extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.proses-kenaikan-kelas';

    // Wajib ada {ktp} di sini agar getUrl(['ktp' => ...]) menghasilkan
    // path parameter (bukan dibuang), dan Livewire bisa bind ke
    // mount(string $ktp). Tanpa ini -> BindingResolutionException.
    protected static ?string $slug = 'proses-kenaikan-kelas/{ktp}';

    public ?KelasTahunPelajaran $ktp = null;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function mount(string $ktp): void
    {
        $this->ktp = KelasTahunPelajaran::query()
            ->with(['kelas', 'tahunPelajaran', 'siswaAktif'])
            ->findOrFail($ktp);

        $this->form->fill(
            $this->ktp->siswaAktif->mapWithKeys(fn ($siswa) => [
                $siswa->id => StatusRiwayatKelas::Naik->value,
            ])->toArray()
        );
    }

    public function getHeading(): string
    {
        return "Proses Kenaikan Kelas: {$this->ktp->kelas->nama} ({$this->ktp->tahunPelajaran->nama})";
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(
            $this->ktp->siswaAktif->map(
                fn ($siswa) => Select::make((string) $siswa->id)
                    ->label($siswa->nama.' ('.($siswa->nisn ?? '-').')')
                    ->options([
                        StatusRiwayatKelas::Naik->value => 'Naik Kelas',
                        StatusRiwayatKelas::Tinggal->value => 'Tinggal Kelas',
                        StatusRiwayatKelas::Lulus->value => 'Lulus',
                        StatusRiwayatKelas::Keluar->value => 'Keluar',
                    ])
                    ->required()
            )->all()
        )->statePath('data');
    }

    public function proses(): void
    {
        $keputusan = $this->form->getState();

        try {
            $gagal = app(KenaikanKelasService::class)->prosesKenaikan($this->ktp, $keputusan);
        } catch (RuntimeException $e) {
            Notification::make()->danger()->title('Gagal memproses kenaikan kelas')->body($e->getMessage())->send();

            return;
        }

        if (empty($gagal)) {
            Notification::make()->success()->title('Kenaikan kelas berhasil diproses untuk semua siswa.')->send();
        } else {
            Notification::make()
                ->warning()
                ->title('Sebagian siswa gagal diproses')
                ->body(collect($gagal)->map(fn ($pesan, $nama) => "{$nama}:{$pesan}")->implode('; '))
                ->send();
        }

        $this->redirect(KelasTahunPelajaranResource::getUrl());
    }
}

```
---

## app/Filament/Pages/TransaksiCepat.php
```php
<?php

namespace App\Filament\Pages;

use App\Enums\KondisiBuku;
use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Models\Buku;
use App\Models\Eksemplar;
use App\Models\Peminjaman;
use App\Models\User;
use App\Services\PeminjamanService;
use App\Services\RfidResolverService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Halaman transaksi cepat: scan kartu (identifikasi user) -> scan barcode
 * EKSEMPLAR atau ISBN buku satu per satu -> sistem OTOMATIS deteksi
 * pinjam/kembali per eksemplar, diproses langsung tiap scan (TIDAK
 * dikumpulkan dulu, sesuai keputusan QA). Seluruh logic bisnis (limit,
 * stok, Denda, Point, WA) tetap lewat PeminjamanService - halaman ini
 * murni orkestrasi UI (Aturan poin 3).
 *
 * FITUR BARU (iterasi ini): sebelumnya input hanya dicocokkan ke
 * Eksemplar.barcode. Sekarang jika tidak match barcode eksemplar manapun,
 * input dicoba resolve sebagai Buku.isbn (lihat resolveEksemplarDariIsbn())
 * - karena satu ISBN bisa punya banyak Eksemplar/copy fisik, sistem
 * otomatis memilih eksemplar yang relevan (lihat TODO: GAP-SPEC di
 * method tersebut untuk aturan pemilihannya). Property/method di-rename
 * dari $barcodeInput/scanBarcode() menjadi $kodeInput/scanKode() karena
 * sekarang menerima barcode ATAU ISBN, ditelusuri ke seluruh pemakaian
 * termasuk blade (Aturan poin 11).
 *
 * BUG FIX (iterasi sebelumnya): scan barcode sudah dipindah dari query
 * Buku.barcode/Peminjaman.buku_id (sudah tidak ada sejak migration
 * 2026_08_02_000002-000004) ke Eksemplar.barcode/Peminjaman.eksemplar_id.
 *
 * Reader RFID di komputer = USB keyboard-wedge (ketik ke input fokus,
 * seperti barcode scanner), BUKAN endpoint device Attendance Machine -
 * jangan disamakan dengan PerpustakaanDeviceController.
 *
 * Otorisasi: reuse Policy existing, tidak ada permission baru untuk
 * halaman ini sendiri - akses digerbang oleh Create:Peminjaman.
 *
 * Rate limit anti-scan-ganda: eksemplar yang sama (setelah diresolve dari
 * barcode ATAU ISBN) untuk user aktif yang sama tidak boleh diproses ulang
 * dalam window RATE_LIMIT_DETIK detik.
 *
 * TODO: GAP-SPEC - window rate limit di-key per (user_id, eksemplar_id),
 * BUKAN global per eksemplar - asumsi: 2 user berbeda scan eksemplar yang
 * sama beruntun (mis. serah terima cepat) tetap valid, hanya user yang
 * SAMA scan eksemplar yang SAMA berulang yang di-block. Jika ternyata
 * yang diinginkan adalah block global per eksemplar (siapapun
 * operatornya), sesuaikan cache key di bawah (buang bagian user->id).
 *
 * TODO: verifikasi signature terhadap versi package yang terpasang
 * (filament/filament versi sesuai composer.json) - properti $view dan
 * $navigationIcon di bawah mengikuti API Filament v4/v5 (schema-based),
 * cek ulang jika versi terpasang berbeda.
 */
class TransaksiCepat extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Transaksi Cepat';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected string $view = 'filament.pages.transaksi-cepat';

    /**
     * Window rate limit anti-scan-ganda (detik). Lihat catatan class di atas.
     */
    protected const RATE_LIMIT_DETIK = 60;

    public ?string $kartuInput = '';

    public ?string $kodeInput = '';

    public ?User $user = null;

    public bool $bisaMeminjam = false;

    /**
     * @var array<int, array{barcode: string, judul: string, aksi: string,pesan: string, sukses: bool}>
     */
    public array $riwayatScan = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', Peminjaman::class) ?? false;
    }

    public function scanKartu(): void
    {
        $input = trim((string) $this->kartuInput);
        $this->kartuInput = '';

        if ($input === '') {
            return;
        }

        try {
            $this->user = app(RfidResolverService::class)->resolveUser($input);
        } catch (RuntimeException $e) {
            Notification::make()->danger()->title('User tidak ditemukan')->body($e->getMessage())->send();

            return;
        }

        $this->riwayatScan = [];
        $this->bisaMeminjam = app(PeminjamanService::class)->bisaMeminjam($this->user);

        if ($this->user->status_suspend) {
            Notification::make()
                ->warning()
                ->title('User sedang suspend')
                ->body('User masih bisa mengembalikan buku, tapi tidak bisa meminjam baru sampai Denda lunas.')
                ->send();
        }
    }

    public function scanKode(): void
    {
        $kode = trim((string) $this->kodeInput);
        $this->kodeInput = '';

        if ($kode === '' || ! $this->user) {
            return;
        }

        $eksemplar = Eksemplar::query()->where('barcode', $kode)->with('buku')->first();

        if (! $eksemplar) {
            $eksemplar = $this->resolveEksemplarDariIsbn($kode);
        }

        if (! $eksemplar) {
            $this->tambahRiwayat($kode, '-', 'error', 'Barcode/ISBN tidak ditemukan.', false);

            return;
        }

        // Rate limit anti-scan-ganda - dicek SEBELUM logic pinjam/kembali,
        // supaya eksemplar yang sama (baik diresolve dari barcode maupun
        // ISBN) ter-scan 2x dalam window tidak memicu toggle
        // pinjam->kembali->pinjam yang tidak diinginkan.
        $rateLimitKey = "transaksi-cepat-scan:{$this->user->id}:{$eksemplar->id}";

        if (Cache::has($rateLimitKey)) {
            $this->tambahRiwayat(
                $eksemplar->barcode,
                $eksemplar->buku->judul,
                'ditolak',
                'Eksemplar ini baru saja diproses untuk user ini, tunggu '.self::RATE_LIMIT_DETIK.' detik sebelum scan ulang.',
                false,
            );

            return;
        }

        Cache::put($rateLimitKey, true, self::RATE_LIMIT_DETIK);

        // Deteksi otomatis: ada Peminjaman aktif/terlambat milik user ini
        // untuk eksemplar ini -> kembalikan. Kalau tidak -> pinjam baru.
        $peminjamanAktif = Peminjaman::query()
            ->where('eksemplar_id', $eksemplar->id)
            ->where('user_id', $this->user->id)
            ->whereIn('status', [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat])
            ->first();

        $service = app(PeminjamanService::class);

        try {
            if ($peminjamanAktif) {
                $service->prosesPengembalian(
                    peminjaman: $peminjamanAktif,
                    kondisi: KondisiBuku::Baik, // default, koreksi manual lewat PengembalianResource jika perlu
                    diprosesOleh: auth()->user(),
                );
                $this->tambahRiwayat($eksemplar->barcode, $eksemplar->buku->judul, 'dikembalikan', 'Berhasil dikembalikan (kondisi: baik).', true);
            } else {
                if ($eksemplar->status !== StatusEksemplar::Tersedia) {
                    // UX: pesan dipertegas - ini SANGAT mungkin eksemplar/copy
                    // fisik lain dari judul yang sama, sedang dipinjam user lain,
                    // bukan berarti transaksi user saat ini bermasalah/bug.
                    throw new RuntimeException(
                        "Eksemplar barcode '{$eksemplar->barcode}' ({$eksemplar->buku->judul}) sedang tidak tersedia (status: {$eksemplar->status->value}). ".
                            'Jika Anda mengira eksemplar ini seharusnya dikembalikan oleh user ini, periksa apakah barcode/ISBN yang di-scan sesuai dengan yang tadi dipinjam - satu judul buku bisa punya beberapa copy/eksemplar dengan barcode berbeda.'
                    );
                }

                $service->pinjamBuku(
                    user: $this->user,
                    eksemplarIds: [$eksemplar->id],
                    diprosesOleh: auth()->user(),
                );
                $this->tambahRiwayat($eksemplar->barcode, $eksemplar->buku->judul, 'dipinjamkan', 'Berhasil dipinjamkan.', true);
            }
        } catch (RuntimeException $e) {
            // Gagal diproses - buka kembali rate limit supaya operator bisa
            // langsung retry tanpa perlu menunggu window habis.
            Cache::forget($rateLimitKey);
            $this->tambahRiwayat($eksemplar->barcode, $eksemplar->buku->judul, 'error', $e->getMessage(), false);
        }

        $this->bisaMeminjam = app(PeminjamanService::class)->bisaMeminjam($this->user->fresh());
    }

    /**
     * Resolve input sebagai ISBN Buku (dipanggil ketika input tidak match
     * barcode Eksemplar manapun) -> pilih SATU Eksemplar yang relevan.
     *
     * TODO: GAP-SPEC - aturan pemilihan eksemplar saat scan ISBN (bukan
     * barcode eksemplar spesifik):
     *  1. PENGEMBALIAN: jika user ini punya Peminjaman aktif/terlambat atas
     *     eksemplar manapun dari Buku ber-ISBN ini, ambil yang PALING LAMA
     *     dipinjam (created_at terkecil). Asumsi: user jarang pinjam >1
     *     eksemplar dari judul yang sama secara bersamaan; kalau itu
     *     terjadi, operator TIDAK diminta memilih - sistem otomatis pilih
     *     yang tertua. Jika perilaku yang diinginkan adalah selalu minta
     *     scan barcode eksemplar spesifik ketika ambigu (bukan auto-pick),
     *     ubah logic ini untuk melempar RuntimeException alih-alih memilih.
     *  2. PEMINJAMAN BARU: ambil 1 Eksemplar berstatus Tersedia dari Buku
     *     ini secara FIFO (created_at terkecil) - operator TIDAK memilih
     *     eksemplar/copy fisik spesifik, sistem yang menentukan. Ini
     *     konsisten dengan premis gap ("barcode identik dengan ISBN yang
     *     dipinjam" - dianggap tidak ada preferensi copy tertentu).
     */
    protected function resolveEksemplarDariIsbn(string $isbn): ?Eksemplar
    {
        $buku = Buku::query()->where('isbn', $isbn)->first();

        if (! $buku) {
            return null;
        }

        $eksemplarDipinjamUser = Eksemplar::query()
            ->where('buku_id', $buku->id)
            ->whereHas('peminjamans', fn ($q) => $q
                ->where('user_id', $this->user->id)
                ->whereIn('status', [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat]))
            ->with('buku')
            ->oldest('created_at')
            ->first();

        if ($eksemplarDipinjamUser) {
            return $eksemplarDipinjamUser;
        }

        return Eksemplar::query()
            ->where('buku_id', $buku->id)
            ->where('status', StatusEksemplar::Tersedia)
            ->with('buku')
            ->oldest('created_at')
            ->first();
    }

    public function selesai(): void
    {
        $this->user = null;
        $this->riwayatScan = [];
        $this->bisaMeminjam = false;
        $this->kartuInput = '';
        $this->kodeInput = '';
    }

    protected function tambahRiwayat(string $barcode, string $judul, string $aksi, string $pesan, bool $sukses): void
    {
        array_unshift($this->riwayatScan, [
            'barcode' => $barcode,
            'judul' => $judul,
            'aksi' => $aksi,
            'pesan' => $pesan,
            'sukses' => $sukses,
        ]);
    }
}

```
---

## app/Filament/Resources/BukuResource/Pages/CreateBuku.php
```php
<?php

namespace App\Filament\Resources\BukuResource\Pages;

use App\Enums\StatusEksemplar;
use App\Filament\Resources\BukuResource;
use App\Models\Eksemplar;
use App\Models\Rak;
use Filament\Resources\Pages\CreateRecord;

class CreateBuku extends CreateRecord
{
    protected static string $resource = BukuResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    /**
     * GAP-SPEC ditutup: buku bisa langsung dibuat sekaligus dengan N
     * Eksemplar awal (field 'jumlah_eksemplar_awal' non-persisten, lihat
     * BukuResource::form()). Format barcode kini SATU SUMBER KEBENARAN
     * lewat Eksemplar::generateBarcodeUntuk() - sebelumnya kode generate
     * barcode disalin persis dari BukuImporter::afterSave() (Aturan poin 3).
     */
    protected function afterCreate(): void
    {
        $jumlah = (int) ($this->data['jumlah_eksemplar_awal'] ?? 0);

        if ($jumlah <= 0) {
            return;
        }

        $buku = $this->record;
        $rak = ! empty($this->data['rak_id_eksemplar_awal'])
            ? Rak::query()->find($this->data['rak_id_eksemplar_awal'])
            : null;

        for ($i = 0; $i < $jumlah; $i++) {
            $buku->eksemplars()->create([
                'barcode' => Eksemplar::generateBarcodeUntuk($buku, $i + 1),
                'rak_id' => $rak?->id,
                'status' => StatusEksemplar::Tersedia,
            ]);
        }
    }
}

```
---

## app/Filament/Resources/BukuResource/Pages/EditBuku.php
```php
<?php

namespace App\Filament\Resources\BukuResource\Pages;

use App\Filament\Resources\BukuResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBuku extends EditRecord
{
    protected static string $resource = BukuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/BukuResource/Pages/ListBukus.php
```php
<?php

namespace App\Filament\Resources\BukuResource\Pages;

use App\Filament\Resources\BukuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBukus extends ListRecords
{
    protected static string $resource = BukuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

```
---

## app/Filament/Resources/BukuResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Enums\StatusPeminjaman;
use App\Filament\Exports\BukuExporter;
use App\Filament\Imports\BukuImporter;
use App\Filament\Resources\BukuResource\Pages;
use App\Filament\Resources\BukuResource\RelationManagers\EksemplarsRelationManager;
use App\Jobs\GenerateLabelBarcodePdfJob;
use App\Models\Buku;
use App\Models\Eksemplar;
use App\Models\Rak;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class BukuResource extends Resource
{
    protected static ?string $model = Buku::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Buku';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('judul')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            FileUpload::make('cover')
                ->image()
                ->directory('buku-cover'),
            TextInput::make('penulis')
                ->maxLength(255),
            TextInput::make('penerbit')
                ->maxLength(255),
            TextInput::make('isbn')
                ->label('ISBN')
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('1 ISBN = 1 judul. Jumlah eksemplar fisik dikelola di tab Eksemplar setelah buku disimpan.'),
            TextInput::make('tahun_terbit')
                ->label('Tahun Terbit')
                ->numeric()
                ->minValue(1000)
                ->maxValue((int) date('Y'))
                ->maxLength(4),
            Select::make('kategoris')
                ->label('Kategori')
                ->relationship('kategoris', 'nama')
                ->multiple()
                ->preload()
                ->searchable(),
            TextInput::make('harga_ganti')
                ->label('Harga Ganti')
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->helperText('Dipakai sebagai basis perhitunganDenda kerusakan/kehilangan untuk semua eksemplar judul ini.'),
            Textarea::make('deskripsi')
                ->columnSpanFull(),
            // GAP-SPEC ditutup: field non-persisten, hanya dipakai saat
            // create (lihat CreateBuku::afterCreate()) untuk sekaligus
            // membuat N Eksemplar baru - tidak ada kolom 'jumlah_eksemplar'
            // di tabel bukus, jadi dehydrated(false) dan disembunyikan di
            // context edit (Aturan poin 3 - ubah stok setelah create tetap
            // HANYA lewat tab Eksemplar/BukuImporter, bukan disini).
            TextInput::make('jumlah_eksemplar_awal')
                ->label('Jumlah Eksemplar Awal')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->helperText('Opsional - langsung membuat N eksemplar berstatus Tersedia. Jumlah eksemplar SETELAH buku dibuat tetap dikelola lewat tab Eksemplar atau Import Buku.')
                ->dehydrated(false)
                ->visibleOn('create'),
            Select::make('rak_id_eksemplar_awal')
                ->label('Rak untuk Eksemplar Awal')
                ->options(fn() => Rak::query()->pluck('nama', 'id'))
                ->searchable()
                ->helperText('Opsional - rak yang sama dipakaikan ke semuaeksemplar awal yang dibuat.')
                ->dehydrated(false)
                ->visibleOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->withCount([
                // BARU (iterasi ini) - diganti dari 4x query N+1 per baris
                // (method model dipanggil di ->state() closure) jadi 4
                // sub-select dalam SATU query withCount, dieksekusi sekali
                // untuk seluruh halaman (bukan per baris) - Aturan poin 3/9,
                // penting untuk skala data besar.
                'eksemplars as jumlah_eksemplar_aktif' => fn($q) => $q->where('status', '!=', \App\Enums\StatusEksemplar::Hilang),
                'eksemplars as jumlah_stok_tersedia' => fn($q) => $q->where('status', \App\Enums\StatusEksemplar::Tersedia),
                'eksemplars as jumlah_eksemplar_rusak' => fn($q) => $q->where('status', \App\Enums\StatusEksemplar::Rusak),
                'eksemplars as jumlah_eksemplar_hilang' => fn($q) => $q->where('status', \App\Enums\StatusEksemplar::Hilang),
            ]))
            ->headerActions([
                ImportAction::make()
                    ->importer(BukuImporter::class)
                    ->authorize(fn() => auth()->user()?->can('create', Buku::class) ?? false),
                ExportAction::make()
                    ->exporter(BukuExporter::class)
                    ->authorize(fn() => auth()->user()?->can('viewAny', Buku::class) ?? false),
            ])
            ->columns([
                ImageColumn::make('cover')
                    ->square(),
                TextColumn::make('judul')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('isbn')
                    ->label('ISBN')
                    ->searchable(),
                TextColumn::make('tahun_terbit')
                    ->label('Tahun')
                    ->sortable()
                    ->toggleable(),
                // Sekarang membaca kolom hasil withCount di atas - TIDAK
                // lagi memanggil $record->jumlahEksemplarAktif() (yang
                // menjalankan query baru tiap baris).
                TextColumn::make('jumlah_eksemplar_aktif')
                    ->label('Jumlah Buku')
                    ->description(fn(Buku $record) => $record->jumlah_eksemplar_hilang > 0
                        ? "{$record->jumlah_eksemplar_hilang} hilang (tidak dihitung)"
                        : null)
                    ->badge()
                    ->color(fn(Buku $record) => $record->jumlah_eksemplar_aktif > 0 ? 'gray' : 'danger')
                    ->sortable(),
                TextColumn::make('jumlah_stok_tersedia')
                    ->label('Stok Tersedia')
                    ->badge()
                    ->color(fn(Buku $record) => $record->jumlah_stok_tersedia > 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('eksemplar_bermasalah')
                    ->label('Rusak/Hilang')
                    ->state(fn(Buku $record) => $record->jumlah_eksemplar_rusak + $record->jumlah_eksemplar_hilang)
                    ->badge()
                    ->color(fn(Buku $record) => ($record->jumlah_eksemplar_rusak + $record->jumlah_eksemplar_hilang) > 0 ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('harga_ganti')
                    ->label('Harga Ganti')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                DeleteAction::make(),
                ForceDeleteAction::make()
                    ->action(function (Buku $record) {
                        $adaPeminjamanBerjalan = Eksemplar::query()
                            ->withTrashed()
                            ->where('buku_id', $record->id)
                            ->whereHas('peminjamans', fn($q) => $q->whereIn('status', [
                                StatusPeminjaman::Aktif,
                                StatusPeminjaman::Terlambat,
                            ]))
                            ->exists();

                        if ($adaPeminjamanBerjalan) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak bisa dihapus permanen')
                                ->body('Masih ada Peminjaman Aktif/Terlambat yang menggunakan eksemplar buku ini. Selesaikan/kembalikan dulu sebelum force delete.')
                                ->send();

                            return;
                        }

                        $record->forceDelete();
                    }),
                RestoreAction::make(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('kategoris')
                    ->label('Kategori')
                    ->relationship('kategoris', 'nama'),
            ])
            ->toolbarActions([
                BulkAction::make('cetak_label_massal')
                    ->label('Cetak Label Eksemplar')
                    ->icon('heroicon-o-printer')
                    ->authorize(fn() => auth()->user()?->can('viewAny', Eksemplar::class) ?? false)
                    ->action(function (Collection $records) {
                        $eksemplarIds = Eksemplar::query()
                            ->whereIn('buku_id', $records->pluck('id'))
                            ->pluck('id')
                            ->all();

                        if (empty($eksemplarIds)) {
                            Notification::make()
                                ->warning()
                                ->title('Tidak ada Eksemplar')
                                ->body('Buku yang dipilih belumpunya Eksemplar untuk dicetak labelnya.')
                                ->send();

                            return;
                        }

                        GenerateLabelBarcodePdfJob::dispatch($eksemplarIds, (string) auth()->id());

                        Notification::make()
                            ->info()
                            ->title('Sedang memproses label barcode')
                            ->body('Anda akan menerima notifikasi begitu PDF siap diunduh.')
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EksemplarsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBukus::route('/'),
            'create' => Pages\CreateBuku::route('/create'),
            'edit' => Pages\EditBuku::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Resources/BukuResource/RelationManagers/EksemplarsRelationManager.php
```php
<?php

namespace App\Filament\Resources\BukuResource\RelationManagers;

use App\Enums\KondisiBuku;
use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Enums\TipeDenda;
use App\Filament\Exports\EksemplarExporter;
use App\Filament\Imports\EksemplarImporter;
use App\Jobs\GenerateLabelBarcodePdfJob;
use App\Models\Eksemplar;
use App\Services\LabelBarcodeService;
use App\Services\PeminjamanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class EksemplarsRelationManager extends RelationManager
{
    protected static string $relationship = 'eksemplars';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('barcode')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Select::make('rak_id')
                ->label('Rak')
                ->relationship('rak', 'nama')
                ->searchable()
                ->preload(),
            Select::make('status')
                ->options(collect(StatusEksemplar::cases())->mapWithKeys(fn($s) => [$s->value => ucfirst($s->value)]))
                ->required()
                ->default(StatusEksemplar::Tersedia->value)
                ->helperText('Ubah manual hanya untuk koreksi data - alur normal status diubah otomatis oleh PeminjamanService.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('barcode')
            // BARU (iterasi ini) - eager load relasi yang dipakai kolom
            // Peminjam/Denda/Refund di bawah, supaya tidak N+1 query per
            // baris (Aturan poin 3/9 - performa).
            ->modifyQueryUsing(fn(Builder $query) => $query->with([
                'peminjamanTerakhir.user',
                'peminjamanTerakhir.dendas',
            ]))
            ->headerActions([
                ImportAction::make()
                    ->importer(EksemplarImporter::class)
                    ->authorize(fn() => auth()->user()?->can('create', Eksemplar::class) ?? false),
                ExportAction::make()
                    ->exporter(EksemplarExporter::class)
                    ->authorize(fn() => auth()->user()?->can('viewAny', Eksemplar::class) ?? false),
            ])
            ->columns([
                TextColumn::make('barcode')->searchable(),
                TextColumn::make('rak.nama')->label('Rak'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(StatusEksemplar $state) => match ($state) {
                        StatusEksemplar::Tersedia => 'success',
                        StatusEksemplar::Dipinjam => 'warning',
                        StatusEksemplar::Rusak, StatusEksemplar::Hilang => 'danger',
                    }),
                // BARU (iterasi ini) - hanya relevan untuk eksemplar
                // Rusak/Hilang, tapi ditampilkan apa adanya (placeholder '-'
                // untuk status lain) supaya tidak perlu logic visible/hidden
                // per kolom yang rumit di Filament table.
                TextColumn::make('peminjamanTerakhir.user.nama')
                    ->label('Dipinjam/Dirusak/Dihilangkan Oleh')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status_denda')
                    ->label('Status Denda')
                    ->state(fn(Eksemplar $record) => static::labelStatusDenda($record))
                    ->badge()
                    ->color(fn(Eksemplar $record) => match (static::labelStatusDenda($record)) {
                        'Lunas' => 'success',
                        'Belum Lunas' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status_refund_denda')
                    ->label('Status Refund')
                    ->state(fn(Eksemplar $record) => static::dendaTerkait($record)?->status_refund?->value)
                    ->formatStateUsing(fn(?string $state) => $state ? ucfirst(str_replace('_', ' ', $state)) : null)
                    ->badge()
                    ->color(fn(?string $state) => match ($state) {
                        'perlu_refund' => 'warning',
                        'sudah_direfund' => 'success',
                        default => 'gray',
                    })
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(StatusEksemplar::cases())->mapWithKeys(fn($s) => [$s->value => ucfirst($s->value)])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->disabled(fn(Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam)
                    ->tooltip(fn(Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam
                        ? 'Eksemplar sedang dipinjam - tidak bisa diedit manual di sini.'
                        : null),
                DeleteAction::make()
                    ->disabled(fn(Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam)
                    ->tooltip(fn(Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam
                        ? 'Eksemplar sedang dipinjam - tidak bisa dihapus.'
                        : null),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->action(function (Eksemplar $record) {
                        $adaPeminjamanBerjalan = $record->peminjamans()
                            ->whereIn('status', [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat])
                            ->exists();

                        if ($adaPeminjamanBerjalan) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak bisa dihapus permanen')
                                ->body('Eksemplar ini masih punya Peminjaman Aktif/Terlambat. Selesaikan/kembalikan dulu sebelum force delete.')
                                ->send();

                            return;
                        }

                        $record->forceDelete();
                    }),
                // BARU (iterasi ini) - satu tombol menangani DUA jalur data
                // "Hilang" (lihat penjelasan di respons, TODO: ASUMSI
                // otorisasi memakai 'Update:Pengembalian' - lihat catatan di
                // PeminjamanService::bukuDitemukanKembali()):
                // 1. Ada Pengembalian (hilang dilaporkan via proses
                //    pengembalian normal) -> koreksiKondisiPengembalian().
                // 2. Tidak ada Pengembalian (hilang via laporkanHilang() saat
                //    masih dipinjam) -> bukuDitemukanKembali().
                Action::make('buku_ditemukan_kembali')
                    ->label('Buku Ditemukan Kembali')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn(Eksemplar $record) => $record->status === StatusEksemplar::Hilang)
                    ->requiresConfirmation()
                    ->modalDescription('Eksemplar akan dikembalikan ke status Tersedia dan Denda kehilangan terkait dibatalkan. Jika Denda sudah lunas, refund manual tetap perlu diproses pustakawan.')
                    ->authorize(fn() => auth()->user()?->can('Update:Pengembalian') ?? false)
                    ->action(function (Eksemplar $record) {
                        $peminjaman = $record->peminjamanTerakhir;

                        if (! $peminjaman || $peminjaman->status !== StatusPeminjaman::Hilang) {
                            Notification::make()
                                ->danger()
                                ->title('Data Peminjaman tidak konsisten')
                                ->body('Eksemplar berstatus Hilang tapi Peminjaman terkait tidak ditemukan/tidak berstatus Hilang. Periksa data secara manual.')
                                ->send();

                            return;
                        }

                        try {
                            if ($peminjaman->pengembalian) {
                                app(PeminjamanService::class)->koreksiKondisiPengembalian(
                                    pengembalian: $peminjaman->pengembalian,
                                    kondisiBaru: KondisiBuku::Baik,
                                    diprosesOleh: auth()->user(),
                                );
                            } else {
                                app(PeminjamanService::class)->bukuDitemukanKembali($peminjaman);
                            }

                            Notification::make()
                                ->success()
                                ->title('Eksemplar ditandai ditemukan kembali')
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal memproses')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                // BARU - cetak 1 label barcode langsung dari baris tabel.
                // Reuse ability 'view' Eksemplar (tidak ada permission baru,
                // Aturan poin 15 - tidak mengubah skema/otorisasi).
                Action::make('cetak_label')
                    ->label('Cetak Label')
                    ->icon('heroicon-o-printer')
                    ->authorize(fn(Eksemplar $record) => auth()->user()?->can('view', $record) ?? false)
                    ->action(function (Eksemplar $record, LabelBarcodeService $service) {
                        $record->loadMissing('buku');
                        $labels = $service->generateData(collect([$record]));

                        $pdf = Pdf::loadView('pdf.label-barcode', ['labels' => $labels])
                            ->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn() => print($pdf->output()),
                            "label-{$record->barcode}.pdf",
                            ['Content-Type' => 'application/pdf'],
                        );
                    }),
            ])
            ->toolbarActions([
                // Cetak label massal dari eksemplar terpilih -DIPINDAH ke
                // queue 'default' (dikonfirmasi, konsisten dengan
                // BukuResource::cetak_label_massal). Reuse ability
                // 'viewAny' Eksemplar, tidak ada permission baru.
                BulkAction::make('cetak_label_massal')
                    ->label('Cetak Label (Massal)')
                    ->icon('heroicon-o-printer')
                    ->authorize(fn() => auth()->user()?->can('viewAny', Eksemplar::class) ?? false)
                    ->action(function (Collection $records) {
                        GenerateLabelBarcodePdfJob::dispatch(
                            $records->pluck('id')->all(),
                            (string) auth()->id(),
                        );

                        Notification::make()
                            ->info()
                            ->title('Sedang memproses label barcode')
                            ->body('Anda akan menerima notifikasi begitu PDF siap diunduh.')
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    /**
     * dihitung sekali, dipakai kolom Status Denda & Status Refund supaya
     * tidak duplikasi logic pencarian Denda terkait (Aturan poin 3).
     */
    protected static function dendaTerkait(Eksemplar $record): ?\App\Models\Denda
    {
        $tipe = match ($record->status) {
            StatusEksemplar::Rusak => TipeDenda::Kerusakan,
            StatusEksemplar::Hilang => TipeDenda::Kehilangan,
            default => null,
        };

        if (! $tipe || ! $record->peminjamanTerakhir) {
            return null;
        }

        return $record->peminjamanTerakhir->dendas
            ->where('tipe', $tipe)
            ->sortByDesc('created_at')
            ->first();
    }

    protected static function labelStatusDenda(Eksemplar $record): ?string
    {
        $denda = static::dendaTerkait($record);

        if (! $denda) {
            return null;
        }

        return $denda->status_lunas ? 'Lunas' : 'Belum Lunas';
    }
}

```
---

## app/Filament/Resources/DendaResource/Pages/ListDendas.php
```php
<?php

namespace App\Filament\Resources\DendaResource\Pages;

use App\Filament\Resources\DendaResource;
use Filament\Resources\Pages\ListRecords;

class ListDendas extends ListRecords
{
    protected static string $resource = DendaResource::class;
}

```
---

## app/Filament/Resources/DendaResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Enums\JenisTransaksi;
use App\Enums\StatusRefund;
use App\Enums\TipeDenda;
use App\Filament\Exports\DendaExporter;
use App\Filament\Resources\DendaResource\Pages;
use App\Models\Denda;
use App\Models\Transaksi;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Denda SELALU dibuat otomatis oleh PeminjamanService (keterlambatan saat
 * pengembalian, kerusakan/kehilangan saat proses terkait) - tidak ada
 * Create/Edit page di Resource ini, sesuai pola PengembalianResource
 * (Aturan poin 3, DRY - tidak ada jalan lain mengubah data selain lewat
 * Service/Observer terpusat).
 *
 * TODO: GAP-SPEC - PeminjamanService::batalkanDenda() TIDAK men-set
 * status_refund ke 'perlu_refund' saat membatalkan Denda yang sudah
 * terbayar (lihat komentar di method tsb + migration
 * add_status_refund_to_dendas_table). Action 'update_status_refund' di
 * bawah adalah mitigasi manual sementara - Admin harus proaktif mengecek
 * kolom 'keterangan' untuk tahu ada Denda yang perlu direfund, sistem
 * TIDAK memberi notifikasi otomatis untuk ini. Perlu konfirmasi apakah
 * PeminjamanService perlu di-patch.
 */
class DendaResource extends Resource
{
    protected static ?string $model = Denda::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Denda';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(DendaExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Denda::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                // BUG FIX (iterasi ini): 'peminjaman.buku.judul' DIHAPUS -
                // Peminjaman tidak lagi punya relasi langsung ke Buku sejak
                // migration 2026_08_02_000002-000004. Diganti
                // 'peminjaman.eksemplar.buku.judul', konsisten dengan
                // PengembalianResource yang sudah benar.
                TextColumn::make('peminjaman.eksemplar.buku.judul')
                    ->label('Buku')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tipe')
                    ->badge()
                    ->color(fn (TipeDenda $state) => match ($state) {
                        TipeDenda::Keterlambatan => 'warning',
                        TipeDenda::Kerusakan => 'danger',
                        TipeDenda::Kehilangan => 'danger',
                    }),
                TextColumn::make('nominal')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status_lunas')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Lunas' : 'Belum Lunas')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                TextColumn::make('tanggal_lunas')
                    ->dateTime('d F Y H:i')
                    ->toggleable(),
                TextColumn::make('status_refund')
                    ->label('Refund')
                    ->badge()
                    ->color(fn (StatusRefund $state) => match ($state) {
                        StatusRefund::TidakPerlu => 'gray',
                        StatusRefund::PerluRefund => 'warning',
                        StatusRefund::SudahDirefund => 'success',
                    })
                    ->toggleable(),
                TextColumn::make('keterangan')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->options(collect(TipeDenda::cases())->mapWithKeys(fn ($t) => [$t->value => ucfirst($t->value)])),
                TernaryFilter::make('status_lunas')
                    ->label('Status Lunas'),
                SelectFilter::make('status_refund')
                    ->label('Status Refund')
                    ->options(collect(StatusRefund::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))])),
            ])
            ->recordActions([
                Action::make('tandai_lunas')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    // TODO: ASUMSI - Pustakawan boleh tandai lunas (setara
                    // proses pembayaran di meja), sama seperti pola akses
                    // Aksi "Proses Pengembalian" - perlu dikonfirmasi.
                    ->authorize(fn (Denda $record) => auth()->user()?->can('update', $record) ?? false)
                    ->visible(fn (Denda $record) => ! $record->status_lunas)
                    ->requiresConfirmation()
                    ->schema([
                        DateTimePicker::make('tanggal_lunas')
                            ->label('Tanggal Lunas')
                            ->default(now())
                            ->required(),
                        Textarea::make('keterangan')
                            ->label('Catatan')
                            ->default(fn (Denda $record) => $record->keterangan),
                    ])
                    ->action(function (Denda $record, array $data) {
                        // dipicu DendaObserver::updated() -> cek auto-unsuspend user
                        $record->update([
                            'status_lunas' => true,
                            'tanggal_lunas' => $data['tanggal_lunas'],
                            'keterangan' => $data['keterangan'] ?? $record->keterangan,
                        ]);

                        // FITUR BARU: catat Transaksi jenis pembayaran_denda -
                        // satu sumber kebenaran pembuatan Transaksi tipe ini,
                        // jangan duplikasi di tempat lain (Aturan poin 3).
                        Transaksi::create([
                            'user_id' => $record->user_id,
                            'jenis' => JenisTransaksi::PembayaranDenda,
                            'diproses_oleh' => auth()->id(),
                            'tanggal' => $data['tanggal_lunas'],
                            'keterangan' => "Pembayaran Denda {$record->tipe->value} sebesar Rp".number_format((float) $record->nominal, 0, ',', '.'),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Denda ditandai lunas')
                            ->send();
                    }),

                Action::make('update_status_refund')
                    ->label('Update Status Refund')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    // Sengaja HANYA super_admin (bukan permission 'update'
                    // biasa) - lihat TODO: GAP-SPEC di atas class, ini
                    // mitigasi manual untuk gap yang belum ada alur
                    // otomatisnya, jadi dibatasi lebih ketat dari Update:Denda.
                    ->authorize(fn () => auth()->user()?->hasRole('super_admin') ?? false)
                    ->schema([
                        Select::make('status_refund')
                            ->label('Status Refund')
                            ->options(collect(StatusRefund::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))]))
                            ->required(),
                    ])
                    ->action(function (Denda $record, array $data) {
                        $record->update(['status_refund' => $data['status_refund']]);

                        Notification::make()
                            ->success()
                            ->title('Status refund diperbarui')
                            ->send();
                    }),

                DeleteAction::make(), // digerbang DendaPolicy::delete() -hanya Admin, lihat ShieldSeeder
            ])
            ->toolbarActions([
                DeleteBulkAction::make(), // digerbang DendaPolicy::deleteAny()
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDendas::route('/'),
        ];
    }
}

```
---

## app/Filament/Resources/FirmwareResource/Pages/CreateFirmwareRelease.php
```php
<?php

namespace App\Filament\Resources\FirmwareResource\Pages;

use App\Filament\Resources\FirmwareResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateFirmwareRelease extends CreateRecord
{
    protected static string $resource = FirmwareResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $path = $data['file'] ?? null;
        unset($data['file']);

        if ($path) {
            $data['url'] = Storage::disk('public')->url($path);
            $data['md5'] = md5_file(Storage::disk('public')->path($path));
        }

        return $data;
    }
}

```
---

## app/Filament/Resources/FirmwareResource/Pages/EditFirmwareRelease.php
```php
<?php

namespace App\Filament\Resources\FirmwareResource\Pages;

use App\Filament\Resources\FirmwareResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditFirmwareRelease extends EditRecord
{
    protected static string $resource = FirmwareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $path = $data['file'] ?? null;
        unset($data['file']);

        // Hanya recompute url/md5 kalau ada file BARU diupload - lihat
        // GAP-SPEC di FirmwareResource (form Edit tidak preload file lama).
        if ($path) {
            $data['url'] = Storage::disk('public')->url($path);
            $data['md5'] = md5_file(Storage::disk('public')->path($path));
        }

        return $data;
    }
}

```
---

## app/Filament/Resources/FirmwareResource/Pages/ListFirmwareReleases.php
```php
<?php

namespace App\Filament\Resources\FirmwareResource\Pages;

use App\Filament\Resources\FirmwareResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFirmwareReleases extends ListRecords
{
    protected static string $resource = FirmwareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

```
---

## app/Filament/Resources/FirmwareResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FirmwareResource\Pages;
use App\Models\FirmwareRelease;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Kelola rilis firmware OTA untuk device Attendance Machine (ESP32-C3).
 * File .bin disimpan di disk 'public' (dikonfirmasi user) - URL hasil
 * upload langsung dipakai sebagai field 'url' yang dikirim ke device lewat
 * PerpustakaanDeviceController::firmwareCheck().
 *
 * TODO: GAP-SPEC - form Edit TIDAK menampilkan preview file lama (field
 * 'file' hanya dipetakan satu arah saat create/update baru), karena kolom
 * tersimpan adalah 'url' (full URL) bukan path relatif disk. Jika ingin
 * ganti versi, admin wajib upload ulang file setiap kali submit form Edit.
 */
class FirmwareResource extends Resource
{
    protected static ?string $model = FirmwareRelease::class;

    protected static ?string $navigationLabel = 'Firmware OTA';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('version')
                ->label('Versi (semver x.y.z)')
                ->required()
                ->unique(ignoreRecord: true)
                ->rule('regex:/^\d+\.\d+\.\d+$/')
                ->helperText('Format wajib x.y.z, dibandingkan device via compareFirmwareVersion().'),
            FileUpload::make('file')
                ->label('File Firmware (.bin)')
                ->disk('public')
                ->directory('firmware')
                ->required(fn (string $context) => $context === 'create')
                ->helperText('Upload ulang file setiap kali menyimpan (lihat catatan GAP-SPEC di kode).'),
            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true)
                ->helperText('Hanya rilis aktif dengan versi tertinggi yang ditawarkan ke device.'),
            Textarea::make('catatan')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('url')
                    ->label('URL')
                    ->limit(40)
                    ->copyable(),
                TextColumn::make('md5')
                    ->label('MD5')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFirmwareReleases::route('/'),
            'create' => Pages\CreateFirmwareRelease::route('/create'),
            'edit' => Pages\EditFirmwareRelease::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Resources/JurusanResource/Pages/CreateJurusan.php
```php
<?php

namespace App\Filament\Resources\JurusanResource\Pages;

use App\Filament\Resources\JurusanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJurusan extends CreateRecord
{
    protected static string $resource = JurusanResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/JurusanResource/Pages/EditJurusan.php
```php
<?php

namespace App\Filament\Resources\JurusanResource\Pages;

use App\Filament\Resources\JurusanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJurusan extends EditRecord
{
    protected static string $resource = JurusanResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

```
---

## app/Filament/Resources/JurusanResource/Pages/ListJurusans.php
```php
<?php

namespace App\Filament\Resources\JurusanResource\Pages;

use App\Filament\Resources\JurusanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJurusans extends ListRecords
{
    protected static string $resource = JurusanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

```
---

## app/Filament/Resources/JurusanResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\JurusanExporter;
use App\Filament\Imports\JurusanImporter;
use App\Filament\Resources\JurusanResource\Pages;
use App\Models\Jurusan;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JurusanResource extends Resource
{
    protected static ?string $model = Jurusan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Jurusan';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')->required()->maxLength(255),
            TextInput::make('kode')->required()->unique(ignoreRecord: true)->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(JurusanImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Jurusan::class) ?? false),
                ExportAction::make()
                    ->exporter(JurusanExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Jurusan::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('kode')->searchable(),
                TextColumn::make('kelas_count')->label('Jumlah Kelas')->counts('kelas'),
                TextColumn::make('created_at')->dateTime('d F Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJurusans::route('/'),
            'create' => Pages\CreateJurusan::route('/create'),
            'edit' => Pages\EditJurusan::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Resources/KategoriResource/Pages/CreateKategori.php
```php
<?php

namespace App\Filament\Resources\KategoriResource\Pages;

use App\Filament\Resources\KategoriResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKategori extends CreateRecord
{
    protected static string $resource = KategoriResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/KategoriResource/Pages/EditKategori.php
```php
<?php

namespace App\Filament\Resources\KategoriResource\Pages;

use App\Filament\Resources\KategoriResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKategori extends EditRecord
{
    protected static string $resource = KategoriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/KategoriResource/Pages/ListKategoris.php
```php
<?php

namespace App\Filament\Resources\KategoriResource\Pages;

use App\Filament\Resources\KategoriResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKategoris extends ListRecords
{
    protected static string $resource = KategoriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

```
---

## app/Filament/Resources/KategoriResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\KategoriExporter;
use App\Filament\Imports\KategoriImporter;
use App\Filament\Resources\KategoriResource\Pages;
use App\Models\Kategori;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KategoriResource extends Resource
{
    protected static ?string $model = Kategori::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Kategori';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->required()
                ->maxLength(255),
            Textarea::make('deskripsi')
                ->columnSpanFull(),
            Select::make('raks')
                ->label('Rak Terkait')
                ->relationship('raks', 'nama')
                ->multiple()
                ->preload()
                ->searchable()
                ->createOptionForm([
                    TextInput::make('nama')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('lokasi')
                        ->maxLength(255),
                    Select::make('kategoris')
                        ->label('Kategori Terkait')
                        ->relationship('kategoris', 'nama')
                        ->multiple()
                        ->preload()
                        ->searchable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(KategoriImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Kategori::class) ?? false),
                ExportAction::make()
                    ->exporter(KategoriExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Kategori::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('deskripsi')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('bukus_count')
                    ->label('Jumlah Buku')
                    ->counts('bukus')
                    ->sortable(),
                TextColumn::make('eksemplars_count')
                    ->label('Jumlah Eksemplar')
                    ->counts('eksemplars')
                    ->sortable(),
                TextColumn::make('stok_tersedia')
                    ->label('Stok Tersedia')
                    ->state(fn (Kategori $record) => $record->stokTersedia())
                    ->badge()
                    ->color(fn (Kategori $record) => $record->stokTersedia() > 0 ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKategoris::route('/'),
            'create' => Pages\CreateKategori::route('/create'),
            'edit' => Pages\EditKategori::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Resources/KelasResource/Pages/CreateKelas.php
```php
<?php

namespace App\Filament\Resources\KelasResource\Pages;

use App\Filament\Resources\KelasResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKelas extends CreateRecord
{
    protected static string $resource = KelasResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/KelasResource/Pages/EditKelas.php
```php
<?php

namespace App\Filament\Resources\KelasResource\Pages;

use App\Filament\Resources\KelasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKelas extends EditRecord
{
    protected static string $resource = KelasResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

```
---

## app/Filament/Resources/KelasResource/Pages/ListKelas.php
```php
<?php

namespace App\Filament\Resources\KelasResource\Pages;

use App\Filament\Resources\KelasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKelas extends ListRecords
{
    protected static string $resource = KelasResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

```
---

## app/Filament/Resources/KelasResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\KelasExporter;
use App\Filament\Imports\KelasImporter;
use App\Filament\Resources\KelasResource\Pages;
use App\Models\Kelas;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KelasResource extends Resource
{
    protected static ?string $model = Kelas::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Kelas';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama Kelas (mis. X IPA 1)')
                // BARU iterasi ini - unik secara global (dikonfirmasi),
                // lihat migration 2026_08_02_000001_add_unique_nama_to_kelas_table
                // dan KelasImporter (Aturan poin 3/11 - validasi
                // konsisten antara form manual dan import).
                ->unique(ignoreRecord: true)
                ->required()
                ->maxLength(255),
            TextInput::make('tingkat')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->required()
                ->helperText('Angka tingkat, mis. 10, 11, 12 - dipakai untuk urutan kenaikan kelas.'),
            Select::make('jurusan_id')
                ->label('Jurusan')
                ->relationship('jurusan', 'nama')
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(KelasImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Kelas::class) ?? false),
                ExportAction::make()
                    ->exporter(KelasExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Kelas::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('tingkat')->sortable(),
                TextColumn::make('jurusan.nama')->label('Jurusan')->toggleable(),
                TextColumn::make('created_at')->dateTime('d F Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jurusan_id')->label('Jurusan')->relationship('jurusan', 'nama'),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKelas::route('/'),
            'create' => Pages\CreateKelas::route('/create'),
            'edit' => Pages\EditKelas::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Resources/KelasTahunPelajaranResource/Pages/CreateKelasTahunPelajaran.php
```php
<?php

namespace App\Filament\Resources\KelasTahunPelajaranResource\Pages;

use App\Filament\Resources\KelasTahunPelajaranResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKelasTahunPelajaran extends CreateRecord
{
    protected static string $resource = KelasTahunPelajaranResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/KelasTahunPelajaranResource/Pages/EditKelasTahunPelajaran.php
```php
<?php

namespace App\Filament\Resources\KelasTahunPelajaranResource\Pages;

use App\Filament\Resources\KelasTahunPelajaranResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKelasTahunPelajaran extends EditRecord
{
    protected static string $resource = KelasTahunPelajaranResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

```
---

## app/Filament/Resources/KelasTahunPelajaranResource/Pages/ListKelasTahunPelajarans.php
```php
<?php

namespace App\Filament\Resources\KelasTahunPelajaranResource\Pages;

use App\Filament\Resources\KelasTahunPelajaranResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKelasTahunPelajarans extends ListRecords
{
    protected static string $resource = KelasTahunPelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

```
---

## app/Filament/Resources/KelasTahunPelajaranResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\KelasTahunPelajaranExporter;
use App\Filament\Imports\KelasTahunPelajaranImporter;
use App\Filament\Pages\ProsesKenaikanKelas;
use App\Filament\Resources\KelasTahunPelajaranResource\Pages;
use App\Filament\Resources\KelasTahunPelajaranResource\RelationManagers\SiswaAktifRelationManager;
use App\Models\KelasTahunPelajaran;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Instance Kelas untuk Tahun Pelajaran tertentu - satu kombinasi
 * kelas_id + tahun_pelajaran_id unik (lihat migration unique index).
 */
class KelasTahunPelajaranResource extends Resource
{
    protected static ?string $model = KelasTahunPelajaran::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Kelas per Tahun Pelajaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kelas_id')
                ->label('Kelas')
                ->relationship('kelas', 'nama')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('tahun_pelajaran_id')
                ->label('Tahun Pelajaran')
                ->relationship('tahunPelajaran', 'nama')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('wali_kelas_id')
                ->label('Wali Kelas')
                // FIX: 'super_admin' (nilai RoleUser::Admin) DIHAPUS dari
                // daftar ini - super_admin tidak boleh menjadi wali kelas
                // (dikonfirmasi Aturan). Sebelumnya bug: role ini ikut
                // tersaring masuk sebagai kandidat wali kelas.
                ->relationship('waliKelas', 'nama', fn ($query) => $query->whereIn('role', ['pustakawan', 'pegawai']))
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(KelasTahunPelajaranImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', KelasTahunPelajaran::class) ?? false),
                ExportAction::make()
                    ->exporter(KelasTahunPelajaranExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', KelasTahunPelajaran::class) ?? false),
            ])
            ->columns([
                TextColumn::make('kelas.nama')->label('Kelas')->searchable()->sortable(),
                TextColumn::make('tahunPelajaran.nama')->label('Tahun Pelajaran')->searchable()->sortable(),
                TextColumn::make('waliKelas.nama')->label('Wali Kelas')->toggleable(),
                TextColumn::make('siswa_aktif_count')->label('Jumlah Siswa')->counts('siswaAktif'),
            ])
            ->filters([
                SelectFilter::make('tahun_pelajaran_id')->label('Tahun Pelajaran')->relationship('tahunPelajaran', 'nama'),
            ])
            ->recordActions([
                Action::make('proses_kenaikan')
                    ->label('Proses Kenaikan Kelas')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('warning')
                    ->url(fn (KelasTahunPelajaran $record) => ProsesKenaikanKelas::getUrl(['ktp' => $record->id])),
                DeleteAction::make(),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            SiswaAktifRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKelasTahunPelajarans::route('/'),
            'create' => Pages\CreateKelasTahunPelajaran::route('/create'),
            'edit' => Pages\EditKelasTahunPelajaran::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Resources/KelasTahunPelajaranResource/RelationManagers/SiswaAktifRelationManager.php
```php
<?php

namespace App\Filament\Resources\KelasTahunPelajaranResource\RelationManagers;

use App\Enums\RoleUser;
use App\Models\KelasTahunPelajaran;
use App\Models\User;
use App\Services\KenaikanKelasService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Assignment/pelepasan siswa SELALU lewat KenaikanKelasService (Aturan
 * poin 3, DRY) - tidak ada attach()/detach() pivot langsung di sini,
 * karena relasi ini belongsTo di sisi User (kelas_tahun_pelajaran_id),
 * bukan pivot, dan setiap perubahan wajib tercatat di RiwayatKelasSiswa.
 *
 * // TODO: GAP-SPEC - "Tambah Siswa" di sini memakai KenaikanKelasService::
 * assignKelas() yang SAMA dengan bulk action UserResource - artinya jika
 * siswa yang dipilih sudah aktif di KTP lain, riwayat lamanya otomatis
 * ditutup status 'keluar' (bukan error/penolakan). Perlu dikonfirmasi
 * apakah perilaku pindah-kelas-implisit ini yang diinginkan di titik
 * masuk ini juga, atau harus menolak siswa yang sudah punya KTP aktif.
 */
class SiswaAktifRelationManager extends RelationManager
{
    protected static string $relationship = 'siswaAktif';

    protected static ?string $title = 'Siswa Aktif';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('nisn')->label('NISN')->searchable(),
                TextColumn::make('status_akademik')->badge(),
            ])
            ->headerActions([
                Action::make('tambah_siswa')
                    ->label('Tambah Siswa')
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Select::make('user_ids')
                            ->label('Pilih Siswa')
                            ->multiple()
                            ->searchable()
                            ->options(
                                User::query()
                                    ->where('role', RoleUser::Siswa)
                                    ->pluck('nama', 'id')
                            )
                            ->required()
                            ->helperText('Siswa yang sudah aktif di kelas lain akan otomatis dipindahkan ke kelas ini.'),
                    ])
                    ->action(function (array $data) {
                        /** @var KelasTahunPelajaran $ktp */
                        $ktp = $this->getOwnerRecord();
                        $service = app(KenaikanKelasService::class);

                        User::query()->whereIn('id', $data['user_ids'])
                            ->get()
                            ->each(fn (User $user) => $service->assignKelas($user, $ktp));

                        Notification::make()
                            ->success()
                            ->title(count($data['user_ids']).' siswa berhasil ditambahkan ke kelas.')
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('keluarkan')
                    ->label('Keluarkan dari Kelas')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Siswa akan dilepas dari kelas ini dan status akademik berubah menjadi Keluar. Aksi ini tercatat di riwayat.')
                    ->action(function (User $record) {
                        app(KenaikanKelasService::class)->keluarkanDariKelas($record);

                        Notification::make()
                            ->success()
                            ->title("{$record->nama} dikeluarkan dari kelas.")
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}

```
---

## app/Filament/Resources/KunjunganResource/Pages/ListKunjungans.php
```php
<?php

namespace App\Filament\Resources\KunjunganResource\Pages;

use App\Filament\Resources\KunjunganResource;
use Filament\Resources\Pages\ListRecords;

class ListKunjungans extends ListRecords
{
    protected static string $resource = KunjunganResource::class;
}

```
---

## app/Filament/Resources/KunjunganResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Enums\SourceKunjungan;
use App\Filament\Exports\KunjunganExporter;
use App\Filament\Resources\KunjunganResource\Pages;
use App\Models\Kunjungan;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Kunjungan HANYA hasil sinkronisasi device RFID (atau input manual oleh
 * Pustakawan di luar sistem ini - lihat SourceKunjungan::Manual, belum ada
 * UI-nya). Tidak ada Create/Edit page - murni log read-only, Admin boleh
 * Delete untuk koreksi data salah (dikonfirmasi). Tidak ada halaman View
 * terpisah karena semua field sudah tampil penuh di tabel (Aturan poin 6 -
 * hindari file yang tidak menambah nilai).
 */
class KunjunganResource extends Resource
{
    protected static ?string $model = Kunjungan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationLabel = 'Kunjungan';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(KunjunganExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Kunjungan::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')
                    ->label('Pengunjung')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('jam_tap')
                    ->time()
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn (SourceKunjungan $state) => match ($state) {
                        SourceKunjungan::Rfid => 'info',
                        SourceKunjungan::Manual => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->options(collect(SourceKunjungan::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])),
            ])
            ->recordActions([
                DeleteAction::make(), // digerbang KunjunganPolicy::delete() - hanya Admin
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKunjungans::route('/'),
        ];
    }
}

```
---

## app/Filament/Resources/LevelBadgeLogResource/Pages/ListLevelBadgeLogs.php
```php
<?php

namespace App\Filament\Resources\LevelBadgeLogResource\Pages;

use App\Filament\Resources\LevelBadgeLogResource;
use Filament\Resources\Pages\ListRecords;

class ListLevelBadgeLogs extends ListRecords
{
    protected static string $resource = LevelBadgeLogResource::class;
}

```
---

## app/Filament/Resources/LevelBadgeLogResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\LevelBadgeLogExporter;
use App\Filament\Resources\LevelBadgeLogResource\Pages;
use App\Models\LevelBadgeLog;
use Filament\Actions\ExportAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only - LevelBadgeLog HANYA dihasilkan otomatis oleh
 * PointService::cekBadge() saat badge user berubah (Aturan poin 3, DRY).
 * Tidak ada Import - insert manual akan melewati validasi rentang
 * min_point/max_point di PointService. Pola identik dengan
 * RewardLogResource/PunishmentLogResource.
 */
class LevelBadgeLogResource extends Resource
{
    protected static ?string $model = LevelBadgeLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Riwayat Badge';

    protected static string|\UnitEnum|null $navigationGroup = 'Poin & Reward';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(LevelBadgeLogExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', LevelBadgeLog::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')->label('User')->searchable()->sortable(),
                TextColumn::make('levelBadge.nama_badge')->label('Badge')->searchable()->sortable(),
                TextColumn::make('tanggal_didapat')->dateTime('d F Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('level_badge_id')->label('Badge')->relationship('levelBadge', 'nama_badge'),
            ])
            ->defaultSort('tanggal_didapat', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLevelBadgeLogs::route('/'),
        ];
    }
}

```
---

## app/Filament/Resources/LevelBadgeResource/Pages/CreateLevelBadge.php
```php
<?php

namespace App\Filament\Resources\LevelBadgeResource\Pages;

use App\Filament\Resources\LevelBadgeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLevelBadge extends CreateRecord
{
    protected static string $resource = LevelBadgeResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/LevelBadgeResource/Pages/EditLevelBadge.php
```php
<?php

namespace App\Filament\Resources\LevelBadgeResource\Pages;

use App\Filament\Resources\LevelBadgeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLevelBadge extends EditRecord
{
    protected static string $resource = LevelBadgeResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

```
---

## app/Filament/Resources/LevelBadgeResource/Pages/ListLevelBadges.php
```php
<?php

namespace App\Filament\Resources\LevelBadgeResource\Pages;

use App\Filament\Resources\LevelBadgeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLevelBadges extends ListRecords
{
    protected static string $resource = LevelBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

```
---

## app/Filament/Resources/LevelBadgeResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\LevelBadgeExporter;
use App\Filament\Imports\LevelBadgeImporter;
use App\Filament\Resources\LevelBadgeResource\Pages;
use App\Models\LevelBadge;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LevelBadgeResource extends Resource
{
    protected static ?string $model = LevelBadge::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Level Badge';

    protected static string|\UnitEnum|null $navigationGroup = 'Poin & Reward';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama_badge')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('min_point')
                ->numeric()
                ->integer()
                ->required(),
            TextInput::make('max_point')
                ->numeric()
                ->integer()
                ->helperText('Kosongkan jika badge tertinggi (tidak ada batas atas).'),
            TextInput::make('urutan')
                ->numeric()
                ->integer()
                ->default(0)
                ->helperText('Dipakai untuk urutan tampilan, bukan urutan threshold.'),
            FileUpload::make('icon')
                ->image()
                ->directory('level-badge-icon'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(LevelBadgeImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', LevelBadge::class) ?? false),
                ExportAction::make()
                    ->exporter(LevelBadgeExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', LevelBadge::class) ?? false),
            ])
            ->columns([
                ImageColumn::make('icon')->circular(),
                TextColumn::make('nama_badge')->searchable()->sortable(),
                TextColumn::make('min_point')->sortable(),
                TextColumn::make('max_point')->sortable()->placeholder('Tanpa batas atas'),
                TextColumn::make('urutan')->sortable(),
            ])
            ->defaultSort('urutan')
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLevelBadges::route('/'),
            'create' => Pages\CreateLevelBadge::route('/create'),
            'edit' => Pages\EditLevelBadge::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Resources/PeminjamanResource/Pages/CreatePeminjaman.php
```php
<?php

namespace App\Filament\Resources\PeminjamanResource\Pages;

use App\Filament\Resources\PeminjamanResource;
use App\Models\Peminjaman;
use App\Models\User;
use App\Services\PeminjamanService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreatePeminjaman extends CreateRecord
{
    protected static string $resource = PeminjamanResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    /**
     * Override total - TIDAK memakai Peminjaman::create($data) bawaan
     * Filament. Seluruh proses (validasi limit/suspend, stok, jatuh tempo,
     * Point, WA) wajib lewat PeminjamanService::pinjamBuku() (Aturan poin 3).
     *
     * $data berisi 'user_id' dan 'buku_ids' (array) dari form - bukan kolom
     * asli tabel peminjamans, jadi tidak bisa diserahkan ke Model::create().
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            $transaksi = app(PeminjamanService::class)->pinjamBuku(
                user: User::findOrFail($data['user_id']),
                eksemplarIds: $data['eksemplar_ids'],
                diprosesOleh: auth()->user(),
            );

            return $transaksi->peminjamans->first();
        } catch (RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('Gagal memproses peminjaman')
                ->body($e->getMessage())
                ->send();

            $this->halt();
        }
    }
}

```
---

## app/Filament/Resources/PeminjamanResource/Pages/ListPeminjamans.php
```php
<?php

namespace App\Filament\Resources\PeminjamanResource\Pages;

use App\Filament\Resources\PeminjamanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPeminjamans extends ListRecords
{
    protected static string $resource = PeminjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Input Peminjaman Manual'),
        ];
    }
}

```
---

## app/Filament/Resources/PeminjamanResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Enums\KondisiBuku;
use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Filament\Exports\PeminjamanExporter;
use App\Filament\Resources\PeminjamanResource\Pages;
use App\Models\Eksemplar;
use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use RuntimeException;

/**
 * Form Create di sini adalah FALLBACK MANUAL untuk Pustakawan (input lewat
 * panel jika device RFID/scan barcode error) - lihat konfirmasi Aturan.
 * Alur normal tetap lewat endpoint device RFID + scan barcode fisik.
 *
 * Create SENGAJA tidak memakai Peminjaman::create() bawaan Filament -
 * seluruh logic (validasi limit/suspend, kalkulasi jatuh tempo, stok, Point,
 * WA) WAJIB lewat PeminjamanService::pinjamBuku() (Aturan poin 3, DRY).
 * Lihat Pages\CreatePeminjaman::handleRecordCreation().
 *
 * Status Peminjaman TIDAK bisa diedit manual - transisi hanya lewat
 * PeminjamanService (cron/Action pengembalian/laporkan hilang), karenanya
 * Resource ini TIDAK punya halaman Edit sama sekali.
 */
class PeminjamanResource extends Resource
{
    protected static ?string $model = Peminjaman::class;

    protected static ?string $navigationLabel = 'Peminjaman';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Peminjam')
                ->relationship('user', 'nama')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('eksemplar_ids')
                ->label('Eksemplar (scan barcode / pilih)')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(fn() => Eksemplar::query()
                    ->where('status', StatusEksemplar::Tersedia)
                    ->with('buku')
                    ->get()
                    ->mapWithKeys(fn($e) => [$e->id => "{$e->buku->judul} — {$e->barcode}"]))
                ->helperText('Hanya menampilkan eksemplar berstatus tersedia. Validasi limit peminjaman aktif & status suspend dicek otomatis saat submit.')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(PeminjamanExporter::class)
                    ->authorize(fn() => auth()->user()?->can('viewAny', Peminjaman::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')
                    ->label('Peminjam')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('eksemplar.buku.judul')
                    ->label('Buku')
                    ->placeholder('(eksemplar sudah dihapus permanen)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_pinjam')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('tanggal_jatuh_tempo')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(StatusPeminjaman $state) => match ($state) {
                        StatusPeminjaman::Aktif => 'success',
                        StatusPeminjaman::Terlambat => 'danger',
                        StatusPeminjaman::Selesai => 'gray',
                        StatusPeminjaman::Hilang => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(StatusPeminjaman::cases())->mapWithKeys(fn($s) => [$s->value => ucfirst($s->value)])),
            ])
            ->recordActions([
                Action::make('proses_pengembalian')
                    ->label('Proses Pengembalian')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->authorize(fn(Peminjaman $record) => auth()->user()?->can('update', $record) ?? false)
                    ->visible(fn(Peminjaman $record) => in_array($record->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true))
                    ->visible(fn(Peminjaman $record) => in_array($record->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true))
                    ->schema([
                        Select::make('kondisi')
                            ->label('Kondisi Buku')
                            ->options(collect(KondisiBuku::cases())->mapWithKeys(fn($k) => [$k->value => ucfirst($k->value)]))
                            ->required(),
                        Textarea::make('catatan')
                            ->label('Catatan'),
                    ])
                    ->action(function (Peminjaman $record, array $data) {
                        try {
                            app(PeminjamanService::class)->prosesPengembalian(
                                peminjaman: $record,
                                kondisi: KondisiBuku::from($data['kondisi']),
                                catatan: $data['catatan'] ?? null,
                                diprosesOleh: auth()->user(),
                            );

                            Notification::make()
                                ->success()
                                ->title('Pengembalian berhasil diproses')
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal memproses pengembalian')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('laporkan_hilang')
                    ->label('Laporkan Hilang')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->authorize(fn(Peminjaman $record) => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->requiresConfirmation()
                    ->modalDescription('Buku belum dikembalikan secara fisik. Denda kehilangan penuh (Buku.harga_ganti) akan langsung dicatat.')
                    ->visible(fn(Peminjaman $record) => in_array($record->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true))
                    ->action(function (Peminjaman $record) {
                        try {
                            app(PeminjamanService::class)->laporkanHilang($record);

                            Notification::make()
                                ->success()
                                ->title('Peminjaman ditandai hilang, Denda tercatat')
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal melaporkan hilang')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPeminjamans::route('/'),
            'create' => Pages\CreatePeminjaman::route('/create'),
        ];
    }
}

```
---

## app/Filament/Resources/PengembalianResource/Pages/ListPengembalians.php
```php
<?php

namespace App\Filament\Resources\PengembalianResource\Pages;

use App\Filament\Resources\PengembalianResource;
use Filament\Resources\Pages\ListRecords;

class ListPengembalians extends ListRecords
{
    protected static string $resource = PengembalianResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/PengembalianResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Enums\KondisiBuku;
use App\Filament\Exports\PengembalianExporter;
use App\Filament\Resources\PengembalianResource\Pages;
use App\Models\Pengembalian;
use App\Services\PeminjamanService;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use RuntimeException;

/**
 * Tidak ada halaman Create/Edit - Pengembalian adalah HASIL dari
 * PeminjamanService::prosesPengembalian() (dipicu Action "Proses
 * Pengembalian" di PeminjamanResource).
 *
 * SATU pengecualian: Action "Koreksi Kondisi" di tabel ini, untuk kasus
 * Pustakawan salah input kondisi saat transaksi cepat (lihat gap iterasi
 * ini). Sengaja berupa Action terbatas (bukan Edit page penuh) - field yang
 * bisa diubah cuma 'kondisi' + 'catatan', supaya lebih mudah di-audit.
 * Seluruh efek samping (stok, Denda, status Peminjaman) wajib lewat
 * PeminjamanService::koreksiKondisiPengembalian() (Aturan poin 3, DRY).
 *
 * RESOLVED (iterasi ini): permission 'Update:Pengembalian' untuk role
 * Pustakawan dan Admin (super_admin) SUDAH ada di ShieldSeeder - dicek
 * ulang, TODO sebelumnya sudah basi/tidak perlu tindakan lanjutan.
 */
class PengembalianResource extends Resource
{
    protected static ?string $model = Pengembalian::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Pengembalian';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(PengembalianExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Pengembalian::class) ?? false),
            ])
            ->columns([
                TextColumn::make('peminjaman.user.nama')
                    ->label('Peminjam')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('peminjaman.eksemplar.buku.judul')
                    ->label('Buku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_kembali')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('kondisi')
                    ->badge()
                    ->color(fn (KondisiBuku $state) => match ($state) {
                        KondisiBuku::Baik => 'success',
                        KondisiBuku::Rusak => 'warning',
                        KondisiBuku::Hilang => 'danger',
                    }),
                TextColumn::make('catatan')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('diprosesOleh.nama')
                    ->label('Diproses Oleh')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('kondisi')
                    ->options(collect(KondisiBuku::cases())->mapWithKeys(fn ($k) => [$k->value => ucfirst($k->value)])),
            ])
            ->recordActions([
                Action::make('koreksi_kondisi')
                    ->label('Koreksi Kondisi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->authorize(fn (Pengembalian $record) => auth()->user()?->can('update', $record) ?? false)
                    // dijaga PengembalianPolicy - lihat TODO ShieldSeeder di atas
                    ->requiresConfirmation()
                    ->modalDescription('Mengubah kondisi akan otomatis menyesuaikan stok dan Denda terkait (batalkan Denda lama, catat Denda baru jika perlu). Ini tidak bisa dibatalkan lewat tombol undo.')
                    ->schema(fn (Pengembalian $record) => [
                        Select::make('kondisi_baru')
                            ->label('Kondisi Baru')
                            ->options(
                                collect(KondisiBuku::cases())
                                    ->reject(fn ($k) => $k === $record->kondisi)
                                    ->mapWithKeys(fn ($k) => [$k->value => ucfirst($k->value)])
                            )
                            ->required(),
                        Textarea::make('catatan')
                            ->label('Catatan Koreksi')
                            ->default($record->catatan),
                    ])
                    ->action(function (Pengembalian $record, array $data) {
                        try {
                            app(PeminjamanService::class)->koreksiKondisiPengembalian(
                                pengembalian: $record,
                                kondisiBaru: KondisiBuku::from($data['kondisi_baru']),
                                catatan: $data['catatan'] ?? null,
                                diprosesOleh: auth()->user(),
                            );

                            Notification::make()
                                ->success()
                                ->title('Kondisi berhasil dikoreksi')
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal mengoreksi kondisi')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengembalians::route('/'),
        ];
    }
}

```
---

## app/Filament/Resources/PunishmentLogResource/Pages/ListPunishmentLogs.php
```php
<?php

namespace App\Filament\Resources\PunishmentLogResource\Pages;

use App\Filament\Resources\PunishmentLogResource;
use Filament\Resources\Pages\ListRecords;

class ListPunishmentLogs extends ListRecords
{
    protected static string $resource = PunishmentLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

```
---

## app/Filament/Resources/PunishmentLogResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PunishmentLogExporter;
use App\Filament\Resources\PunishmentLogResource\Pages;
use App\Models\PunishmentLog;
use Filament\Actions\ExportAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * // TODO: ASUMSI - lihat catatan sama di RewardLogResource.
 * Read-only, tanpa Import - dihasilkan otomatis oleh PointService.
 */
class PunishmentLogResource extends Resource
{
    protected static ?string $model = PunishmentLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Riwayat Punishment';

    protected static string|\UnitEnum|null $navigationGroup = 'Poin & Reward';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(PunishmentLogExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', PunishmentLog::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')->label('User')->searchable()->sortable(),
                TextColumn::make('punishment.nama')->label('Punishment')->searchable()->sortable(),
                TextColumn::make('tanggal_diterapkan')->dateTime('d F Y H:i')->sortable(),
                TextColumn::make('tanggal_berakhir')->dateTime('d F Y H:i')->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('punishment_id')->label('Punishment')->relationship('punishment', 'nama'),
            ])
            ->defaultSort('tanggal_diterapkan', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPunishmentLogs::route('/'),
        ];
    }
}

```
---

## app/Filament/Resources/PunishmentResource/Pages/CreatePunishment.php
```php
<?php

namespace App\Filament\Resources\PunishmentResource\Pages;

use App\Filament\Resources\PunishmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePunishment extends CreateRecord
{
    protected static string $resource = PunishmentResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/PunishmentResource/Pages/EditPunishment.php
```php
<?php

namespace App\Filament\Resources\PunishmentResource\Pages;

use App\Filament\Resources\PunishmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPunishment extends EditRecord
{
    protected static string $resource = PunishmentResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

```
---

## app/Filament/Resources/PunishmentResource/Pages/ListPunishments.php
```php
<?php

namespace App\Filament\Resources\PunishmentResource\Pages;

use App\Filament\Resources\PunishmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPunishments extends ListRecords
{
    protected static string $resource = PunishmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

```
---

## app/Filament/Resources/PunishmentResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PunishmentExporter;
use App\Filament\Imports\PunishmentImporter;
use App\Filament\Resources\PunishmentResource\Pages;
use App\Models\Punishment;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PunishmentResource extends Resource
{
    protected static ?string $model = Punishment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationLabel = 'Punishment';

    protected static string|\UnitEnum|null $navigationGroup = 'Poin & Reward';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Textarea::make('deskripsi')
                ->columnSpanFull(),
            TextInput::make('threshold_point_minus')
                ->numeric()
                ->integer()
                ->maxValue(0)
                ->required()
                ->helperText('Nilai negatif - akumulasi point <= nilai ini akan memicu punishment.'),
            TextInput::make('durasi_suspend_hari')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->helperText('Kosongkan jika punishment tidak memicu suspend otomatis.'),
            Toggle::make('aktif')
                ->default(true)
                ->helperText('Punishment nonaktif tidak akan dicek/direalisasikan lagi oleh PointService.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(PunishmentImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Punishment::class) ?? false),
                ExportAction::make()
                    ->exporter(PunishmentExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Punishment::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('threshold_point_minus')->sortable(),
                TextColumn::make('durasi_suspend_hari')->placeholder('-'),
                IconColumn::make('aktif')->boolean(),
                TextColumn::make('punishment_logs_count')->label('Jumlah Diterapkan')->counts('punishmentLogs'),
            ])
            ->filters([TernaryFilter::make('aktif')])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPunishments::route('/'),
            'create' => Pages\CreatePunishment::route('/create'),
            'edit' => Pages\EditPunishment::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Resources/RakResource/Pages/CreateRak.php
```php
<?php

namespace App\Filament\Resources\RakResource\Pages;

use App\Filament\Resources\RakResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRak extends CreateRecord
{
    protected static string $resource = RakResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/RakResource/Pages/EditRak.php
```php
<?php

namespace App\Filament\Resources\RakResource\Pages;

use App\Filament\Resources\RakResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRak extends EditRecord
{
    protected static string $resource = RakResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/RakResource/Pages/ListRaks.php
```php
<?php

namespace App\Filament\Resources\RakResource\Pages;

use App\Filament\Resources\RakResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRaks extends ListRecords
{
    protected static string $resource = RakResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

```
---

## app/Filament/Resources/RakResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\RakExporter;
use App\Filament\Imports\RakImporter;
use App\Filament\Resources\RakResource\Pages;
use App\Filament\Resources\RakResource\RelationManagers\EksemplarsRelationManager;
use App\Models\Rak;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RakResource extends Resource
{
    protected static ?string $model = Rak::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Rak';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->required()
                ->maxLength(255),
            TextInput::make('lokasi')
                ->maxLength(255),
            Select::make('kategoris')
                ->label('Kategori Terkait')
                ->relationship('kategoris', 'nama')
                ->multiple()
                ->preload()
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(RakImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Rak::class) ?? false),
                ExportAction::make()
                    ->exporter(RakExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Rak::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lokasi')
                    ->searchable(),
                // dulu counts('bukus') - kolom bukus.rak_id sudah tidak
                // ada, jadi dihitung dari eksemplars (lihat Rak::eksemplars()).
                TextColumn::make('eksemplars_count')
                    ->label('Jumlah Eksemplar')
                    ->counts('eksemplars')
                    ->sortable(),
                // GAP-SPEC ditutup: stok tersedia per rak, lihat
                // Rak::stokTersedia() - definisi sama dengan Buku::stokTersedia().
                TextColumn::make('stok_tersedia')
                    ->label('Stok Tersedia')
                    ->state(fn (Rak $record) => $record->stokTersedia())
                    ->badge()
                    ->color(fn (Rak $record) => $record->stokTersedia() > 0 ? 'success' : 'danger'),
                // judul unik, lihat Rak::jumlahJudulUnik(). Bukan hasil
                // counts() bawaan (butuh distinct buku_id), jadi dihitung
                // manual per baris - toggleable & default hidden supaya
                // tidak mengubah tampilan existing.
                TextColumn::make('jumlah_judul_unik')
                    ->label('Jumlah Judul Unik')
                    ->state(fn (Rak $record) => $record->jumlahJudulUnik())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EksemplarsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRaks::route('/'),
            'create' => Pages\CreateRak::route('/create'),
            'edit' => Pages\EditRak::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Resources/RakResource/RelationManagers/EksemplarsRelationManager.php
```php
<?php

namespace App\Filament\Resources\RakResource\RelationManagers;

use App\Enums\StatusEksemplar;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Rak -> Eksemplar adalah relasi one-to-many ASLI (Eksemplar.rak_id).
 * Rak TIDAK lagi berelasi langsung ke Buku (lihat migration
 * 2026_08_02_000003 dan 2026_08_02_000002) - satu judul Buku bisa punya
 * banyak Eksemplar tersebar di rak berbeda.
 *
 * RelationManager ini murni untuk LIHAT eksemplar yang ada di rak ini -
 * pindah rak / ubah status eksemplar tetap lewat BukuResource >
 * EksemplarsRelationManager langsung (satu sumber kebenaran, Aturan
 * poin 3 - DRY), supaya tidak ada dua tempat berbeda yang bisa mengubah
 * data Eksemplar yang sama.
 */
class EksemplarsRelationManager extends RelationManager
{
    protected static string $relationship = 'eksemplars';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('barcode')
            ->columns([
                TextColumn::make('buku.judul')
                    ->label('Judul Buku')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (StatusEksemplar $state) => $state->value),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}

```
---

## app/Filament/Resources/RewardLogResource/Pages/ListRewardLogs.php
```php
<?php

namespace App\Filament\Resources\RewardLogResource\Pages;

use App\Filament\Resources\RewardLogResource;
use Filament\Resources\Pages\ListRecords;

class ListRewardLogs extends ListRecords
{
    protected static string $resource = RewardLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

```
---

## app/Filament/Resources/RewardLogResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\RewardLogExporter;
use App\Filament\Resources\RewardLogResource\Pages;
use App\Models\RewardLog;
use Filament\Actions\ExportAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * // TODO: ASUMSI - dibuat sebagai Resource terpisah (bukan RelationManager
 * di UserResource), mengikuti pola RiwayatKelasSiswaResource. Jika Anda
 * lebih suka tab "Riwayat Reward" langsung di form/edit User, beri tahu -
 * mudah dipindah karena logic query-nya identik.
 *
 * Read-only - RewardLog HANYA dihasilkan otomatis oleh PointService saat
 * threshold tercapai (Aturan poin 3, DRY). Tidak ada Import - insert
 * manual lewat spreadsheet akan melewati validasi threshold PointService.
 */
class RewardLogResource extends Resource
{
    protected static ?string $model = RewardLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Riwayat Reward';

    protected static string|\UnitEnum|null $navigationGroup = 'Poin & Reward';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(RewardLogExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', RewardLog::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')->label('User')->searchable()->sortable(),
                TextColumn::make('reward.nama')->label('Reward')->searchable()->sortable(),
                TextColumn::make('tanggal_didapat')->dateTime('d F Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('reward_id')->label('Reward')->relationship('reward', 'nama'),
            ])
            ->defaultSort('tanggal_didapat', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRewardLogs::route('/'),
        ];
    }
}

```
---

## app/Filament/Resources/RewardResource/Pages/CreateReward.php
```php
<?php

namespace App\Filament\Resources\RewardResource\Pages;

use App\Filament\Resources\RewardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReward extends CreateRecord
{
    protected static string $resource = RewardResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/RewardResource/Pages/EditReward.php
```php
<?php

namespace App\Filament\Resources\RewardResource\Pages;

use App\Filament\Resources\RewardResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReward extends EditRecord
{
    protected static string $resource = RewardResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

```
---

## app/Filament/Resources/RewardResource/Pages/ListRewards.php
```php
<?php

namespace App\Filament\Resources\RewardResource\Pages;

use App\Filament\Resources\RewardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRewards extends ListRecords
{
    protected static string $resource = RewardResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

```
---

## app/Filament/Resources/RewardResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\RewardExporter;
use App\Filament\Imports\RewardImporter;
use App\Filament\Resources\RewardResource\Pages;
use App\Models\Reward;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RewardResource extends Resource
{
    protected static ?string $model = Reward::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Reward';

    protected static string|\UnitEnum|null $navigationGroup = 'Poin & Reward';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Textarea::make('deskripsi')
                ->columnSpanFull(),
            TextInput::make('threshold_point')
                ->numeric()
                ->integer()
                ->required(),
            Toggle::make('aktif')
                ->default(true)
                ->helperText('Reward nonaktif tidak akan dicek/direalisasikan lagi oleh PointService.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(RewardImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Reward::class) ?? false),
                ExportAction::make()
                    ->exporter(RewardExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Reward::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('threshold_point')->sortable(),
                IconColumn::make('aktif')->boolean(),
                TextColumn::make('reward_logs_count')->label('Jumlah Diperoleh')->counts('rewardLogs'),
            ])
            ->filters([TernaryFilter::make('aktif')])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRewards::route('/'),
            'create' => Pages\CreateReward::route('/create'),
            'edit' => Pages\EditReward::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Resources/RiwayatKelasSiswaResource/Pages/ListRiwayatKelasSiswas.php
```php
<?php

namespace App\Filament\Resources\RiwayatKelasSiswaResource\Pages;

use App\Filament\Resources\RiwayatKelasSiswaResource;
use Filament\Resources\Pages\ListRecords;

class ListRiwayatKelasSiswas extends ListRecords
{
    protected static string $resource = RiwayatKelasSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

```
---

## app/Filament/Resources/RiwayatKelasSiswaResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Enums\StatusRiwayatKelas;
use App\Filament\Resources\RiwayatKelasSiswaResource\Pages;
use App\Models\RiwayatKelasSiswa;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only - histori kenaikan/perpindahan kelas siswa. Tidak ada
 * form/create/edit karena data ini hanya dihasilkan otomatis oleh
 * KenaikanKelasService (Aturan poin 3, DRY - satu sumber kebenaran).
 *
 * RESOLVED (iterasi ini): dicek ulang ke ShieldSeeder, permission
 * 'ViewAny:RiwayatKelasSiswa' dan 'View:RiwayatKelasSiswa' SUDAH
 * diberikan ke role Pustakawan. TODO sebelumnya sudah basi.
 */
class RiwayatKelasSiswaResource extends Resource
{
    protected static ?string $model = RiwayatKelasSiswa::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Riwayat Kelas Siswa';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nama')->label('Siswa')->searchable()->sortable(),
                TextColumn::make('user.nisn')->label('NISN')->searchable(),
                TextColumn::make('kelasTahunPelajaran.kelas.nama')->label('Kelas')->sortable(),
                TextColumn::make('kelasTahunPelajaran.tahunPelajaran.nama')->label('Tahun Pelajaran')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (StatusRiwayatKelas $state) => match ($state) {
                        StatusRiwayatKelas::Aktif => 'success',
                        StatusRiwayatKelas::Naik => 'info',
                        StatusRiwayatKelas::Tinggal => 'warning',
                        StatusRiwayatKelas::Lulus => 'primary',
                        StatusRiwayatKelas::Keluar => 'gray',
                    }),
                TextColumn::make('tanggal_mulai')->date('d F Y')->sortable(),
                TextColumn::make('tanggal_selesai')->date('d F Y')->sortable()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(StatusRiwayatKelas::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRiwayatKelasSiswas::route('/'),
        ];
    }
}

```
---

## app/Filament/Resources/TahunPelajaranResource/Pages/CreateTahunPelajaran.php
```php
<?php

namespace App\Filament\Resources\TahunPelajaranResource\Pages;

use App\Filament\Resources\TahunPelajaranResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTahunPelajaran extends CreateRecord
{
    protected static string $resource = TahunPelajaranResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

```
---

## app/Filament/Resources/TahunPelajaranResource/Pages/EditTahunPelajaran.php
```php
<?php

namespace App\Filament\Resources\TahunPelajaranResource\Pages;

use App\Filament\Resources\TahunPelajaranResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTahunPelajaran extends EditRecord
{
    protected static string $resource = TahunPelajaranResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

```
---

## app/Filament/Resources/TahunPelajaranResource/Pages/ListTahunPelajarans.php
```php
<?php

namespace App\Filament\Resources\TahunPelajaranResource\Pages;

use App\Filament\Resources\TahunPelajaranResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTahunPelajarans extends ListRecords
{
    protected static string $resource = TahunPelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

```
---

## app/Filament/Resources/TahunPelajaranResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Filament\Exports\TahunPelajaranExporter;
use App\Filament\Imports\TahunPelajaranImporter;
use App\Filament\Resources\TahunPelajaranResource\Pages;
use App\Models\TahunPelajaran;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TahunPelajaranResource extends Resource
{
    protected static ?string $model = TahunPelajaran::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Tahun Pelajaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama (mis. 2025/2026)')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            DatePicker::make('tanggal_mulai')->required(),
            DatePicker::make('tanggal_selesai')->required()->afterOrEqual('tanggal_mulai'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(TahunPelajaranImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', TahunPelajaran::class) ?? false),
                ExportAction::make()
                    ->exporter(TahunPelajaranExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', TahunPelajaran::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('tanggal_mulai')->date('d F Y'),
                TextColumn::make('tanggal_selesai')->date('d F Y'),
                IconColumn::make('aktif')->boolean()->label('Aktif'),
            ])
            ->recordActions([
                Action::make('jadikan_aktif')
                    ->label('Jadikan Aktif')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (TahunPelajaran $record) => ! $record->aktif)
                    ->requiresConfirmation()
                    ->modalDescription('Tahun Pelajaran lain yang sedang aktif akan otomatis dinonaktifkan.')
                    ->action(function (TahunPelajaran $record) {
                        TahunPelajaran::query()->where('id', '!=', $record->id)->update(['aktif' => false]);
                        $record->update(['aktif' => true]);

                        Notification::make()->success()->title('Tahun Pelajaran diaktifkan')->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (TahunPelajaran $record) => ! $record->aktif),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTahunPelajarans::route('/'),
            'create' => Pages\CreateTahunPelajaran::route('/create'),
            'edit' => Pages\EditTahunPelajaran::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Resources/TransaksiResource/Pages/ListTransaksis.php
```php
<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use Filament\Resources\Pages\ListRecords;

class ListTransaksis extends ListRecords
{
    protected static string $resource = TransaksiResource::class;
}

```
---

## app/Filament/Resources/TransaksiResource/Pages/ViewTransaksi.php
```php
<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Filament\Resources\TransaksiResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTransaksi extends ViewRecord
{
    protected static string $resource = TransaksiResource::class;
}

```
---

## app/Filament/Resources/TransaksiResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Enums\JenisTransaksi;
use App\Filament\Exports\TransaksiExporter;
use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\RelationManagers\PeminjamansRelationManager;
use App\Models\Transaksi;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Transaksi dibuat otomatis sebagai pembungkus proses (peminjaman/
 * kunjungan/pembayaran_denda) - tidak ada Create/Edit manual di sini.
 * Read-only log + Admin boleh Delete untuk koreksi (dikonfirmasi).
 *
 * TODO: GAP-SPEC - belum ditemukan kode yang membuat Transaksi dengan
 * jenis 'kunjungan' atau 'pembayaran_denda' (PeminjamanService hanya
 * terlihat menangani jenis 'peminjaman' lewat pinjamBuku()). Kemungkinan
 * dua jenis ini memang belum diimplementasikan - perlu dikonfirmasi apakah
 * ini scope iterasi selanjutnya, bukan gap di TransaksiResource ini.
 */
class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(TransaksiExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Transaksi::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jenis')
                    ->badge()
                    ->color(fn (JenisTransaksi $state) => match ($state) {
                        JenisTransaksi::Peminjaman => 'info',
                        JenisTransaksi::Kunjungan => 'gray',
                        JenisTransaksi::PembayaranDenda => 'success',
                    }),
                TextColumn::make('diprosesOleh.nama')
                    ->label('Diproses Oleh')
                    ->toggleable(),
                TextColumn::make('tanggal')
                    ->dateTime('d F Y H:i')
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->options(collect(JenisTransaksi::cases())->mapWithKeys(fn ($j) => [$j->value => ucfirst(str_replace('_', ' ', $j->value))])),
            ])
            ->recordActions([
                DeleteAction::make(), // digerbang TransaksiPolicy::delete() - hanya Admin
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            PeminjamansRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksis::route('/'),
            'view' => Pages\ViewTransaksi::route('/{record}'),
        ];
    }
}

```
---

## app/Filament/Resources/TransaksiResource/RelationManagers/PeminjamansRelationManager.php
```php
<?php

namespace App\Filament\Resources\TransaksiResource\RelationManagers;

use App\Enums\StatusPeminjaman;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only - Peminjaman diubah HANYA lewat PeminjamanResource/
 * PeminjamanService (Aturan poin 3), RelationManager ini murni untuk lihat
 * buku apa saja yang termasuk dalam satu Transaksi (mirip pola
 * RakResource\BukusRelationManager).
 */
class PeminjamansRelationManager extends RelationManager
{
    protected static string $relationship = 'peminjamans';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('eksemplar.buku.judul')
                    ->label('Buku')
                    ->placeholder('(eksemplar sudah dihapus permanen)')
                    ->searchable(),
                TextColumn::make('tanggal_jatuh_tempo')
                    ->date('d F Y'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (StatusPeminjaman $state) => match ($state) {
                        StatusPeminjaman::Aktif => 'success',
                        StatusPeminjaman::Terlambat => 'danger',
                        StatusPeminjaman::Selesai => 'gray',
                        StatusPeminjaman::Hilang => 'warning',
                    }),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

```
---

## app/Filament/Resources/UserResource/Pages/CreateUser.php
```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\KelasTahunPelajaran;
use App\Services\KenaikanKelasService;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    /**
     * Field 'assign_kelas_tahun_pelajaran_id' HANYA ada di form create
     * (lihat UserResource::form(), visibleOn('create')) - bukan kolom
     * User sungguhan, jadi wajib dibuang sebelum mass-assign, lalu
     * assignment dilakukan di afterCreate() lewat KenaikanKelasService
     * supaya RiwayatKelasSiswa tetap tercatat (Aturan poin 3, DRY).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->assignKtpId = $data['assign_kelas_tahun_pelajaran_id'] ?? null;

        unset($data['assign_kelas_tahun_pelajaran_id']);

        return $data;
    }

    protected ?string $assignKtpId = null;

    protected function afterCreate(): void
    {
        if (! $this->assignKtpId) {
            return;
        }

        $ktp = KelasTahunPelajaran::query()->find($this->assignKtpId);

        if ($ktp) {
            app(KenaikanKelasService::class)->assignKelas($this->record, $ktp);
        }
    }
}

```
---

## app/Filament/Resources/UserResource/Pages/EditUser.php
```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn ($record) => $record && $record->hasRole('super_admin')),
        ];
    }
}

```
---

## app/Filament/Resources/UserResource/Pages/ListUsers.php
```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

```
---

## app/Filament/Resources/UserResource.php
```php
<?php

namespace App\Filament\Resources;

use App\Enums\JenisKelamin;
use App\Enums\RoleUser;
use App\Enums\StatusAkademik;
use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use App\Filament\Resources\UserResource\Pages;
use App\Models\KelasTahunPelajaran;
use App\Models\User;
use App\Services\KenaikanKelasService;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/**
 * Resource khusus super_admin (dikonfirmasi) - lihat UserPolicy dan
 * ShieldSeeder (permission User TIDAK disinkron ke role pustakawan).
 *
 * TODO: verifikasi signature terhadap versi package yang terpasang -
 * mengikuti pola BukuResource untuk Schema/Table API Filament ^5.7.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'User';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->required()
                ->maxLength(255),
            Select::make('role')
                ->options(collect(RoleUser::cases())->mapWithKeys(fn ($r) => [$r->value => ucfirst(str_replace('_', ' ', $r->value))]))
                ->required(),
            TextInput::make('nisn')
                ->label('NISN')
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('nip')
                ->label('NIP')
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Select::make('jenis_kelamin')
                ->label('Jenis Kelamin')
                ->options(collect(JenisKelamin::cases())->mapWithKeys(fn ($j) => [$j->value => $j->label()]))
                ->native(false),
            // Kolom 'kelas' (string bebas) sudah di-drop dari tabel users
            // (migration 2026_08_01_000006), diganti relasi
            // kelas_tahun_pelajaran_id. Ditampilkan read-only di sini -
            // penetapan/perubahan kelas WAJIB lewat KenaikanKelasService
            // (bulk action 'assign_kelas' di tabel bawah, atau proses
            // kenaikan kelas massal) supaya RiwayatKelasSiswa selalu
            // tercatat. Form ini sengaja TIDAK menyediakan input langsung
            // untuk field ini agar tidak ada jalur kedua yang melewati
            // service (Aturan poin 3, DRY).
            // TODO: GAP-SPEC - pada 'create', user baru dibuat tanpa KTP
            // (kelas_tahun_pelajaran_id null, status_akademik default
            // 'aktif' dari migration). Assignment awal dilakukan setelah
            // user tersimpan, lewat bulk action 'assign_kelas' di index.
            // Perlu dikonfirmasi apakah alur ini sudah sesuai ekspektasi,
            // atau dibutuhkan Select assignment langsung di form create.

            Placeholder::make('kelas_tahun_pelajaran_id')
                ->label('Kelas (Tahun Pelajaran)')
                ->content(fn (?User $record) => $record?->kelasTahunPelajaran
                    ? "{$record->kelasTahunPelajaran->kelas->nama} - {$record->kelasTahunPelajaran->tahunPelajaran->nama}"
                    : 'Belum di-assign - gunakan aksi "Assign ke Kelas" di daftar User.')
                ->visibleOn('edit'),
            // Hanya tampil saat create - field virtual (bukan kolom User),
            // dibuang & diproses lewat KenaikanKelasService::assignKelas()
            // di CreateUser::afterCreate(). Assignment setelah create
            // (bukan saat edit) tetap konsisten dengan alur bulk action
            // 'assign_kelas' yang juga selalu lewat service ini.
            Select::make('assign_kelas_tahun_pelajaran_id')
                ->label('Assign ke Kelas (opsional)')
                ->options(
                    KelasTahunPelajaran::query()
                        ->with(['kelas', 'tahunPelajaran'])
                        ->get()
                        ->mapWithKeys(fn (KelasTahunPelajaran $ktp) => [
                            $ktp->id => "{$ktp->kelas->nama} - {$ktp->tahunPelajaran->nama}",
                        ])
                )
                ->searchable()
                ->helperText('Bisa dikosongkan, assign belakangan lewat aksi "Assign ke Kelas".')
                ->dehydrated()
                ->visibleOn('create'),
            Select::make('status_akademik')
                ->options(collect(StatusAkademik::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))]))
                ->disabled()
                ->dehydrated(false)
                ->helperText('Berubah otomatis lewat proses Kenaikan Kelas / assignment, tidak bisa diedit manual di sini.')
                ->visibleOn('edit'),
            TextInput::make('jabatan')
                ->maxLength(255),
            TextInput::make('no_telepon')
                ->label('No. Telepon')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('no_kartu_rfid')
                ->label('No. Kartu RFID')
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->maxLength(255)
                ->helperText('Kosongkan jika tidak ingin mengubah password.'),
            FileUpload::make('avatar')
                ->image()
                ->disk('public')
                ->directory('user-avatar'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(UserImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', User::class) ?? false),
                ExportAction::make()
                    ->exporter(UserExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', User::class) ?? false),
            ])
            ->columns([
                ImageColumn::make('avatar')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (RoleUser $state) => match ($state) {
                        RoleUser::Admin => 'danger',
                        RoleUser::Pustakawan => 'warning',
                        RoleUser::Pegawai => 'info',
                        RoleUser::Siswa => 'gray',
                    }),
                TextColumn::make('nisn')
                    ->label('NISN')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('kelasTahunPelajaran.kelas.nama')
                    ->label('Kelas')
                    ->toggleable()
                    ->placeholder('-'),
                TextColumn::make('status_akademik')
                    ->badge()
                    ->toggleable()
                    ->color(fn (StatusAkademik $state) => match ($state) {
                        StatusAkademik::Aktif => 'success',
                        StatusAkademik::Lulus => 'info',
                        StatusAkademik::Keluar => 'gray',
                    }),
                TextColumn::make('no_telepon')
                    ->label('No. Telepon')
                    ->searchable(),
                TextColumn::make('no_kartu_rfid')
                    ->label('Kartu RFID')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('status_suspend')
                    ->label('Suspend')
                    ->boolean()
                    // Dibalik dari default Filament - true (suspend) = merah
                    // (masalah), false (aman) = hijau. Default bawaan
                    // mewarnai false sebagai merah, keliru untuk flag ini.
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),
                TextColumn::make('akumulasi_point')
                    ->label('Point')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('role')
                    ->options(collect(RoleUser::cases())->mapWithKeys(fn ($r) => [$r->value => ucfirst(str_replace('_', ' ', $r->value))])),
                SelectFilter::make('status_akademik')
                    ->options(collect(StatusAkademik::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))])),
                TernaryFilter::make('status_suspend')
                    ->label('Status Suspend'),
            ])
            ->recordActions([
                DeleteAction::make()
                    // super_admin tidak boleh dihapus, termasuk oleh
                    // sesama super_admin - mencegah lock-out akun sistem.
                    ->authorize(fn (User $record) => ! $record->hasRole('super_admin')
                        && (auth()->user()?->can('delete', $record) ?? false)),
            ])
            ->toolbarActions([
                BulkAction::make('assign_kelas')
                    ->label('Assign ke Kelas')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Select::make('kelas_tahun_pelajaran_id')
                            ->label('Kelas (Tahun Pelajaran)')
                            ->options(
                                KelasTahunPelajaran::query()
                                    ->with(['kelas', 'tahunPelajaran'])
                                    ->get()
                                    ->mapWithKeys(fn (KelasTahunPelajaran $ktp) => [
                                        $ktp->id => "{$ktp->kelas->nama} -{$ktp->tahunPelajaran->nama}",
                                    ])
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $ktp = KelasTahunPelajaran::query()->findOrFail($data['kelas_tahun_pelajaran_id']);
                        $service = app(KenaikanKelasService::class);

                        $records->each(fn (User $user) => $service->assignKelas($user, $ktp));

                        Notification::make()
                            ->success()
                            ->title($records->count().' user berhasil di-assign ke kelas.')
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make()
                    // Filter record super_admin keluar dari proses bulk
                    // delete - baris super_admin yang ikut ter-select akan
                    // dilewati (tidak ikut terhapus), bukan meng-error-kan
                    // seluruh aksi.
                    ->action(function (Collection $records) {
                        $dilindungi = $records->filter(fn (User $u) => $u->hasRole('super_admin'));
                        $bolehHapus = $records->reject(fn (User $u) => $u->hasRole('super_admin'));

                        $bolehHapus->each->delete();

                        if ($dilindungi->isNotEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('Sebagian user tidak dihapus')
                                ->body($dilindungi->count().' user dengan role super_admin dilewati (tidak bisa dihapus lewat bulk delete).')
                                ->send();
                        }
                    })
                    ->authorize(fn () => auth()->user()?->can('deleteAny', User::class) ?? false),
            ])
            // Checkbox baris super_admin dinonaktifkan supaya tidak bisa
            // ikut ter-select sama sekali (lapisan pencegahan pertama,
            // sebelum sampai ke action() di atas).
            ->checkIfRecordIsSelectableUsing(fn (User $record) => ! $record->hasRole('super_admin'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

```
---

## app/Filament/Widgets/BukuPerKategoriWidget.php
```php
<?php

namespace App\Filament\Widgets;

use App\Models\Kategori;
use Filament\Widgets\ChartWidget;

/**
 * Jumlah judul Buku per Kategori - pakai withCount('bukus') (relasi
 * BelongsToMany langsung Kategori::bukus(), bukan hasManyThrough
 * eksemplars() yang menghitung EKSEMPLAR, bukan judul - beda makna,
 * jangan tertukar).
 */
class BukuPerKategoriWidget extends ChartWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Jumlah Judul Buku per Kategori';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getData(): array
    {
        $kategoris = Kategori::query()
            ->withCount('bukus')
            ->orderByDesc('bukus_count')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Judul Buku',
                    'data' => $kategoris->pluck('bukus_count')->all(),
                    'backgroundColor' => '#06b6d4',
                ],
            ],
            'labels' => $kategoris->pluck('nama')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}

```
---

## app/Filament/Widgets/BukuRusakHilangWidget.php
```php
<?php

namespace App\Filament\Widgets;

use App\Enums\StatusEksemplar;
use App\Models\Eksemplar;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Daftar Eksemplar dengan status Rusak/Hilang SAAT INI - sumber
 * kebenaran Eksemplar.status (Aturan poin 3, konsisten dengan
 * Buku::stokTersedia()), BUKAN histori Pengembalian.kondisi/Denda.tipe -
 * eksemplar yang sudah diperbaiki/dihapus setelah kejadian rusak/hilang
 * TIDAK akan muncul di sini, karena statusnya sudah berubah.
 */
class BukuRusakHilangWidget extends TableWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getTableHeading(): string
    {
        return 'Eksemplar Rusak & Hilang';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Eksemplar::query()
                    ->with('buku', 'rak')
                    ->whereIn('status', [StatusEksemplar::Rusak, StatusEksemplar::Hilang])
                    ->latest('updated_at')
            )
            ->columns([
                TextColumn::make('buku.judul')->label('Judul Buku')->searchable(),
                TextColumn::make('barcode')->label('Barcode')->searchable(),
                TextColumn::make('rak.nama')->label('Rak')->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (StatusEksemplar $state) => match ($state) {
                        StatusEksemplar::Rusak => 'warning',
                        StatusEksemplar::Hilang => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')->label('Diperbarui')->dateTime('d F Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        StatusEksemplar::Rusak->value => 'Rusak',
                        StatusEksemplar::Hilang->value => 'Hilang',
                    ]),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}

```
---

## app/Filament/Widgets/DendaTerbaruWidget.php
```php
<?php

namespace App\Filament\Widgets;

use App\Enums\TipeDenda;
use App\Models\Denda;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Denda terbaru yang belum lunas - untuk Admin & Pustakawan.
 */
class DendaTerbaruWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getTableHeading(): string
    {
        return 'Denda Belum Lunas Terbaru';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Denda::query()
                    // BARU (iterasi ini) - eager load 'user' (dipakai kolom
                    // 'user.nama' di bawah), sebelumnya N+1: tiap baris di
                    // halaman widget ini (5-10 baris/page) memicu query
                    // terpisah ke tabel users (Aturan poin 3/9 - performa).
                    ->with('user')
                    ->where('status_lunas', false)
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('user.nama')->label('User'),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(TipeDenda $state) => match ($state) {
                        TipeDenda::Keterlambatan => 'warning',
                        TipeDenda::Kerusakan => 'danger',
                        TipeDenda::Kehilangan => 'gray',
                    }),
                TextColumn::make('nominal')->label('Nominal')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),
                IconColumn::make('status_refund')
                    ->label('Refund')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')->label('Tanggal')->dateTime('d F Y H:i'),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}

```
---

## app/Filament/Widgets/GamifikasiBulananWidget.php
```php
<?php

namespace App\Filament\Widgets;

use App\Models\LevelBadgeLog;
use App\Models\PunishmentLog;
use App\Models\RewardLog;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Tren perolehan Badge, Reward, dan penerapan Punishment per bulan tahun
 * berjalan. Badge/Reward dari kolom tanggal_didapat, Punishment dari
 * tanggal_diterapkan (bukan tanggal_berakhir - poin start peristiwa yang
 * relevan untuk tren "terjadi kapan").
 */
class GamifikasiBulananWidget extends ChartWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Badge, Reward & Punishment per Bulan ('.now()->year.')';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getData(): array
    {
        $tahun = now()->year;

        $badge = LevelBadgeLog::query()
            ->selectRaw('MONTH(tanggal_didapat) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_didapat', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $reward = RewardLog::query()
            ->selectRaw('MONTH(tanggal_didapat) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_didapat', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $punishment = PunishmentLog::query()
            ->selectRaw('MONTH(tanggal_diterapkan) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_diterapkan', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $labels = [];
        $dataBadge = [];
        $dataReward = [];
        $dataPunishment = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $labels[] = Carbon::create($tahun, $bulan, 1)->translatedFormat('M');
            $dataBadge[] = (int) ($badge[$bulan] ?? 0);
            $dataReward[] = (int) ($reward[$bulan] ?? 0);
            $dataPunishment[] = (int) ($punishment[$bulan] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Badge',
                    'data' => $dataBadge,
                    'borderColor' => '#a855f7',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Reward',
                    'data' => $dataReward,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Punishment',
                    'data' => $dataPunishment,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.12)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
            'elements' => [
                'point' => ['radius' => 2, 'hoverRadius' => 5],
            ],
        ];
    }
}

```
---

## app/Filament/Widgets/PeminjamanJatuhTempoWidget.php
```php
<?php

namespace App\Filament\Widgets;

use App\Enums\StatusPeminjaman;
use App\Models\Peminjaman;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Peminjaman yang mendekati/melewati jatuh tempo - untuk Admin & Pustakawan.
 * TODO: verifikasi signature terhadap versi filament/filament ^5.7 -
 * TableWidget/table() API diasumsikan sama seperti pola Resource table().
 *
 * BUG FIX (iterasi sebelumnya): kolom 'buku.judul' DIHAPUS - Peminjaman
 * tidak lagi punya relasi langsung ke Buku sejak migration
 * 2026_08_02_000002-000004 (relasi kini lewat Eksemplar). Diganti jadi
 * 'eksemplar.buku.judul', dan query diberi eager load 'eksemplar.buku'
 * supaya tidak N+1 di tabel widget ini.
 *
 * UI (iterasi ini): kolom 'Sisa Hari' diubah dari angka mentah menjadi
 * teks manusiawi ("3 hari lagi", "Terlambat 2 hari", "Jatuh tempo hari
 * ini") - warna badge tetap dipertahankan dari logika sebelumnya
 * (negatif/<=1/lainnya), hanya label teksnya yang diubah. Perhitungan
 * pakai startOfDay() pada kedua tanggal agar "hari ini" akurat tanpa
 * terpengaruh jam saat request dibuat.
 */
class PeminjamanJatuhTempoWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getTableHeading(): string
    {
        return 'Peminjaman Perlu Perhatian (Jatuh Tempo Terdekat)';
    }

    /**
     * Selisih hari (positif = belum jatuh tempo, 0 = hari ini, negatif =
     * sudah terlambat sekian hari) - dipisah dari format teksnya supaya
     * warna badge tetap konsisten dengan angka asli, bukan hasil parsing
     * teks.
     */
    protected function hitungSelisihHari(Peminjaman $record): int
    {
        return (int) Carbon::now()->startOfDay()
            ->diffInDays($record->tanggal_jatuh_tempo->copy()->startOfDay(), false);
    }

    protected function formatSisaHari(int $selisih): string
    {
        return match (true) {
            $selisih === 0 => 'Jatuh tempo hari ini',
            $selisih === 1 => 'Besok jatuh tempo',
            $selisih > 1 => "{$selisih} hari lagi",
            $selisih === -1 => 'Terlambat 1 hari',
            default => 'Terlambat '.abs($selisih).' hari',
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Peminjaman::query()
                    ->with(['user', 'eksemplar.buku'])
                    ->whereIn('status', [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat])
                    ->orderBy('tanggal_jatuh_tempo')
            )
            ->columns([
                TextColumn::make('user.nama')->label('Peminjam'),
                TextColumn::make('eksemplar.buku.judul')->label('Buku'),
                TextColumn::make('tanggal_jatuh_tempo')->label('Jatuh Tempo')->date('d F Y'),
                TextColumn::make('sisa_hari')
                    ->label('Sisa Hari')
                    ->state(fn (Peminjaman $record) => $this->formatSisaHari($this->hitungSelisihHari($record)))
                    ->badge()
                    ->color(function (Peminjaman $record) {
                        $selisih = $this->hitungSelisihHari($record);

                        return $selisih < 0 ? 'danger' : ($selisih <= 1 ? 'warning' : 'success');
                    }),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (StatusPeminjaman $state) => match ($state) {
                        StatusPeminjaman::Terlambat => 'danger',
                        StatusPeminjaman::Aktif => 'success',
                        default => 'gray',
                    }),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}

```
---

## app/Filament/Widgets/PeminjamanStatsWidget.php
```php
<?php

namespace App\Filament\Widgets;

use App\Enums\RoleUser;
use App\Enums\StatusPeminjaman;
use App\Models\Buku;
use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\SnapshotHarian;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

/**
 * BARU (iterasi ini): 6 chart trend sebelumnya menghitung ulang query
 * agregat 7x per metrik SETIAP dashboard dibuka (~35-42 query total).
 * Sekarang trend dibaca dari SnapshotHarian - 1 query untuk 7 hari
 * sekaligus, diisi harian oleh ProsesCronHarianPerpustakaan (Aturan
 * poin 3/9 - performa). Nilai "sekarang" (angka besar di tiap Stat,
 * BUKAN chart-nya) tetap dihitung live seperti sebelumnya - itu memang
 * harus real-time, bukan snapshot kemarin.
 */
class PeminjamanStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    /**
     * Snapshot 7 hari terakhir (termasuk hari ini), di-key per format
     * tanggal singkat (d/m) - dimuat SEKALI dan dipakai ulang oleh semua
     * Stat di getStats(), bukan query per Stat (Aturan poin 3).
     *
     * @return Collection<string, SnapshotHarian>
     */
    private function muatSnapshot7Hari(): Collection
    {
        $mulai = Carbon::now()->subDays(6)->startOfDay();
        $akhir = Carbon::now()->startOfDay();

        return SnapshotHarian::query()
            ->whereBetween('tanggal', [$mulai->toDateString(), $akhir->toDateString()])
            ->get()
            ->keyBy(fn(SnapshotHarian $s) => $s->tanggal->format('d/m'));
    }

    /**
     * @return array<string, float>
     */
    private function trendDariSnapshot(Collection $snapshots, string $kolom): array
    {
        $hasil = [];

        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::now()->subDays($i)->startOfDay();
            $label = $tanggal->format('d/m');
            $hasil[$label] = (float) ($snapshots[$label]?->{$kolom} ?? 0);
        }

        return $hasil;
    }

    protected function getStats(): array
    {
        $snapshots = $this->muatSnapshot7Hari();

        $aktif = Peminjaman::query()->where('status', StatusPeminjaman::Aktif)->count();
        $terlambat = Peminjaman::query()->where('status', StatusPeminjaman::Terlambat)->count();

        $dendaBelumLunas = Denda::query()->where('status_lunas', false);
        $jumlahDendaBelumLunas = $dendaBelumLunas->count();
        $nominalDendaBelumLunas = (clone $dendaBelumLunas)->sum('nominal');

        $kunjunganHariIni = Kunjungan::query()->whereDate('tanggal', now()->toDateString())->count();

        $totalJudulBuku = Buku::query()->count();
        $totalAnggotaAktif = User::query()
            ->where('status_suspend', false)
            ->whereNotIn('role', [RoleUser::Admin, RoleUser::Pustakawan])
            ->count();

        return [
            Stat::make('Peminjaman Aktif', (string) $aktif)
                ->icon('heroicon-o-book-open')
                ->color('success')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'peminjaman_baru'))),

            Stat::make('Peminjaman Terlambat', (string) $terlambat)
                ->icon('heroicon-o-clock')
                ->color($terlambat > 0 ? 'danger' : 'gray')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'peminjaman_terlambat'))),

            Stat::make('Denda Belum Lunas', $jumlahDendaBelumLunas . ' transaksi')
                ->icon('heroicon-o-banknotes')
                ->description('Rp ' . number_format((float) $nominalDendaBelumLunas, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($jumlahDendaBelumLunas > 0 ? 'warning' : 'gray')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'denda_baru'))),

            Stat::make('Kunjungan Hari Ini', (string) $kunjunganHariIni)
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->color('info')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'kunjungan'))),

            Stat::make('Total Judul Buku', (string) $totalJudulBuku)
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'total_judul_buku'))),

            Stat::make('Total Anggota Aktif', (string) $totalAnggotaAktif)
                ->icon('heroicon-o-users')
                ->color('gray')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'total_anggota_aktif'))),
        ];
    }
}

```
---

## app/Filament/Widgets/PerJenisKelaminWidget.php
```php
<?php

namespace App\Filament\Widgets;

use App\Enums\JenisKelamin;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Filament\Widgets\ChartWidget;

/**
 * Perbandingan Laki-laki vs Perempuan untuk Kunjungan, Peminjaman,
 * Pengembalian - tahun berjalan. Pengembalian tidak punya user_id
 * langsung, resolusi lewat relasi peminjaman.user (Aturan poin 3 - tetap
 * pakai relasi Model yang sudah ada, tidak duplikasi join manual di raw
 * SQL).
 *
 * TODO: GAP-SPEC - user dengan jenis_kelamin masih null (data lama,
 * belum diisi) TIDAK ikut dihitung di kedua batang L/P - jumlah total
 * chart ini bisa lebih kecil dari total kunjungan/peminjaman
 * sebenarnya selama data belum lengkap.
 */
class PerJenisKelaminWidget extends ChartWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Kunjungan, Peminjaman & Pengembalian per Jenis Kelamin ('.now()->year.')';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getData(): array
    {
        $tahun = now()->year;

        $hitungKunjungan = fn (JenisKelamin $jk) => Kunjungan::query()
            ->whereYear('tanggal', $tahun)
            ->whereHas('user', fn ($q) => $q->where('jenis_kelamin', $jk))
            ->count();

        $hitungPeminjaman = fn (JenisKelamin $jk) => Peminjaman::query()
            ->whereYear('tanggal_pinjam', $tahun)
            ->whereHas('user', fn ($q) => $q->where('jenis_kelamin', $jk))
            ->count();

        $hitungPengembalian = fn (JenisKelamin $jk) => Pengembalian::query()
            ->whereYear('tanggal_kembali', $tahun)
            ->whereHas('peminjaman.user', fn ($q) => $q->where('jenis_kelamin', $jk))
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Laki-laki',
                    'data' => [
                        $hitungKunjungan(JenisKelamin::LakiLaki),
                        $hitungPeminjaman(JenisKelamin::LakiLaki),
                        $hitungPengembalian(JenisKelamin::LakiLaki),
                    ],
                    'backgroundColor' => '#3b82f6',
                ],
                [
                    'label' => 'Perempuan',
                    'data' => [
                        $hitungKunjungan(JenisKelamin::Perempuan),
                        $hitungPeminjaman(JenisKelamin::Perempuan),
                        $hitungPengembalian(JenisKelamin::Perempuan),
                    ],
                    'backgroundColor' => '#ec4899',
                ],
            ],
            'labels' => ['Kunjungan', 'Peminjaman', 'Pengembalian'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

```
---

## app/Filament/Widgets/TrenBulananWidget.php
```php
<?php

namespace App\Filament\Widgets;

use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Tren bulanan tahun berjalan - Kunjungan, Peminjaman, Pengembalian
 * dalam satu chart supaya mudah dibandingkan (Aturan poin 3, data
 * dihitung langsung dari tabel masing-masing, tidak ada tabel agregat
 * baru).
 */
class TrenBulananWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Tren Bulanan ('.now()->year.')';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getData(): array
    {
        $tahun = now()->year;

        $kunjungan = Kunjungan::query()
            ->selectRaw('MONTH(tanggal) as bulan, COUNT(*) as total')
            ->whereYear('tanggal', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $peminjaman = Peminjaman::query()
            ->selectRaw('MONTH(tanggal_pinjam) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_pinjam', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $pengembalian = Pengembalian::query()
            ->selectRaw('MONTH(tanggal_kembali) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_kembali', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $labels = [];
        $dataKunjungan = [];
        $dataPeminjaman = [];
        $dataPengembalian = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $labels[] = Carbon::create($tahun, $bulan, 1)->translatedFormat('M');
            $dataKunjungan[] = (int) ($kunjungan[$bulan] ?? 0);
            $dataPeminjaman[] = (int) ($peminjaman[$bulan] ?? 0);
            $dataPengembalian[] = (int) ($pengembalian[$bulan] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Kunjungan',
                    'data' => $dataKunjungan,
                    'borderColor' => '#06b6d4',
                    'backgroundColor' => 'rgba(6, 182, 212, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Peminjaman',
                    'data' => $dataPeminjaman,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Pengembalian',
                    'data' => $dataPengembalian,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
            'elements' => [
                'point' => ['radius' => 2, 'hoverRadius' => 5],
            ],
        ];
    }
}

```
---

## app/Filament/Widgets/WhatsappLogWidget.php
```php
<?php

namespace App\Filament\Widgets;

use App\Models\WhatsappLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Log Pengiriman WhatsApp - super_admin & pustakawan bisa lihat widget
 * ini (dikonfirmasi), tapi kolom yang memuat data pribadi (No. Tujuan)
 * atau detail teknis mentah (Keterangan - bisa memuat pesan error
 * gateway apa adanya) DIBATASI hanya untuk super_admin lewat ->visible()
 * per kolom - bukan menyembunyikan widget secara keseluruhan.
 */
class WhatsappLogWidget extends TableWidget
{
    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getTableHeading(): string
    {
        return 'Log Pengiriman WhatsApp Terbaru';
    }

    protected function isSuperAdmin(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(WhatsappLog::query()->latest('updated_at'))
            ->columns([
                TextColumn::make('template_code')->label('Template')->searchable(),
                TextColumn::make('nomor_tujuan')
                    ->label('No. Tujuan')
                    ->searchable()
                    ->visible(fn () => $this->isSuperAdmin()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'terkirim' => 'success',
                        'gagal_transient' => 'warning',
                        'gagal_permanen' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('percobaan_ke')->label('Percobaan'),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->toggleable()
                    ->placeholder('-')
                    ->visible(fn () => $this->isSuperAdmin()),
                TextColumn::make('updated_at')->label('Terakhir Diperbarui')->dateTime('d F Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'terkirim' => 'Terkirim',
                        'gagal_transient' => 'Gagal (Transient)',
                        'gagal_permanen' => 'Gagal (Permanen)',
                    ]),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}

```
---

## app/Http/Controllers/Api/PerpustakaanDeviceController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventTypePoint;
use App\Enums\JenisTransaksi;
use App\Enums\SourceKunjungan;
use App\Enums\StatusOtaFirmware;
use App\Http\Controllers\Controller;
use App\Models\DeviceLog;
use App\Models\FirmwareRelease;
use App\Models\Kunjungan;
use App\Models\Setting;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\PointService;
use App\Services\RfidResolverService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Endpoint untuk Attendance Machine (ESP32-C3) - kontrak persis mengikuti
 * firmware v2.3.1 (lihat internal/... referensi firmware). SETIAP perubahan
 * response shape di sini WAJIB dicek ulang terhadap parsing firmware
 * (mis. downloadRfidDb() parsing baris per baris plain text, BUKAN JSON).
 *
 * FITUR BARU (iterasi ini): setiap Kunjungan yang berhasil tercatat (baik
 * lewat syncBulk() maupun kirimLangsung()) sekarang JUGA membuat 1
 * Transaksi (jenis: kunjungan) - lihat catatTransaksiKunjungan(). Ini
 * TIDAK mengubah response/HTTP status yang dikirim ke device sama sekali
 * (kontrak firmware poin 17 Aturan tetap utuh) - murni penambahan log di
 * sisi server setelah Kunjungan berhasil dibuat.
 *
 * TODO: GAP-SPEC - Transaksi hasil ini TIDAK menyimpan FK balik ke
 * Kunjungan (tabel kunjungans tidak punya kolom transaksi_id, sengaja
 * tidak ditambah migration baru - lihat diskusi terkait). Transaksi
 * murni log independen, keterangan berisi ringkasan (jam tap + device_id)
 * untuk audit manual.
 */
class PerpustakaanDeviceController extends Controller
{
    public function __construct(
        protected RfidResolverService $rfidResolver,
        protected PointService $pointService,
    ) {}

    public function ping(): JsonResponse
    {
        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Firmware: checkRfidDbVersion() - JSON { "ver": int }
     */
    public function rfidListVersion(): JsonResponse
    {
        return response()->json(['ver' => (int) Setting::get('rfid_db_ver', 0)]);
    }

    /**
     * Firmware: downloadRfidDb() - PLAIN TEXT, bukan JSON.
     * Baris pertama: "ver:<n>". Baris berikutnya: satu kartu 10-digit per baris.
     * Firmware menolak baris yang bukan persis 10 digit angka (lihat parsing
     * di downloadRfidDb: isdigit check, len == 10).
     *
     * TODO: GAP-SPEC - hanya user dengan no_kartu_rfid berformat 10 digit
     * numeric yang akan ikut ter-generate ke daftar ini; kartu format lain
     * (mis. seeder lama 'RFID58354503') otomatis TIDAK akan muncul di device
     * karena tidak lolos filter regex di bawah - bukan bug, tapi konsekuensi
     * kontrak firmware. Data lama wajib diperbaiki ke format 10 digit.
     */
    public function rfidList(): Response
    {
        $ver = (int) Setting::get('rfid_db_ver', 0);

        $kartuList = User::query()
            ->whereNotNull('no_kartu_rfid')
            ->where('no_kartu_rfid', 'REGEXP', '^[0-9]{10}$')
            ->pluck('no_kartu_rfid');

        $body = "ver:{$ver}\n".$kartuList->implode("\n");

        return response($body, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Firmware: nvsSyncToServer() / syncQueueFile() - POST batch.
     * Request: { "data": [ { rfid, timestamp, device_id, sync_mode: true } ] }
     * Response WAJIB: { "data": [ { rfid, timestamp, status: "ok"|"error", message? } ] }
     * karena firmware membaca field "status" per item untuk logging kegagalan
     * (appendFailedLogToSD) - status HTTP selalu 200 selama body valid JSON,
     * kegagalan per-record dilaporkan lewat "status" per item, bukan HTTP code.
     */
    public function syncBulk(Request $request): JsonResponse
    {
        $items = $request->input('data', []);
        $hasil = [];

        foreach ($items as $item) {
            $rfid = (string) ($item['rfid'] ?? '');
            $timestamp = (string) ($item['timestamp'] ?? '');
            $deviceId = (string) ($item['device_id'] ?? '');

            $hasil[] = $this->prosesSatuTap($rfid, $timestamp, $deviceId);
        }

        return response()->json(['data' => $hasil]);
    }

    /**
     * Firmware: kirimLangsung() - kirim 1 tap real-time (SD tidak tersedia).
     * Firmware membaca HTTP STATUS CODE, bukan body, untuk menentukan pesan:
     * 200 = OK, 400 = duplikat ("CUKUP SEKALI!"), 404 = kartu nonaktif.
     * (403 hari libur SENGAJA tidak diimplementasikan - device sudah
     * mengunci diri sendiri di luar jam operasional per keputusan produk.)
     */
    public function kirimLangsung(Request $request): JsonResponse
    {
        $rfid = (string) $request->input('rfid', '');
        $timestamp = (string) $request->input('timestamp', '');
        $deviceId = (string) $request->input('device_id', '');

        $user = $this->rfidResolver->findByKartu($rfid);

        if (! $user) {
            return response()->json(['error' => 'kartu tidak terdaftar'], 404);
        }

        $tanggal = $this->parseTanggalDariTimestamp($timestamp);

        $duplikat = Kunjungan::query()
            ->where('user_id', $user->id)
            ->where('tanggal', $tanggal)
            ->exists();

        if ($duplikat) {
            return response()->json(['error' => 'sudah tercatat hari ini'], 400);
        }

        try {
            $kunjungan = Kunjungan::create([
                'user_id' => $user->id,
                'tanggal' => $tanggal,
                'jam_tap' => $this->parseJamDariTimestamp($timestamp),
                'source' => SourceKunjungan::Rfid,
            ]);
        } catch (QueryException $e) {
            // Race condition dengan unique index kunjungans_unik_aktif_unique.
            return response()->json(['error' => 'sudah tercatat hari ini'], 400);
        }

        $this->pointService->catatEvent(
            $user,
            EventTypePoint::Kunjungan,
            'kunjungan',
            $kunjungan->id,
        );

        $this->catatTransaksiKunjungan($user, $kunjungan, $deviceId);

        return response()->json(['status' => 'ok'], 200);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        DeviceLog::query()->updateOrCreate(
            ['device_id' => (string) $request->input('device_id')],
            [
                'device_name' => $request->input('device_name'),
                'firmware_version' => $request->input('firmware'),
                'uptime_sec' => (int) $request->input('uptime_sec', 0),
                'heap_free' => (int) $request->input('heap_free', 0),
                'pending_records' => (int) $request->input('pending_records', 0),
                'scan_today' => (int) $request->input('scan_today', 0),
                'rssi' => (int) $request->input('rssi', 0),
                'sd_ok' => (bool) $request->input('sd_ok', false),
                'rfid_db_entries' => (int) $request->input('rfid_db_entries', 0),
                'online' => (bool) $request->input('online', false),
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * Firmware: fetchRemoteConfig() - hanya field yang dikirim yang akan
     * dipakai firmware (containsKey check per field), field lain diabaikan.
     */
    public function config(Request $request): JsonResponse
    {
        return response()->json([
            'sleep_start' => (int) Setting::get('device_sleep_start_hour', 18),
            'sleep_end' => (int) Setting::get('device_sleep_end_hour', 5),
            'oled_dim_start' => (int) Setting::get('device_oled_dim_start_hour', 8),
            'oled_dim_end' => (int) Setting::get('device_oled_dim_end_hour', 12),
            'sync_interval_ms' => (int) Setting::get('device_sync_interval_ms', 300000),
            'ota_check_interval_ms' => (int) Setting::get('device_ota_check_interval_ms', 30000),
        ]);
    }

    /**
     * Firmware: checkOtaUpdate() - membandingkan versi via
     * compareFirmwareVersion() (semver x.y.z). Field "update" harus true
     * HANYA jika versi rilis aktif LEBIH BARU dari yang dikirim device.
     */
    public function firmwareCheck(Request $request): JsonResponse
    {
        $versiDevice = (string) $request->input('version', '0.0.0');

        $rilisTerbaru = FirmwareRelease::query()
            ->where('aktif', true)
            ->get()
            ->sortByDesc(fn ($r) => $this->normalisasiVersi($r->version))
            ->first();

        if (! $rilisTerbaru || $this->bandingkanVersi($rilisTerbaru->version, $versiDevice) <= 0) {
            return response()->json(['update' => false]);
        }

        return response()->json([
            'update' => true,
            'version' => $rilisTerbaru->version,
            'url' => $rilisTerbaru->url,
            'md5' => $rilisTerbaru->md5,
        ]);
    }

    protected function prosesSatuTap(string $rfid, string $timestamp, string $deviceId): array
    {
        $user = $this->rfidResolver->findByKartu($rfid);

        if (! $user) {
            return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'error', 'message' => 'kartu tidak terdaftar'];
        }

        $tanggal = $this->parseTanggalDariTimestamp($timestamp);

        $duplikat = Kunjungan::query()
            ->where('user_id', $user->id)
            ->where('tanggal', $tanggal)
            ->exists();

        if ($duplikat) {
            // Bukan error sesungguhnya (device sudah kirim data valid, hanya
            // duplikat) - tetap dilaporkan "error" karena firmware hanya
            // mengenal dua status ("ok"/lainnya) untuk keputusan logging lokal.
            return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'error', 'message' => 'duplikat'];
        }

        try {
            $kunjungan = Kunjungan::create([
                'user_id' => $user->id,
                'tanggal' => $tanggal,
                'jam_tap' => $this->parseJamDariTimestamp($timestamp),
                'source' => SourceKunjungan::Rfid,
            ]);
        } catch (QueryException $e) {
            // Race condition dengan unique index kunjungans_unik_aktif_unique.
            return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'error', 'message' => 'duplikat'];
        }

        $this->pointService->catatEvent(
            $user,
            EventTypePoint::Kunjungan,
            'kunjungan',
            $kunjungan->id,
        );

        $this->catatTransaksiKunjungan($user, $kunjungan, $deviceId);

        return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'ok'];
    }

    /**
     * Satu sumber kebenaran pembuatan Transaksi jenis 'kunjungan' - dipanggil
     * dari prosesSatuTap() (batch) maupun kirimLangsung() (real-time),
     * jangan duplikasi query Transaksi::create() di tempat lain (Aturan
     * poin 3).
     */
    protected function catatTransaksiKunjungan(User $user, Kunjungan $kunjungan, string $deviceId): Transaksi
    {
        return Transaksi::create([
            'user_id' => $user->id,
            'jenis' => JenisTransaksi::Kunjungan,
            'diproses_oleh' => null, // otomatis oleh device, bukan staff
            'tanggal' => now(),
            'keterangan' => "Kunjungan RFID jam {$kunjungan->jam_tap} via device '{$deviceId}'.",
        ]);
    }

    protected function parseTanggalDariTimestamp(string $timestamp): string
    {
        // Firmware format: "Y-m-d H:i:s"
        return substr($timestamp, 0, 10) ?: now()->toDateString();
    }

    protected function parseJamDariTimestamp(string $timestamp): string
    {
        return substr($timestamp, 11) ?: now()->toTimeString();
    }

    protected function normalisasiVersi(string $v): int
    {
        sscanf($v, '%d.%d.%d', $maj, $min, $pat);

        return ((int) $maj * 1000000) + ((int) $min * 1000) + (int) $pat;
    }

    protected function bandingkanVersi(string $a, string $b): int
    {
        return $this->normalisasiVersi($a) <=> $this->normalisasiVersi($b);
    }

    /**
     * Kontrak BARU: firmware lapor hasil OTA setelah proses update/reboot.
     * Request: { "device_id": string, "version": string, "status": "success"|"failed", "error"?: string }
     * Response selalu { "status": "ok" } dengan HTTP 200 selama device_id
     * terisi - device tidak perlu retry berdasarkan response ini (best
     * effort logging, bukan bagian kritis alur OTA).
     *
     * Jika status "success", firmware_version di DeviceLog ikut
     * diperbarui ke versi baru (device sudah berhasil boot versi
     * tersebut). Jika "failed", firmware_version TIDAK diubah (device
     * masih menjalankan versi lama) - hanya ota_error yang dicatat.
     */
    public function firmwareReport(Request $request): JsonResponse
    {
        $deviceId = (string) $request->input('device_id');

        if ($deviceId === '') {
            return response()->json(['error' => 'device_id wajib diisi'], 422);
        }

        $status = StatusOtaFirmware::tryFrom((string) $request->input('status'));

        if (! $status) {
            return response()->json(['error' => 'status harus "success" atau "failed"'], 422);
        }

        $update = [
            'ota_status' => $status,
            'ota_error' => $status === StatusOtaFirmware::Gagal ? (string) $request->input('error', '') : null,
            'ota_reported_at' => now(),
        ];

        if ($status === StatusOtaFirmware::Sukses && $request->filled('version')) {
            $update['firmware_version'] = (string) $request->input('version');
        }

        DeviceLog::query()->updateOrCreate(
            ['device_id' => $deviceId],
            $update
        );

        return response()->json(['status' => 'ok']);
    }
}

```
---

## app/Http/Controllers/ChartExportController.php
```php
<?php

namespace App\Http\Controllers;

use App\Filament\Widgets\BukuPerKategoriWidget;
use App\Filament\Widgets\GamifikasiBulananWidget;
use App\Filament\Widgets\PeminjamanStatsWidget;
use App\Filament\Widgets\PerJenisKelaminWidget;
use App\Filament\Widgets\TrenBulananWidget;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ReflectionMethod;

/**
 * Generate PDF grafik berisi gambar + tabel data + ringkasan, diambil
 * ULANG dari database lewat widget terkait (bukan dari angka yang
 * dikirim client) - satu sumber kebenaran tetap di getData()/getStats()
 * masing-masing widget (Aturan poin 3), di sini hanya dipanggil ulang
 * lewat reflection karena method-nya protected.
 *
 * TODO: verifikasi signature terhadap versi barryvdh/laravel-dompdf
 * yang benar-benar terpasang (composer.json ^3.1) - method
 * Pdf::loadView()->download() diasumsikan stabil, belum diverifikasi
 * langsung terhadap composer.lock.
 */
class ChartExportController extends Controller
{
    // Whitelist ketat - JANGAN pernah instantiate class dari input client
    // tanpa validasi ini, mencegah instansiasi class sembarangan.
    private const ALLOWED_CHART_WIDGETS = [
        TrenBulananWidget::class,
        GamifikasiBulananWidget::class,
        PerJenisKelaminWidget::class,
        BukuPerKategoriWidget::class,
    ];

    private const ALLOWED_STAT_WIDGETS = [
        PeminjamanStatsWidget::class,
    ];

    public function pdf(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'string', 'starts_with:data:image/png;base64,'],
            'filename' => ['nullable', 'string', 'max:100'],
            'widget' => ['required', 'string'],
            'type' => ['required', 'in:chart,stat'],
            'stat_label' => ['nullable', 'string', 'max:150'],
        ]);

        $filename = Str::slug($validated['filename'] ?? 'grafik').'.pdf';

        $data = $validated['type'] === 'chart'
            ? $this->buildChartExportData($validated['widget'])
            : $this->buildStatExportData($validated['widget'], $validated['stat_label'] ?? '');

        $pdf = Pdf::loadView('pdf.chart-export', [
            'image' => $validated['image'],
            'heading' => $data['heading'],
            'rows' => $data['rows'],
            'summary' => $data['summary'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    private function buildChartExportData(string $widgetClass): array
    {
        abort_unless(in_array($widgetClass, self::ALLOWED_CHART_WIDGETS, true), 403);

        $widget = app($widgetClass);

        $heading = (new ReflectionMethod($widget, 'getHeading'))->invoke($widget);
        $heading = $heading instanceof Htmlable ? strip_tags($heading->toHtml()) : (string) $heading;

        $chartData = (new ReflectionMethod($widget, 'getData'))->invoke($widget);
        $labels = $chartData['labels'] ?? [];
        $datasets = $chartData['datasets'] ?? [];

        $rows = [];
        foreach ($labels as $i => $label) {
            $row = ['label' => $label];
            foreach ($datasets as $dataset) {
                $row[$dataset['label'] ?? '-'] = $dataset['data'][$i] ?? 0;
            }
            $rows[] = $row;
        }

        $summary = [];
        foreach ($datasets as $dataset) {
            $values = array_map('floatval', $dataset['data'] ?? []);
            $summary[] = [
                'label' => $dataset['label'] ?? '-',
                'total' => array_sum($values),
                'rata_rata' => count($values) ? round(array_sum($values) / count($values), 2) : 0,
            ];
        }

        return ['heading' => $heading, 'rows' => $rows, 'summary' => $summary];
    }

    private function buildStatExportData(string $widgetClass, string $statLabel): array
    {
        abort_unless(in_array($widgetClass, self::ALLOWED_STAT_WIDGETS, true), 403);

        $widget = app($widgetClass);

        $stats = (new ReflectionMethod($widget, 'getStats'))->invoke($widget);

        $stat = collect($stats)->first(
            fn ($s) => $s->getLabel() === $statLabel
        );

        abort_if($stat === null, 404, 'Stat tidak ditemukan.');

        $chart = $stat->getChart() ?? [];

        $rows = [];
        foreach ($chart as $label => $value) {
            $rows[] = ['label' => $label, $statLabel => $value];
        }

        $values = array_map('floatval', array_values($chart));

        return [
            'heading' => $statLabel,
            'rows' => $rows,
            'summary' => [[
                'label' => $statLabel,
                'total' => array_sum($values),
                'rata_rata' => count($values) ? round(array_sum($values) / count($values), 2) : 0,
            ]],
        ];
    }
}

```
---

## app/Http/Controllers/Controller.php
```php
<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}

```
---

## app/Http/Middleware/AuthenticateDeviceApiKey.php
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi sederhana untuk endpoint device ESP32 (bukan HMAC seperti WA
 * Gateway) - firmware mengirim header X-API-KEY statis yang sama untuk
 * seluruh device (lihat kirimLangsung/nvsSyncToServer/dst. di firmware).
 *
 * Perubahan pada key ini WAJIB dikomunikasikan ke seluruh device di lapangan
 * (harus di-reconfigure via provisioning mode) - lihat Aturan poin 17.
 */
class AuthenticateDeviceApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-KEY');
        $expected = (string) config('services.device_gateway.api_key');

        if (! $expected || ! $key || ! hash_equals($expected, $key)) {
            return response()->json(['error' => 'API key tidak valid'], 401);
        }

        return $next($request);
    }
}

```
---

## app/Jobs/GenerateLabelBarcodePdfJob.php
```php
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

/**
 * Job generate PDF label barcode di background (queue 'default') agar
 * tidak timeout HTTP request Livewire (Aturan poin 3 - reuse
 * LabelBarcodeService, jangan duplikasi logic generate barcodedi sini).
 *
 * PENTING (Aturan poin 17): $timeout di bawah WAJIB <= --timeout worker
 * queue 'default' di supervisor config.
 *
 * TODO: GAP-SPEC - constructor menerima $eksemplarIds LANGSUNG(bukan
 * $bukuIds seperti versi sebelumnya) - job ini sebelumnya deadcode
 * (di-comment di BukuResource, belum pernah didispatch di production),
 * jadi perubahan signature aman. Resolusi dari Buku -> Eksemplar kini
 * jadi tanggung jawab CALLER (BukuResource meresolve dulu sebelum
 * dispatch), supaya job ini juga bisa dipakai caller yang sudah
 * langsung memilih Eksemplar (EksemplarsRelationManager bulk action).
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

        $filename = 'label-barcode-' . now()->format('Ymd-His') . '-' . substr(md5(uniqid()), 0, 6) . '.pdf';
        $path = "labels/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        Notification::make()
            ->success()
            ->title('Label barcode siap diunduh')
            ->body(count($labels) . ' label berhasil dibuat.')
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
        Log::error('GenerateLabelBarcodePdfJob: gagal generate label. EksemplarIDs: ' . implode(',', $this->eksemplarIds) . ". Error: {$exception->getMessage()}");

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

```
---

## app/Jobs/KirimNotifikasiWhatsapp.php
```php
<?php

namespace App\Jobs;

use App\Exceptions\WhatsappGatewayException;
use App\Models\WhatsappLog;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pengirim notifikasi WhatsApp - dijalankan di queue 'whatsapp' terpisah
 * agar pengiriman WA tidak blocking proses utama (Peminjaman/Denda/Point).
 * Lihat Logic Module §11 checklist dan Aturan poin 3 (Prinsip DRY).
 *
 * Job ini menerima template_code yang SUDAH di-resolve dari Setting (lihat
 * WhatsappService::kirimEvent()) - lookup Setting tetap dilakukan sinkron
 * di pemanggil supaya job tidak perlu query Setting berulang dan supaya
 * kegagalan "template belum dikonfigurasi" tetap terdeteksi segera (bukan
 * baru diketahui setelah job diproses worker).
 *
 * Idempotency: reference_id yang dikirim oleh WhatsappService::kirimEvent()
 * bersifat stabil per event (bukan UUID acak untuk event terjadwal seperti
 * reminder H-3/H-1/denda), sehingga retry job ini maupun eksekusi cron
 * ganda di hari yang sama aman - gateway mendeteksi reference_id yang
 * sama dan mengembalikan 200 (bukan mengirim ulang WA), sesuai kontrak API
 * §2.2 & §9 (idempotency window 24 jam). Retry di sini hanya menghitung
 * ulang signature/timestamp, TIDAK pernah mengirim signature lama.
 *
 * LOGGING (baru, iterasi ini): setiap eksekusi handle() (termasuk retry)
 * melakukan UPSERT ke whatsapp_logs berdasarkan reference_id - BUKAN
 * insert baru per percobaan, supaya satu event tetap satu baris log
 * dengan status/keterangan TERBARU dan counter percobaan_ke bertambah.
 * TODO: GAP-SPEC - reference_id null (constructor mengizinkan ?string,
 * meski kirimEvent() di WhatsappService selalu mengisi fallback UUID)
 * akan selalu INSERT baris baru (tidak bisa di-upsert tanpa key unik) -
 * skenario ini seharusnya tidak pernah terjadi lewat kirimEvent(), tapi
 * dijaga agar job tidak fatal error jika suatu saat dipanggil manual
 * dengan referenceId null.
 */
class KirimNotifikasiWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 25;

    public array $backoff = [5, 15, 30];

    private const STATUS_PERMANEN = [400, 403, 409];

    public function __construct(
        protected string $templateCode,
        protected string $nomorTujuan,
        protected array $variables,
        protected ?string $referenceId,
    ) {}

    public function handle(WhatsappService $whatsappService): void
    {
        $percobaanKe = $this->attempts();

        try {
            $whatsappService->kirimPesan(
                templateCode: $this->templateCode,
                recipient: $this->nomorTujuan,
                variables: $this->variables,
                referenceId: $this->referenceId,
            );

            $this->catatLog('terkirim', null, $percobaanKe);
        } catch (WhatsappGatewayException $e) {
            if (in_array($e->statusCode, self::STATUS_PERMANEN, true)) {
                Log::error("KirimNotifikasiWhatsapp: kegagalan permanen (status {$e->statusCode}), tidak di-retry. Template '{$this->templateCode}' ke {$this->nomorTujuan}: {$e->getMessage()}");

                $this->catatLog('gagal_permanen', $e->getMessage(), $percobaanKe);

                $this->fail($e);

                return;
            }

            Log::error("KirimNotifikasiWhatsapp: gagal mengirim template '{$this->templateCode}' ke {$this->nomorTujuan}: {$e->getMessage()}");

            $this->catatLog('gagal_transient', $e->getMessage(), $percobaanKe);

            throw $e;
        }
    }

    /**
     * Daftar nama variable yang dianggap sensitif dan WAJIB di-redact
     * sebelum disimpan ke whatsapp_logs (dikonfirmasi eksplisit - OTP
     * tidak boleh tersimpan plaintext permanen di log, beda dengan
     * login_otps/password_reset_otps yang sudah hashed by design).
     * TODO: GAP-SPEC - daftar ini match case-insensitive terhadap NAMA
     * key variable, bukan terhadap eventCode - kalau suatu saat ada
     * variable baru yang juga sensitif (mis. 'password_sementara'),
     * WAJIB ditambahkan di sini, satu tempat, bukan di tiap pemanggil.
     */
    private const VARIABLE_SENSITIF = ['otp', 'password', 'password_baru', 'password_sementara'];

    protected function catatLog(string $status, ?string $keterangan, int $percobaanKe): void
    {
        $atribut = [
            'template_code' => $this->templateCode,
            'nomor_tujuan' => $this->nomorTujuan,
            'variables' => $this->redactVariabelSensitif($this->variables),
            'status' => $status,
            'keterangan' => $keterangan,
            'percobaan_ke' => $percobaanKe,
        ];

        if ($this->referenceId === null) {
            WhatsappLog::create(['reference_id' => null, ...$atribut]);

            return;
        }

        WhatsappLog::updateOrCreate(
            ['reference_id' => $this->referenceId],
            $atribut,
        );
    }

    protected function redactVariabelSensitif(array $variables): array
    {
        $hasil = [];

        foreach ($variables as $key => $value) {
            $hasil[$key] = in_array(strtolower((string) $key), self::VARIABLE_SENSITIF, true)
                ? '***'
                : $value;
        }

        return $hasil;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("KirimNotifikasiWhatsapp: job gagal permanen. Template '{$this->templateCode}' ke {$this->nomorTujuan}: {$exception->getMessage()}");
    }
}

```
---

## app/Models/BukuKategori.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Pivot model untuk buku_kategori, dipakai sebagai "through" table
// pada Kategori::eksemplars() (hasManyThrough)
class BukuKategori extends Model
{
    protected $table = 'buku_kategori';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'buku_id';

    protected $keyType = 'string';
}

```
---

## app/Models/Buku.php
```php
<?php

namespace App\Models;

use App\Enums\StatusEksemplar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Buku extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'judul',
        'cover',
        'penulis',
        'penerbit',
        'isbn',
        'tahun_terbit',
        'harga_ganti',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'harga_ganti' => 'decimal:2',
            'tahun_terbit' => 'integer',
        ];
    }

    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class);
    }

    public function eksemplars(): HasMany
    {
        return $this->hasMany(Eksemplar::class);
    }

    // dihitung on-the-fly, bukan field statis lagi
    public function stokTersedia(): int
    {
        return $this->eksemplars()->where('status', StatusEksemplar::Tersedia)->count();
    }

    /**
     * BARU - jumlah eksemplar yang masih dianggap bagian koleksi aktif
     * (Tersedia + Dipinjam + Rusak). Eksemplar Hilang SENGAJA dikeluarkan
     * dari hitungan ini (dikonfirmasi) - dipakai BukuResource kolom
     * 'Jumlah Buku' supaya angka tidak menyesatkan (buku hilang bukan lagi
     * bagian koleksi yang bisa dipinjam/ditemukan kembali fisiknya).
     */
    public function jumlahEksemplarAktif(): int
    {
        return $this->eksemplars()->where('status', '!=', StatusEksemplar::Hilang)->count();
    }

    public function jumlahEksemplarRusak(): int
    {
        return $this->eksemplars()->where('status', StatusEksemplar::Rusak)->count();
    }

    public function jumlahEksemplarHilang(): int
    {
        return $this->eksemplars()->where('status', StatusEksemplar::Hilang)->count();
    }
}

```
---

## app/Models/Denda.php
```php
<?php

namespace App\Models;

use App\Enums\StatusRefund;
use App\Enums\TipeDenda;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Denda extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'peminjaman_id',
        'user_id',
        'tipe',
        'nominal',
        'status_lunas',
        'tanggal_lunas',
        'keterangan',
        'status_refund',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tipe' => TipeDenda::class,
            'nominal' => 'decimal:2',
            'status_lunas' => 'boolean',
            'tanggal_lunas' => 'datetime',
            'status_refund' => StatusRefund::class,
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

```
---

## app/Models/DeviceLog.php
```php
<?php

namespace App\Models;

use App\Enums\StatusOtaFirmware;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DeviceLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'device_id',
        'device_name',
        'firmware_version',
        'uptime_sec',
        'heap_free',
        'pending_records',
        'scan_today',
        'rssi',
        'sd_ok',
        'rfid_db_entries',
        'online',
        'last_seen_at',
        'ota_status',
        'ota_error',
        'ota_reported_at',
    ];

    protected function casts(): array
    {
        return [
            'sd_ok' => 'boolean',
            'online' => 'boolean',
            'last_seen_at' => 'datetime',
            'ota_status' => StatusOtaFirmware::class,
            'ota_reported_at' => 'datetime',
        ];
    }
}

```
---

## app/Models/Eksemplar.php
```php
<?php

namespace App\Models;

use App\Enums\StatusEksemplar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Eksemplar extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'buku_id',
        'barcode',
        'rak_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusEksemplar::class,
        ];
    }

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class);
    }

    public function rak(): BelongsTo
    {
        return $this->belongsTo(Rak::class);
    }

    public function peminjamans(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }

    /**
     * BARU - Peminjaman terakhir (berdasarkan created_at) untuk eksemplar
     * ini. Dipakai EksemplarsRelationManager untuk menampilkan siapa yang
     * merusak/menghilangkan eksemplar berstatus Rusak/Hilang, tanpa query
     * manual berulang (Aturan poin 3 - DRY).
     */
    public function peminjamanTerakhir(): HasOne
    {
        return $this->hasOne(Peminjaman::class)->latestOfMany();
    }

    /**
     * Satu sumber kebenaran format barcode auto-generate (Aturan poin 3
     * - DRY). SEBELUMNYA duplikat persis di BukuImporter::afterSave() dan
     * CreateBuku::afterCreate() - kedua caller sekarang memanggil ini.
     * Format: "{ISBN-atau-JUDULSLUG}-{urutan}", fallback suffix random
     * kalau barcode hasil generate kebetulan sudah dipakai (unique
     * constraint kolom 'barcode').
     */
    public static function generateBarcodeUntuk(Buku $buku, int $urutan): string
    {
        $barcode = strtoupper(($buku->isbn ?: Str::slug($buku->judul)) . '-' . $urutan);

        if (static::query()->where('barcode', $barcode)->exists()) {
            $barcode .= '-' . strtoupper(Str::random(4));
        }

        return $barcode;
    }
}

```
---

## app/Models/FirmwareRelease.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FirmwareRelease extends Model
{
    use HasUuids;

    protected $fillable = [
        'version',
        'url',
        'md5',
        'aktif',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }
}

```
---

## app/Models/Jurusan.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jurusan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['nama', 'kode'];

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}

```
---

## app/Models/Kategori.php
```php
<?php

namespace App\Models;

use App\Enums\StatusEksemplar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kategori extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    public function bukus(): BelongsToMany
    {
        return $this->belongsToMany(Buku::class);
    }

    // dihitung via pivot buku_kategori, bukan kolom langsung di eksemplars
    public function eksemplars(): HasManyThrough
    {
        return $this->hasManyThrough(
            Eksemplar::class,
            BukuKategori::class,
            'kategori_id', // FK di buku_kategori -> kategoris.id
            'buku_id',     // FK di eksemplars -> bukus.id
            'id',          // local key di Kategori
            'buku_id',     // local key di buku_kategori (= bukus.id)
        );
    }

    public function raks(): BelongsToMany
    {
        return $this->belongsToMany(Rak::class);
    }

    // dihitung on-the-fly, sama pola dengan Buku::stokTersedia()
    public function stokTersedia(): int
    {
        return $this->eksemplars()->where('status', StatusEksemplar::Tersedia)->count();
    }
}

```
---

## app/Models/Kelas.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelas extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'kelas';

    protected $fillable = ['nama', 'tingkat', 'jurusan_id'];

    protected function casts(): array
    {
        return ['tingkat' => 'integer'];
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function kelasTahunPelajarans(): HasMany
    {
        return $this->hasMany(KelasTahunPelajaran::class);
    }
}

```
---

## app/Models/KelasTahunPelajaran.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KelasTahunPelajaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['kelas_id', 'tahun_pelajaran_id', 'wali_kelas_id'];

    protected function casts(): array
    {
        return ['wali_kelas_id' => 'integer'];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunPelajaran(): BelongsTo
    {
        return $this->belongsTo(TahunPelajaran::class);
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    // Siswa yang SAAT INI aktif di KTP ini (bukan histori).
    public function siswaAktif(): HasMany
    {
        return $this->hasMany(User::class, 'kelas_tahun_pelajaran_id');
    }

    public function riwayatSiswa(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class);
    }
}

```
---

## app/Models/Kunjungan.php
```php
<?php

namespace App\Models;

use App\Enums\SourceKunjungan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kunjungan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_tap',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal' => 'date',
            'source' => SourceKunjungan::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Unik per hari per user hanya untuk baris AKTIF (deleted_at IS NULL) - dijaga
    // oleh generated column `unik_aktif` + unique index di DB (lihat migration
    // fix_unique_kunjungan_softdelete_aware). Kolom `unik_aktif` sengaja TIDAK
    // dimasukkan ke $fillable/casts karena murni computed oleh DB, jangan pernah
    // diisi manual dari Filament/kode aplikasi.
}

```
---

## app/Models/LevelBadgeLog.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LevelBadgeLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'level_badge_id',
        'tanggal_didapat',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal_didapat' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function levelBadge(): BelongsTo
    {
        return $this->belongsTo(LevelBadge::class);
    }
}

```
---

## app/Models/LevelBadge.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LevelBadge extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama_badge',
        'min_point',
        'max_point',
        'icon',
        'urutan',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function levelBadgeLogs(): HasMany
    {
        return $this->hasMany(LevelBadgeLog::class);
    }
}

```
---

## app/Models/LoginOtp.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginOtp extends Model
{
    protected $fillable = ['no_telepon', 'otp', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}

```
---

## app/Models/PasswordResetOtp.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    protected $fillable = ['no_telepon', 'otp', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}

```
---

## app/Models/Peminjaman.php
```php
<?php

namespace App\Models;

use App\Enums\StatusPeminjaman;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Peminjaman extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'peminjamans';

    protected $fillable = [
        'transaksi_id',
        'user_id',
        'eksemplar_id',
        'tanggal_pinjam',
        'tanggal_jatuh_tempo',
        'status',
        'diproses_oleh',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal_pinjam' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'status' => StatusPeminjaman::class,
            'diproses_oleh' => 'integer',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eksemplar(): BelongsTo
    {
        return $this->belongsTo(Eksemplar::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function pengembalian(): HasOne
    {
        return $this->hasOne(Pengembalian::class);
    }

    public function dendas(): HasMany
    {
        return $this->hasMany(Denda::class);
    }
}

```
---

## app/Models/Pengembalian.php
```php
<?php

namespace App\Models;

use App\Enums\KondisiBuku;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengembalian extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'peminjaman_id',
        'tanggal_kembali',
        'kondisi',
        'catatan',
        'diproses_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kembali' => 'date',
            'kondisi' => KondisiBuku::class,
            'diproses_oleh' => 'integer',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}

```
---

## app/Models/Point.php
```php
<?php

namespace App\Models;

use App\Enums\EventTypePoint;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Point extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'event_type',
        'nilai',
        'ref_type',
        'ref_id',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'event_type' => EventTypePoint::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ref_type/ref_id: polymorphic manual, bukan Eloquent relation - lihat PointService.
}

```
---

## app/Models/PunishmentLog.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PunishmentLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'punishment_id',
        'tanggal_diterapkan',
        'tanggal_berakhir',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal_diterapkan' => 'datetime',
            'tanggal_berakhir' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function punishment(): BelongsTo
    {
        return $this->belongsTo(Punishment::class);
    }
}

```
---

## app/Models/Punishment.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Punishment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'deskripsi',
        'threshold_point_minus',
        'durasi_suspend_hari',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function punishmentLogs(): HasMany
    {
        return $this->hasMany(PunishmentLog::class);
    }
}

```
---

## app/Models/Rak.php
```php
<?php

namespace App\Models;

use App\Enums\StatusEksemplar;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rak extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'lokasi',
    ];

    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class);
    }

    // Rak tidak lagi punya relasi langsung ke Buku sejak migration
    // 2026_08_02_000003 (bukus.rak_id di-drop). Rak sekarang berelasi ke
    // Eksemplar (kopi fisik), bukan ke Buku (judul).
    public function eksemplars(): HasMany
    {
        return $this->hasMany(Eksemplar::class);
    }

    // Jumlah judul Buku UNIK (distinct) di rak ini, terpisah dari jumlah
    // eksemplar fisik (Rak::eksemplars()->count()).
    public function jumlahJudulUnik(): int
    {
        return $this->eksemplars()->distinct('buku_id')->count('buku_id');
    }

    // GAP-SPEC ditutup: definisi "tersedia" DISAMAKAN persis dengan
    // Buku::stokTersedia() - HANYA status Tersedia yang dihitung, Dipinjam/
    // Rusak/Hilang semua dikecualikan (satu sumber kebenaran definisi
    // "tersedia" di seluruh aplikasi, Aturan poin 3).
    public function stokTersedia(): int
    {
        return $this->eksemplars()->where('status', StatusEksemplar::Tersedia)->count();
    }
}

```
---

## app/Models/RewardLog.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'reward_id',
        'tanggal_didapat',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal_didapat' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }
}

```
---

## app/Models/Reward.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reward extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'deskripsi',
        'threshold_point',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function rewardLogs(): HasMany
    {
        return $this->hasMany(RewardLog::class);
    }
}

```
---

## app/Models/RiwayatKelasSiswa.php
```php
<?php

namespace App\Models;

use App\Enums\StatusRiwayatKelas;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiwayatKelasSiswa extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'riwayat_kelas_siswas';

    protected $fillable = [
        'user_id',
        'kelas_tahun_pelajaran_id',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'status' => StatusRiwayatKelas::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kelasTahunPelajaran(): BelongsTo
    {
        return $this->belongsTo(KelasTahunPelajaran::class);
    }
}

```
---

## app/Models/Setting.php
```php
<?php

namespace App\Models;

use App\Enums\GroupSetting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'value',
        'group',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'group' => GroupSetting::class,
        ];
    }

    /**
     * Ambil nilai Setting berdasarkan key, dengan fallback default.
     * Di-cache 5 menit agar tidak query berulang di proses batch (cron, dsb).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }
}

```
---

## app/Models/SnapshotHarian.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SnapshotHarian extends Model
{
    protected $fillable = [
        'tanggal',
        'peminjaman_baru',
        'peminjaman_terlambat',
        'denda_baru',
        'kunjungan',
        'total_judul_buku',
        'total_anggota_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'peminjaman_baru' => 'integer',
            'peminjaman_terlambat' => 'integer',
            'denda_baru' => 'integer',
            'kunjungan' => 'integer',
            'total_judul_buku' => 'integer',
            'total_anggota_aktif' => 'integer',
        ];
    }
}

```
---

## app/Models/TahunPelajaran.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TahunPelajaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['nama', 'tanggal_mulai', 'tanggal_selesai', 'aktif'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'aktif' => 'boolean',
        ];
    }

    public function kelasTahunPelajarans(): HasMany
    {
        return $this->hasMany(KelasTahunPelajaran::class);
    }

    public static function aktif(): ?self
    {
        return static::query()->where('aktif', true)->first();
    }
}

```
---

## app/Models/Transaksi.php
```php
<?php

namespace App\Models;

use App\Enums\JenisTransaksi;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'jenis',
        'diproses_oleh',
        'tanggal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'jenis' => JenisTransaksi::class,
            'diproses_oleh' => 'integer',
            'tanggal' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function peminjamans(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }
}

```
---

## app/Models/User.php
```php
<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Enums\RoleUser;
use App\Enums\StatusAkademik;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements AuthenticatableContract, FilamentUser, HasName
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'avatar',
        'nama',
        'jenis_kelamin',
        'role',
        'nisn',
        'nip',
        'kelas_tahun_pelajaran_id',
        'status_akademik',
        'jabatan',
        'no_telepon',
        'no_kartu_rfid',
        'password',
        'status_suspend',
        'akumulasi_point',
        'level_badge_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'role' => RoleUser::class,
            'jenis_kelamin' => JenisKelamin::class,
            'status_akademik' => StatusAkademik::class,
            'status_suspend' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getFilamentName(): string
    {
        return $this->nama;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function levelBadge(): BelongsTo
    {
        return $this->belongsTo(LevelBadge::class);
    }

    public function kelasTahunPelajaran(): BelongsTo
    {
        return $this->belongsTo(KelasTahunPelajaran::class);
    }

    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class);
    }
}

```
---

## app/Models/WhatsappLog.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $fillable = [
        'reference_id',
        'template_code',
        'nomor_tujuan',
        'variables',
        'status',
        'keterangan',
        'percobaan_ke',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
        ];
    }
}

```
---

## app/Observers/DendaObserver.php
```php
<?php

namespace App\Observers;

use App\Models\Denda;
use App\Models\PunishmentLog;
use App\Services\WhatsappService;

class DendaObserver
{
    public function __construct(
        protected WhatsappService $whatsappService,
    ) {}

    /**
     * Setiap Denda baru dibuat -> user otomatis suspend (belum lunas apapun tipenya).
     */
    public function created(Denda $denda): void
    {
        $denda->user()->update(['status_suspend' => true]);
    }

    /**
     * Saat status_lunas berubah -> cek apakah SEMUA Denda user sudah lunas
     * DAN tidak ada PunishmentLog aktif, baru unsuspend.
     *
     * TODO: GAP-SPEC - status_suspend dipakai bersama oleh Denda dan Punishment.
     * Unsuspend hanya terjadi jika kedua syarat terpenuhi, supaya user yang masih
     * dalam masa punishment tidak ke-unsuspend keliru saat Denda-nya lunas.
     */
    public function updated(Denda $denda): void
    {
        if (! $denda->wasChanged('status_lunas') || ! $denda->status_lunas) {
            return;
        }

        $masihAdaDendaBelumLunas = Denda::query()
            ->where('user_id', $denda->user_id)
            ->where('status_lunas', false)
            ->exists();

        $masihAdaPunishmentAktif = PunishmentLog::query()
            ->where('user_id', $denda->user_id)
            ->where(function ($q) {
                $q->whereNull('tanggal_berakhir')
                    ->orWhere('tanggal_berakhir', '>', now());
            })
            ->exists();

        if (! $masihAdaDendaBelumLunas && ! $masihAdaPunishmentAktif) {
            $denda->user()->update(['status_suspend' => false]);

            // eventCode 'denda_lunas' - TODO: ASUMSI, samakan dengan Setting
            // wa_template_denda_lunas.
            $this->whatsappService->kirimEvent(
                eventCode: 'denda_lunas',
                nomorTujuan: $denda->user->no_telepon,
                variables: ['nama' => $denda->user->nama],
                referenceId: "denda-lunas-{$denda->id}",
            );
        }
    }
}

```
---

## app/Observers/SettingObserver.php
```php
<?php

namespace App\Observers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingObserver
{
    public function saved(Setting $setting): void
    {
        Cache::forget("setting:{$setting->key}");
    }

    public function deleted(Setting $setting): void
    {
        Cache::forget("setting:{$setting->key}");
    }
}

```
---

## app/Observers/UserObserver.php
```php
<?php

namespace App\Observers;

use App\Enums\GroupSetting;
use App\Enums\RoleUser;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Menaikkan Setting 'rfid_db_ver' setiap kali kartu RFID user berubah, supaya
 * device (ESP32 Attendance Machine) bisa mendeteksi versi baru lewat
 * GET /api/perpustakaan/rfid-list/version dan mengunduh ulang daftar kartu.
 *
 * TODO: GAP-SPEC - "perubahan" didefinisikan sebagai: no_kartu_rfid diisi/diubah,
 * ATAU user dengan kartu terisi di-soft-delete/dipulihkan/dihapus permanen
 * (kartu tersebut harus hilang dari daftar aktif di device). Perubahan pada
 * kolom lain (nama, kelas, dst) TIDAK memicu bump versi.
 *
 * Juga menyinkronkan Spatie role berdasarkan User.role (RoleUser enum), agar
 * akses Filament/Shield selalu konsisten dengan kolom role aplikasi -
 * mapping 1:1 nama enum value <-> nama Spatie role (mis. 'pustakawan' ->
 * 'pustakawan'), KECUALI 'admin' -> 'super_admin' (dikonfirmasi user: admin
 * == super_admin). User hanya boleh punya SATU role Spatie hasil sync ini
 * di satu waktu (syncRoles, bukan assignRole) - role lain yang di-assign
 * manual di luar mapping ini (jika ada) akan ikut tercabut saat sync jalan.
 */
class UserObserver
{
    /**
     * Mapping RoleUser enum -> nama Spatie role.
     */
    private const ROLE_MAP = [
        RoleUser::Admin->value => 'super_admin',
        RoleUser::Pustakawan->value => 'pustakawan',
        RoleUser::Siswa->value => 'siswa',
        RoleUser::Pegawai->value => 'pegawai',
    ];

    public function created(User $user): void
    {
        if ($user->no_kartu_rfid) {
            $this->bumpVersion();
        }

        $this->syncRoleFromEnum($user);
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('no_kartu_rfid')) {
            $this->bumpVersion();
        }

        if ($user->wasChanged('role')) {
            $this->syncRoleFromEnum($user);
        }
    }

    public function deleted(User $user): void
    {
        if ($user->no_kartu_rfid) {
            $this->bumpVersion();
        }
    }

    public function restored(User $user): void
    {
        if ($user->no_kartu_rfid) {
            $this->bumpVersion();
        }
    }

    protected function bumpVersion(): void
    {
        $current = (int) Setting::get('rfid_db_ver', 0);
        $next = $current + 1;

        Setting::query()->updateOrCreate(
            ['key' => 'rfid_db_ver'],
            ['value' => (string) $next, 'group' => GroupSetting::Device]
        );

        // Setting::get() di-cache 5 menit (lihat Setting model) - hapus cache
        // supaya device langsung melihat versi baru, bukan menunggu TTL habis.
        Cache::forget('setting:rfid_db_ver');
    }

    protected function syncRoleFromEnum(User $user): void
    {
        $roleName = self::ROLE_MAP[$user->role->value] ?? null;

        if ($roleName === null) {
            // TODO: GAP-SPEC - enum case baru ditambahkan tapi belum dipetakan
            // di ROLE_MAP. Tidak melakukan apa pun daripada menebak role.
            return;
        }

        $user->syncRoles([$roleName]);
    }
}

```
---

## app/Policies/BukuPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Buku;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BukuPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Buku');
    }

    public function view(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('View:Buku');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Buku');
    }

    public function update(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('Update:Buku');
    }

    public function delete(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('Delete:Buku');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Buku');
    }

    public function restore(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('Restore:Buku');
    }

    public function forceDelete(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('ForceDelete:Buku');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Buku');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Buku');
    }

    public function replicate(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('Replicate:Buku');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Buku');
    }
}

```
---

## app/Policies/DendaPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Denda;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DendaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Denda');
    }

    public function view(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('View:Denda');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Denda');
    }

    public function update(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('Update:Denda');
    }

    public function delete(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('Delete:Denda');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Denda');
    }

    public function restore(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('Restore:Denda');
    }

    public function forceDelete(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('ForceDelete:Denda');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Denda');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Denda');
    }

    public function replicate(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('Replicate:Denda');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Denda');
    }
}

```
---

## app/Policies/EksemplarPolicy.php
```php
<?php

namespace App\Policies;

use App\Models\Eksemplar;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * TODO: GAP-SPEC - dibuat karena EksemplarsRelationManager memakai
 * can('create'/'viewAny', Eksemplar::class) tapi belum pernah ada Policy
 * terdaftar untuk model ini (Eksemplar bukan Filament Resource sendiri,
 * jadi Shield tidak auto-generate). CRUD lengkap disediakan (bukan
 * hanya ViewAny/Create) supaya EditAction/DeleteAction di
 * EksemplarsRelationManager (yang implisit memanggil ability
 * update/delete) tidak tiba-tiba mati begitu Policy ini terdaftar -
 * lihat Aturan poin 17.
 */
class EksemplarPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Eksemplar');
    }

    public function view(User $user, Eksemplar $eksemplar): bool
    {
        return $user->can('View:Eksemplar');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Eksemplar');
    }

    public function update(User $user, Eksemplar $eksemplar): bool
    {
        return $user->can('Update:Eksemplar');
    }

    public function delete(User $user, Eksemplar $eksemplar): bool
    {
        return $user->can('Delete:Eksemplar');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Eksemplar');
    }

    public function restore(User $user, Eksemplar $eksemplar): bool
    {
        return $user->can('Restore:Eksemplar');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Eksemplar');
    }

    public function forceDelete(User $user, Eksemplar $eksemplar): bool
    {
        return $user->can('ForceDelete:Eksemplar');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Eksemplar');
    }

    public function replicate(User $user): bool
    {
        return $user->can('Replicate:Eksemplar');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Eksemplar');
    }
}

```
---

## app/Policies/FirmwareReleasePolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FirmwareRelease;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FirmwareReleasePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FirmwareRelease');
    }

    public function view(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('View:FirmwareRelease');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FirmwareRelease');
    }

    public function update(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('Update:FirmwareRelease');
    }

    public function delete(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('Delete:FirmwareRelease');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FirmwareRelease');
    }

    public function restore(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('Restore:FirmwareRelease');
    }

    public function forceDelete(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('ForceDelete:FirmwareRelease');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FirmwareRelease');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FirmwareRelease');
    }

    public function replicate(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('Replicate:FirmwareRelease');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FirmwareRelease');
    }
}

```
---

## app/Policies/JurusanPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Jurusan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class JurusanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Jurusan');
    }

    public function view(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('View:Jurusan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Jurusan');
    }

    public function update(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('Update:Jurusan');
    }

    public function delete(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('Delete:Jurusan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Jurusan');
    }

    public function restore(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('Restore:Jurusan');
    }

    public function forceDelete(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('ForceDelete:Jurusan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Jurusan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Jurusan');
    }

    public function replicate(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('Replicate:Jurusan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Jurusan');
    }
}

```
---

## app/Policies/KategoriPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Kategori;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class KategoriPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Kategori');
    }

    public function view(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('View:Kategori');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Kategori');
    }

    public function update(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('Update:Kategori');
    }

    public function delete(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('Delete:Kategori');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Kategori');
    }

    public function restore(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('Restore:Kategori');
    }

    public function forceDelete(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('ForceDelete:Kategori');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Kategori');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Kategori');
    }

    public function replicate(AuthUser $authUser, Kategori $kategori): bool
    {
        return $authUser->can('Replicate:Kategori');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Kategori');
    }
}

```
---

## app/Policies/KelasPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Kelas;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class KelasPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Kelas');
    }

    public function view(AuthUser $authUser, Kelas $kelas): bool
    {
        return $authUser->can('View:Kelas');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Kelas');
    }

    public function update(AuthUser $authUser, Kelas $kelas): bool
    {
        return $authUser->can('Update:Kelas');
    }

    public function delete(AuthUser $authUser, Kelas $kelas): bool
    {
        return $authUser->can('Delete:Kelas');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Kelas');
    }

    public function restore(AuthUser $authUser, Kelas $kelas): bool
    {
        return $authUser->can('Restore:Kelas');
    }

    public function forceDelete(AuthUser $authUser, Kelas $kelas): bool
    {
        return $authUser->can('ForceDelete:Kelas');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Kelas');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Kelas');
    }

    public function replicate(AuthUser $authUser, Kelas $kelas): bool
    {
        return $authUser->can('Replicate:Kelas');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Kelas');
    }
}

```
---

## app/Policies/KelasTahunPelajaranPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KelasTahunPelajaran;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class KelasTahunPelajaranPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KelasTahunPelajaran');
    }

    public function view(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('View:KelasTahunPelajaran');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KelasTahunPelajaran');
    }

    public function update(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('Update:KelasTahunPelajaran');
    }

    public function delete(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('Delete:KelasTahunPelajaran');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:KelasTahunPelajaran');
    }

    public function restore(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('Restore:KelasTahunPelajaran');
    }

    public function forceDelete(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('ForceDelete:KelasTahunPelajaran');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KelasTahunPelajaran');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KelasTahunPelajaran');
    }

    public function replicate(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('Replicate:KelasTahunPelajaran');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:KelasTahunPelajaran');
    }
}

```
---

## app/Policies/KunjunganPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Kunjungan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class KunjunganPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Kunjungan');
    }

    public function view(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('View:Kunjungan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Kunjungan');
    }

    public function update(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('Update:Kunjungan');
    }

    public function delete(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('Delete:Kunjungan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Kunjungan');
    }

    public function restore(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('Restore:Kunjungan');
    }

    public function forceDelete(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('ForceDelete:Kunjungan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Kunjungan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Kunjungan');
    }

    public function replicate(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('Replicate:Kunjungan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Kunjungan');
    }
}

```
---

## app/Policies/LevelBadgeLogPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LevelBadgeLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LevelBadgeLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LevelBadgeLog');
    }

    public function view(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('View:LevelBadgeLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LevelBadgeLog');
    }

    public function update(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('Update:LevelBadgeLog');
    }

    public function delete(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('Delete:LevelBadgeLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LevelBadgeLog');
    }

    public function restore(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('Restore:LevelBadgeLog');
    }

    public function forceDelete(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('ForceDelete:LevelBadgeLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LevelBadgeLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LevelBadgeLog');
    }

    public function replicate(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('Replicate:LevelBadgeLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LevelBadgeLog');
    }
}

```
---

## app/Policies/LevelBadgePolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LevelBadge;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LevelBadgePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LevelBadge');
    }

    public function view(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('View:LevelBadge');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LevelBadge');
    }

    public function update(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('Update:LevelBadge');
    }

    public function delete(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('Delete:LevelBadge');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LevelBadge');
    }

    public function restore(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('Restore:LevelBadge');
    }

    public function forceDelete(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('ForceDelete:LevelBadge');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LevelBadge');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LevelBadge');
    }

    public function replicate(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('Replicate:LevelBadge');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LevelBadge');
    }
}

```
---

## app/Policies/PeminjamanPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Peminjaman;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PeminjamanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Peminjaman');
    }

    public function view(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('View:Peminjaman');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Peminjaman');
    }

    public function update(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('Update:Peminjaman');
    }

    public function delete(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('Delete:Peminjaman');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Peminjaman');
    }

    public function restore(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('Restore:Peminjaman');
    }

    public function forceDelete(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('ForceDelete:Peminjaman');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Peminjaman');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Peminjaman');
    }

    public function replicate(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('Replicate:Peminjaman');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Peminjaman');
    }
}

```
---

## app/Policies/PengembalianPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pengembalian;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PengembalianPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pengembalian');
    }

    public function view(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('View:Pengembalian');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pengembalian');
    }

    public function update(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('Update:Pengembalian');
    }

    public function delete(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('Delete:Pengembalian');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Pengembalian');
    }

    public function restore(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('Restore:Pengembalian');
    }

    public function forceDelete(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('ForceDelete:Pengembalian');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pengembalian');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pengembalian');
    }

    public function replicate(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('Replicate:Pengembalian');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pengembalian');
    }
}

```
---

## app/Policies/PunishmentLogPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PunishmentLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PunishmentLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PunishmentLog');
    }

    public function view(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('View:PunishmentLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PunishmentLog');
    }

    public function update(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('Update:PunishmentLog');
    }

    public function delete(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('Delete:PunishmentLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PunishmentLog');
    }

    public function restore(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('Restore:PunishmentLog');
    }

    public function forceDelete(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('ForceDelete:PunishmentLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PunishmentLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PunishmentLog');
    }

    public function replicate(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('Replicate:PunishmentLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PunishmentLog');
    }
}

```
---

## app/Policies/PunishmentPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Punishment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PunishmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Punishment');
    }

    public function view(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('View:Punishment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Punishment');
    }

    public function update(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('Update:Punishment');
    }

    public function delete(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('Delete:Punishment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Punishment');
    }

    public function restore(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('Restore:Punishment');
    }

    public function forceDelete(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('ForceDelete:Punishment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Punishment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Punishment');
    }

    public function replicate(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('Replicate:Punishment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Punishment');
    }
}

```
---

## app/Policies/RakPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rak;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RakPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Rak');
    }

    public function view(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('View:Rak');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Rak');
    }

    public function update(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('Update:Rak');
    }

    public function delete(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('Delete:Rak');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Rak');
    }

    public function restore(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('Restore:Rak');
    }

    public function forceDelete(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('ForceDelete:Rak');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Rak');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Rak');
    }

    public function replicate(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('Replicate:Rak');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Rak');
    }
}

```
---

## app/Policies/RewardLogPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RewardLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RewardLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RewardLog');
    }

    public function view(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('View:RewardLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RewardLog');
    }

    public function update(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('Update:RewardLog');
    }

    public function delete(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('Delete:RewardLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RewardLog');
    }

    public function restore(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('Restore:RewardLog');
    }

    public function forceDelete(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('ForceDelete:RewardLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RewardLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RewardLog');
    }

    public function replicate(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('Replicate:RewardLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RewardLog');
    }
}

```
---

## app/Policies/RewardPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Reward;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RewardPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Reward');
    }

    public function view(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('View:Reward');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Reward');
    }

    public function update(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('Update:Reward');
    }

    public function delete(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('Delete:Reward');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Reward');
    }

    public function restore(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('Restore:Reward');
    }

    public function forceDelete(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('ForceDelete:Reward');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Reward');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Reward');
    }

    public function replicate(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('Replicate:Reward');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Reward');
    }
}

```
---

## app/Policies/RiwayatKelasSiswaPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RiwayatKelasSiswa;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RiwayatKelasSiswaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RiwayatKelasSiswa');
    }

    public function view(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('View:RiwayatKelasSiswa');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RiwayatKelasSiswa');
    }

    public function update(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('Update:RiwayatKelasSiswa');
    }

    public function delete(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('Delete:RiwayatKelasSiswa');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RiwayatKelasSiswa');
    }

    public function restore(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('Restore:RiwayatKelasSiswa');
    }

    public function forceDelete(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('ForceDelete:RiwayatKelasSiswa');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RiwayatKelasSiswa');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RiwayatKelasSiswa');
    }

    public function replicate(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('Replicate:RiwayatKelasSiswa');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RiwayatKelasSiswa');
    }
}

```
---

## app/Policies/RolePolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Role');
    }

    public function view(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('View:Role');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Role');
    }

    public function update(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Update:Role');
    }

    public function delete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Delete:Role');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Role');
    }

    public function restore(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Restore:Role');
    }

    public function forceDelete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('ForceDelete:Role');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Role');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Role');
    }

    public function replicate(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Replicate:Role');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Role');
    }
}

```
---

## app/Policies/TahunPelajaranPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TahunPelajaran;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TahunPelajaranPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TahunPelajaran');
    }

    public function view(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('View:TahunPelajaran');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TahunPelajaran');
    }

    public function update(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('Update:TahunPelajaran');
    }

    public function delete(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('Delete:TahunPelajaran');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TahunPelajaran');
    }

    public function restore(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('Restore:TahunPelajaran');
    }

    public function forceDelete(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('ForceDelete:TahunPelajaran');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TahunPelajaran');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TahunPelajaran');
    }

    public function replicate(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('Replicate:TahunPelajaran');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TahunPelajaran');
    }
}

```
---

## app/Policies/TransaksiPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Transaksi;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TransaksiPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Transaksi');
    }

    public function view(AuthUser $authUser, Transaksi $transaksi): bool
    {
        return $authUser->can('View:Transaksi');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Transaksi');
    }

    public function update(AuthUser $authUser, Transaksi $transaksi): bool
    {
        return $authUser->can('Update:Transaksi');
    }

    public function delete(AuthUser $authUser, Transaksi $transaksi): bool
    {
        return $authUser->can('Delete:Transaksi');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Transaksi');
    }

    public function restore(AuthUser $authUser, Transaksi $transaksi): bool
    {
        return $authUser->can('Restore:Transaksi');
    }

    public function forceDelete(AuthUser $authUser, Transaksi $transaksi): bool
    {
        return $authUser->can('ForceDelete:Transaksi');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Transaksi');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Transaksi');
    }

    public function replicate(AuthUser $authUser, Transaksi $transaksi): bool
    {
        return $authUser->can('Replicate:Transaksi');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Transaksi');
    }
}

```
---

## app/Policies/UserPolicy.php
```php
<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:User');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:User');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:User');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:User');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:User');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:User');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:User');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:User');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:User');
    }
}

```
---

## app/Providers/AppServiceProvider.php
```php
<?php

namespace App\Providers;

use App\Models\Denda;
use App\Models\Setting;
use App\Models\User;
use App\Observers\DendaObserver;
use App\Observers\SettingObserver;
use App\Observers\UserObserver;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        FilamentShield::enforcePolicies();
        Denda::observe(DendaObserver::class);
        User::observe(UserObserver::class);
        Setting::observe(SettingObserver::class); // invalidasi cache Setting::get()
    }
}

```
---

## app/Providers/Filament/DashboardPanelProvider.php
```php
<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Auth\ResetPassword;
use App\Filament\Pages\Dashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->globalSearch(false)
            ->default()
            ->databaseNotifications()
            ->id('dashboard')
            ->path('dashboard')
            ->login(Login::class)
            ->spa()
            ->pages([
                Dashboard::class,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.partials.chart-export-script')->render(),
            )
            ->passwordReset(
                RequestPasswordReset::class,
                ResetPassword::class,
            )
            ->colors([
                'primary' => Color::Cyan,
            ])
            // Pakai Lexend yang sudah di-bundle lokal via @fontsource/lexend
            // (resources/css/app.css), bukan fetch dari Google Fonts CDN.
            // TODO: verifikasi signature terhadap versi package yang
            // terpasang - argumen kedua diasumsikan menonaktifkan provider
            // Google Fonts bawaan Filament v5.7; cek ulang jika behaviour
            // berbeda (mis. tetap muncul request ke fonts.googleapis.com).
            ->font('Lexend', provider: null)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

```
---

## app/Rules/FormatKartuRfid.php
```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Satu sumber kebenaran untuk validasi format no_kartu_rfid (Aturan poin 3).
 * Kontrak mengikat ke firmware Attendance Machine (lihat
 * PerpustakaanDeviceController::rfidList() - firmware downloadRfidDb() hanya
 * menerima baris PERSIS 10 digit angka, isdigit() check + len == 10).
 *
 * Kartu yang tidak lolos rule ini akan tersimpan di DB tapi TIDAK PERNAH
 * muncul di daftar rfid-list yang diunduh device (lihat filter REGEXP di
 * controller) - user tersebut tidak akan bisa tap RFID untuk Kunjungan.
 *
 * Wajib dipakai di:
 * - UserResource form (Filament) saat Resource ini dibuat.
 * - Form Request mana pun yang membuat/mengubah User.no_kartu_rfid.
 */
class FormatKartuRfid implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return; // nullable, kartu belum ditempel/didaftarkan
        }

        if (! preg_match('/^[0-9]{10}$/', (string) $value)) {
            $fail('Nomor kartu RFID harus persis 10 digit angka (sesuai kontrak firmware Attendance Machine). Kartu dengan format lain tidak akan terbaca oleh device.');
        }
    }
}

```
---

## app/Services/KenaikanKelasService.php
```php
<?php

namespace App\Services;

use App\Enums\StatusAkademik;
use App\Enums\StatusRiwayatKelas;
use App\Models\Kelas;
use App\Models\KelasTahunPelajaran;
use App\Models\RiwayatKelasSiswa;
use App\Models\TahunPelajaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Satu sumber kebenaran untuk seluruh perpindahan status akademik siswa
 * (assignment awal, kenaikan kelas, tinggal kelas, lulus, keluar).
 * Resource/Action HANYA memanggil method di sini (Aturan poin 3, DRY).
 */
class KenaikanKelasService
{
    /**
     * Assignment awal / pindah kelas manual (bukan proses kenaikan massal).
     * Menutup RiwayatKelasSiswa aktif sebelumnya (jika ada) dengan status
     * 'keluar' (dianggap pindah kelas manual, bukan kenaikan reguler),
     * lalu buka riwayat baru di KTP tujuan.
     */
    public function assignKelas(User $user, KelasTahunPelajaran $ktpTujuan): void
    {
        DB::transaction(function () use ($user, $ktpTujuan) {
            $this->tutupRiwayatAktif($user, StatusRiwayatKelas::Keluar);

            RiwayatKelasSiswa::query()->create([
                'user_id' => $user->id,
                'kelas_tahun_pelajaran_id' => $ktpTujuan->id,
                'status' => StatusRiwayatKelas::Aktif,
                'tanggal_mulai' => now()->toDateString(),
            ]);

            $user->update([
                'kelas_tahun_pelajaran_id' => $ktpTujuan->id,
                'status_akademik' => StatusAkademik::Aktif,
            ]);
        });
    }

    /**
     * Keluarkan siswa dari kelas aktifnya saat ini tanpa proses kenaikan
     * massal (dipanggil dari SiswaAktifRelationManager, aksi per baris).
     * Menggunakan status 'keluar' - BUKAN 'lulus', karena ini penghapusan
     * assignment manual, bukan kelulusan resmi.
     *
     * // TODO: GAP-SPEC - status_akademik user ikut diubah ke Keluar di
     * sini, sama seperti prosesKeluar() pada alur kenaikan massal, demi
     * konsistensi. Jika maksud "keluarkan dari kelas" di RelationManager
     * ini sebenarnya hanya "lepas assignment kelas" (mis. akan di-assign
     * ulang segera) tanpa mengubah status_akademik, ini perlu dikoreksi -
     * belum ada spek eksplisit yang membedakan dua kasus tersebut.
     */
    public function keluarkanDariKelas(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->tutupRiwayatAktif($user, StatusRiwayatKelas::Keluar);

            $user->update([
                'kelas_tahun_pelajaran_id' => null,
                'status_akademik' => StatusAkademik::Keluar,
            ]);
        });
    }

    /**
     * Proses kenaikan kelas massal dari satu KTP asal.
     *
     * @param  array<string, string>  $keputusan  [user_id => 'naik'|'tinggal'|'lulus'|'keluar']
     *
     * @throws RuntimeException jika Tahun Pelajaran aktif tidak valid atau KTP tujuan tidak ditemukan
     */
    public function prosesKenaikan(KelasTahunPelajaran $ktpAsal, array $keputusan): array
    {
        $tahunAktif = TahunPelajaran::aktif();

        if (! $tahunAktif) {
            throw new RuntimeException('Tidak ada Tahun Pelajaran aktif. Aktifkan Tahun Pelajaran tujuan terlebih dahulu.');
        }

        if ($tahunAktif->id === $ktpAsal->tahun_pelajaran_id) {
            throw new RuntimeException('Tahun Pelajaran aktif masih sama dengan Tahun Pelajaran asal. Aktifkan Tahun Pelajaran berikutnya terlebih dahulu.');
        }

        $gagal = [];

        DB::transaction(function () use ($ktpAsal, $keputusan, $tahunAktif, &$gagal) {
            $kelasAsal = $ktpAsal->kelas;

            foreach ($keputusan as $userId => $status) {
                $user = User::query()->find($userId);

                if (! $user) {
                    continue;
                }

                $statusEnum = StatusRiwayatKelas::from($status);

                try {
                    match ($statusEnum) {
                        StatusRiwayatKelas::Naik => $this->prosesNaik($user, $kelasAsal, $tahunAktif),
                        StatusRiwayatKelas::Tinggal => $this->prosesTinggal($user, $kelasAsal, $tahunAktif),
                        StatusRiwayatKelas::Lulus => $this->prosesLulus($user),
                        StatusRiwayatKelas::Keluar => $this->prosesKeluar($user),
                        default => throw new RuntimeException("Status tidak valid: {$status}"),
                    };
                } catch (RuntimeException $e) {
                    $gagal[$user->nama] = $e->getMessage();
                }
            }
        });

        return $gagal;
    }

    protected function prosesNaik(User $user, Kelas $kelasAsal, TahunPelajaran $tahunTujuan): void
    {
        $kelasTujuan = Kelas::query()
            ->where('tingkat', $kelasAsal->tingkat + 1)
            ->where('jurusan_id', $kelasAsal->jurusan_id)
            ->first();

        if (! $kelasTujuan) {
            throw new RuntimeException('Kelas tingkat+1 dengan jurusan sama belum ada - buat Kelas tujuan terlebih dahulu.');
        }

        $ktpTujuan = KelasTahunPelajaran::query()
            ->where('kelas_id', $kelasTujuan->id)
            ->where('tahun_pelajaran_id', $tahunTujuan->id)
            ->first();

        if (! $ktpTujuan) {
            throw new RuntimeException("KTP tujuan ({$kelasTujuan->nama} -{$tahunTujuan->nama}) belum dibuat.");
        }

        $this->pindahKe($user, $ktpTujuan, StatusRiwayatKelas::Naik);
    }

    protected function prosesTinggal(User $user, Kelas $kelasAsal, TahunPelajaran $tahunTujuan): void
    {
        $ktpTujuan = KelasTahunPelajaran::query()
            ->where('kelas_id', $kelasAsal->id)
            ->where('tahun_pelajaran_id', $tahunTujuan->id)
            ->first();

        if (! $ktpTujuan) {
            throw new RuntimeException("KTP tinggal kelas ({$kelasAsal->nama} - {$tahunTujuan->nama}) belum dibuat.");
        }

        $this->pindahKe($user, $ktpTujuan, StatusRiwayatKelas::Tinggal);
    }

    protected function prosesLulus(User $user): void
    {
        $this->tutupRiwayatAktif($user, StatusRiwayatKelas::Lulus);

        $user->update([
            'kelas_tahun_pelajaran_id' => null,
            'status_akademik' => StatusAkademik::Lulus,
        ]);
        // Akun TETAP aktif normal (dikonfirmasi Aturan) - tidak ada
        // perubahan status_suspend/canAccessPanel di sini.
    }

    protected function prosesKeluar(User $user): void
    {
        $this->keluarkanDariKelas($user);
    }

    protected function pindahKe(User $user, KelasTahunPelajaran $ktpTujuan, StatusRiwayatKelas $statusPenutup): void
    {
        $this->tutupRiwayatAktif($user, $statusPenutup);

        RiwayatKelasSiswa::query()->create([
            'user_id' => $user->id,
            'kelas_tahun_pelajaran_id' => $ktpTujuan->id,
            'status' => StatusRiwayatKelas::Aktif,
            'tanggal_mulai' => now()->toDateString(),
        ]);

        $user->update(['kelas_tahun_pelajaran_id' => $ktpTujuan->id]);
    }

    protected function tutupRiwayatAktif(User $user, StatusRiwayatKelas $statusBaru): void
    {
        RiwayatKelasSiswa::query()
            ->where('user_id', $user->id)
            ->where('status', StatusRiwayatKelas::Aktif)
            ->update([
                'status' => $statusBaru,
                'tanggal_selesai' => now()->toDateString(),
            ]);
    }
}

```
---

## app/Services/LabelBarcodeService.php
```php
<?php

namespace App\Services;

use App\Models\Eksemplar;
use Illuminate\Support\Collection;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Satu sumber kebenaran generate data label barcode Eksemplar (Aturan
 * poin 3) - dipanggil dari EksemplarsRelationManager (aksi single & bulk),
 * jangan duplikasi pemanggilan BarcodeGeneratorPNG di tempat lain.
 */
class LabelBarcodeService
{
    protected BarcodeGeneratorPNG $generator;

    public function __construct()
    {
        $this->generator = new BarcodeGeneratorPNG;
    }

    /**
     * @param  Collection<int, Eksemplar>  $eksemplars
     * @return array<int, array{barcode: string, judul: string, gambar: string}>
     */
    public function generateData(Collection $eksemplars): array
    {
        return $eksemplars->map(function (Eksemplar $eksemplar) {
            $png = $this->generator->getBarcode(
                $eksemplar->barcode,
                $this->generator::TYPE_CODE_128,
                2,
                50,
            );

            return [
                'barcode' => $eksemplar->barcode,
                'judul' => $eksemplar->buku->judul,
                'gambar' => 'data:image/png;base64,'.base64_encode($png),
            ];
        })->all();
    }
}

```
---

## app/Services/LaporanBulananService.php
```php
<?php

namespace App\Services;

use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\LevelBadgeLog;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Point;
use App\Models\PunishmentLog;
use App\Models\RewardLog;
use Illuminate\Support\Carbon;

/**
 * Satu sumber kebenaran agregasi data untuk Laporan Bulanan (Aturan poin 3)
 * - dipanggil dari LaporanBulanan Page, jangan duplikasi query di tempat lain.
 *
 * TODO: GAP-SPEC - filter tanggal per domain memakai kolom "kejadian"
 * masing-masing (tanggal_pinjam, tanggal_kembali, created_at untuk
 * Denda/Point, tanggal untuk Kunjungan, tanggal_didapat untuk
 * RewardLog/LevelBadgeLog, tanggal_diterapkan untuk PunishmentLog) - bukan
 * tanggal_lunas untuk Denda. Perlu dikonfirmasi jika laporan dimaksudkan
 * sebagai laporan kas/arus pemasukan (yang mestinya pakai tanggal_lunas),
 * bukan laporan aktivitas.
 */
class LaporanBulananService
{
    public function generate(int $bulan, int $tahun): array
    {
        $awal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'periode_label' => $awal->translatedFormat('F Y'),
            'peminjaman' => $this->dataPeminjaman($awal, $akhir),
            'pengembalian' => $this->dataPengembalian($awal, $akhir),
            'denda' => $this->dataDenda($awal, $akhir),
            'kunjungan' => $this->dataKunjungan($awal, $akhir),
            'point' => $this->dataPoint($awal, $akhir),
            'poin_reward_punishment' => $this->dataPoinRewardPunishment($awal, $akhir),
        ];
    }

    protected function dataPeminjaman(Carbon $awal, Carbon $akhir): array
    {
        $records = Peminjaman::query()
            ->with(['user', 'eksemplar.buku'])
            ->whereBetween('tanggal_pinjam', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal_pinjam')
            ->get();

        return [
            'total' => $records->count(),
            'per_status' => $records->groupBy(fn ($r) => $r->status->value)->map->count(),
            'detail' => $records,
        ];
    }

    protected function dataPengembalian(Carbon $awal, Carbon $akhir): array
    {
        $records = Pengembalian::query()
            ->with(['peminjaman.user', 'peminjaman.eksemplar.buku'])
            ->whereBetween('tanggal_kembali', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal_kembali')
            ->get();

        return [
            'total' => $records->count(),
            'per_kondisi' => $records->groupBy(fn ($r) => $r->kondisi->value)->map->count(),
            'detail' => $records,
        ];
    }

    protected function dataDenda(Carbon $awal, Carbon $akhir): array
    {
        $records = Denda::query()
            ->with(['user', 'peminjaman.eksemplar.buku'])
            ->whereBetween('created_at', [$awal, $akhir])
            ->orderBy('created_at')
            ->get();

        return [
            'total' => $records->count(),
            'total_nominal' => $records->sum('nominal'),
            'total_nominal_lunas' => $records->where('status_lunas', true)->sum('nominal'),
            'total_nominal_belum_lunas' => $records->where('status_lunas', false)->sum('nominal'),
            'per_tipe' => $records->groupBy(fn ($r) => $r->tipe->value)->map(fn ($g) => [
                'jumlah' => $g->count(),
                'nominal' => $g->sum('nominal'),
            ]),
            'detail' => $records,
        ];
    }

    protected function dataKunjungan(Carbon $awal, Carbon $akhir): array
    {
        $records = Kunjungan::query()
            ->with('user')
            ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal')
            ->get();

        return [
            'total' => $records->count(),
            'user_unik' => $records->pluck('user_id')->unique()->count(),
            'per_source' => $records->groupBy(fn ($r) => $r->source->value)->map->count(),
            'detail' => $records,
        ];
    }

    protected function dataPoint(Carbon $awal, Carbon $akhir): array
    {
        $records = Point::query()
            ->with('user')
            ->whereBetween('created_at', [$awal, $akhir])
            ->orderBy('created_at')
            ->get();

        return [
            'total_transaksi' => $records->count(),
            'total_nilai' => $records->sum('nilai'),
            'per_event' => $records->groupBy(fn ($r) => $r->event_type->value)->map(fn ($g) => [
                'jumlah' => $g->count(),
                'total_nilai' => $g->sum('nilai'),
            ]),
            'detail' => $records,
        ];
    }

    /**
     * Riwayat Badge (LevelBadgeLog), Reward (RewardLog), dan Punishment
     * (PunishmentLog) dalam periode - dikelompokkan per User supaya PDF
     * bisa menampilkan "User X: dapat Badge Y tgl sekian, Reward Z tgl
     * sekian, kena Punishment W tgl sekian" dalam satu baris/blok.
     *
     * TODO: GAP-SPEC - badge yang nempel ke user SEBELUM tabel
     * level_badge_logs dibuat tidak akan muncul di sini (tidak ada
     * histori sebelum migration berjalan) - dikonfirmasi user.
     */
    protected function dataPoinRewardPunishment(Carbon $awal, Carbon $akhir): array
    {
        $badgeLogs = LevelBadgeLog::query()
            ->with(['user', 'levelBadge'])
            ->whereBetween('tanggal_didapat', [$awal, $akhir])
            ->orderBy('tanggal_didapat')
            ->get();

        $rewardLogs = RewardLog::query()
            ->with(['user', 'reward'])
            ->whereBetween('tanggal_didapat', [$awal, $akhir])
            ->orderBy('tanggal_didapat')
            ->get();

        $punishmentLogs = PunishmentLog::query()
            ->with(['user', 'punishment'])
            ->whereBetween('tanggal_diterapkan', [$awal, $akhir])
            ->orderBy('tanggal_diterapkan')
            ->get();

        $userIds = $badgeLogs->pluck('user_id')
            ->merge($rewardLogs->pluck('user_id'))
            ->merge($punishmentLogs->pluck('user_id'))
            ->unique();

        $perUser = $userIds->mapWithKeys(function ($userId) use ($badgeLogs, $rewardLogs, $punishmentLogs) {
            $nama = $badgeLogs->firstWhere('user_id', $userId)?->user?->nama
                ?? $rewardLogs->firstWhere('user_id', $userId)?->user?->nama
                ?? $punishmentLogs->firstWhere('user_id', $userId)?->user?->nama
                ?? '-';

            return [$userId => [
                'nama' => $nama,
                'badge' => $badgeLogs->where('user_id', $userId)->values(),
                'reward' => $rewardLogs->where('user_id', $userId)->values(),
                'punishment' => $punishmentLogs->where('user_id', $userId)->values(),
            ]];
        });

        return [
            'total_badge' => $badgeLogs->count(),
            'total_reward' => $rewardLogs->count(),
            'total_punishment' => $punishmentLogs->count(),
            'per_user' => $perUser,
        ];
    }
}

```
---

## app/Services/LoginOtpService.php
```php
<?php

namespace App\Services;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Satu sumber kebenaran untuk alur login via OTP WhatsApp (Aturan poin 3).
 * BEDA dengan PasswordResetOtpService: verifikasi di sini TIDAK mengubah
 * password sama sekali - hanya mengonfirmasi identitas untuk Auth::login().
 * Risiko diterima sadar (dikonfirmasi): OTP verified = login penuh tanpa
 * user perlu tahu/ganti password, setara alur reset password.
 *
 * TODO: ASUMSI - panjang OTP 6 digit, masa berlaku 5 menit, rate limit 1
 * permintaan per menit per no_telepon - sama seperti PasswordResetOtpService,
 * belum ada Setting terkonfigurasi untuk ini.
 */
class LoginOtpService
{
    public function __construct(
        protected WhatsappService $whatsappService,
    ) {}

    public function kirimOtp(User $user): void
    {
        $rateLimitKey = "otp-login:{$user->no_telepon}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $detik = RateLimiter::availableIn($rateLimitKey);
            throw new \RuntimeException("Tunggu {$detik} detik sebelum meminta OTP baru.");
        }
        RateLimiter::hit($rateLimitKey, 60);

        $otp = (string) random_int(100000, 999999);

        LoginOtp::query()->where('no_telepon', $user->no_telepon)->delete();
        LoginOtp::create([
            'no_telepon' => $user->no_telepon,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
        ]);

        // eventCode 'login_otp' - TODO: ASUMSI, event BARU di luar yang sudah
        // terdaftar Admin di panel gateway. Wajib dibuat template baru +
        // diisi ke Setting 'wa_template_login_otp' (dikonfirmasi dipahami).
        $this->whatsappService->kirimEvent(
            eventCode: 'login_otp',
            nomorTujuan: $user->no_telepon,
            variables: ['nama' => $user->nama, 'otp' => $otp],
            referenceId: "login-otp-{$user->id}-".now()->timestamp,
        );
    }

    /**
     * Verifikasi OTP untuk LOGIN - TIDAK menyentuh password sama sekali.
     * status_suspend TIDAK di-guard di sini (dikonfirmasi: OTP tetap
     * berlaku untuk user suspend, konsisten dengan
     * User::canAccessPanel() yang selalu true - guard sesungguhnya ada
     * di Policy per Resource, bukan di gerbang login).
     *
     * @throws \RuntimeException jika OTP salah/kedaluwarsa/user tidak ditemukan
     */
    public function verifikasiUntukLogin(string $noTelepon, string $otp): User
    {
        $record = LoginOtp::query()
            ->where('no_telepon', $noTelepon)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($otp, $record->otp)) {
            throw new \RuntimeException('Kode OTP salah atau sudah kedaluwarsa.');
        }

        $user = User::query()->where('no_telepon', $noTelepon)->first();

        if (! $user) {
            throw new \RuntimeException('Akun tidak ditemukan.');
        }

        $record->delete();

        return $user;
    }
}

```
---

## app/Services/PasswordResetOtpService.php
```php
<?php

namespace App\Services;

use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Satu sumber kebenaran untuk alur reset password via OTP WhatsApp (Aturan
 * poin 3) - jangan generate/verifikasi OTP di tempat lain (Filament Page,
 * Controller, dsb.), semua wajib lewat service ini.
 */
class PasswordResetOtpService
{
    public function __construct(
        protected WhatsappService $whatsappService,
    ) {}

    /**
     * TODO: ASUMSI - panjang OTP 6 digit, masa berlaku 5 menit, rate limit
     * 1 permintaan per menit per no_telepon. Belum ada key Setting khusus
     * untuk ini karena spec tidak menyebutkan; kalau Admin butuh Setting
     * yang bisa dikonfigurasi (mis. otp_ttl_menit), belum diimplementasikan
     * pada iterasi ini.
     */
    public function kirimOtp(User $user): void
    {
        $rateLimitKey = "otp-reset:{$user->no_telepon}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $detik = RateLimiter::availableIn($rateLimitKey);
            throw new \RuntimeException("Tunggu {$detik} detik sebelum meminta OTP baru.");
        }
        RateLimiter::hit($rateLimitKey, 60);

        $otp = (string) random_int(100000, 999999);

        PasswordResetOtp::query()->where('no_telepon', $user->no_telepon)->delete();
        PasswordResetOtp::create([
            'no_telepon' => $user->no_telepon,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
        ]);

        // eventCode 'reset_password_otp' - TODO: ASUMSI, event BARU di luar 10
        // yang sudah didaftarkan Admin di panel gateway. Wajib dibuat template
        // baru + diisi ke Setting 'wa_template_reset_password_otp'.
        $this->whatsappService->kirimEvent(
            eventCode: 'reset_password_otp',
            nomorTujuan: $user->no_telepon,
            variables: ['nama' => $user->nama, 'otp' => $otp],
            referenceId: "reset-otp-{$user->id}-".now()->timestamp,
        );
    }

    /**
     * @throws \RuntimeException jika OTP salah/kedaluwarsa/tidak ada permintaan
     */
    public function verifikasiDanReset(string $noTelepon, string $otp, string $passwordBaru): void
    {
        $record = PasswordResetOtp::query()
            ->where('no_telepon', $noTelepon)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($otp, $record->otp)) {
            throw new \RuntimeException('Kode OTP salah atau sudah kedaluwarsa.');
        }

        $user = User::query()->where('no_telepon', $noTelepon)->firstOrFail();
        $user->update(['password' => Hash::make($passwordBaru)]);

        $record->delete();
    }
}

```
---

## app/Services/PeminjamanService.php
```php
<?php

namespace App\Services;

use App\Enums\EventTypePoint;
use App\Enums\JenisTransaksi;
use App\Enums\KondisiBuku;
use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Enums\StatusRefund;
use App\Enums\TipeDenda;
use App\Models\Buku;
use App\Models\Denda;
use App\Models\Eksemplar;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Setting;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeminjamanService
{
    public function __construct(
        protected PointService $pointService,
        protected WhatsappService $whatsappService,
    ) {}

    public function bisaMeminjam(User $user): bool
    {
        if ($user->status_suspend) {
            return false;
        }

        $jumlahAktif = Peminjaman::query()
            ->where('user_id', $user->id)
            ->where('status', StatusPeminjaman::Aktif)
            ->count();

        $maxAktif = (int) Setting::get('max_peminjaman_aktif', 3);

        return $jumlahAktif < $maxAktif;
    }

    /**
     * @param  array<int, string>  $eksemplarIds
     */
    public function pinjamBuku(User $user, array $eksemplarIds, ?User $diprosesOleh = null): Transaksi
    {
        if (! $this->bisaMeminjam($user)) {
            throw new RuntimeException('User tidak dapat meminjam: suspend aktif atau limit peminjaman aktif tercapai.');
        }

        $lamaPeminjamanHari = (int) Setting::get('lama_peminjaman_hari', 7);

        $transaksi = DB::transaction(function () use ($user, $eksemplarIds, $diprosesOleh, $lamaPeminjamanHari) {
            $transaksi = Transaksi::create([
                'user_id' => $user->id,
                'jenis' => JenisTransaksi::Peminjaman,
                'diproses_oleh' => $diprosesOleh?->id,
                'tanggal' => now(),
            ]);

            foreach ($eksemplarIds as $eksemplarId) {
                $eksemplar = Eksemplar::query()->lockForUpdate()->findOrFail($eksemplarId);

                if ($eksemplar->status !== StatusEksemplar::Tersedia) {
                    throw new RuntimeException("Eksemplar barcode '{$eksemplar->barcode}' tidak tersedia (status: {$eksemplar->status->value}).");
                }

                $eksemplar->update(['status' => StatusEksemplar::Dipinjam]);

                $peminjaman = Peminjaman::create([
                    'transaksi_id' => $transaksi->id,
                    'user_id' => $user->id,
                    'eksemplar_id' => $eksemplar->id,
                    'tanggal_pinjam' => now()->toDateString(),
                    'tanggal_jatuh_tempo' => now()->addDays($lamaPeminjamanHari)->toDateString(),
                    'status' => StatusPeminjaman::Aktif,
                    'diproses_oleh' => $diprosesOleh?->id,
                ]);

                $this->pointService->catatEvent(
                    $user,
                    EventTypePoint::Peminjaman,
                    'peminjaman',
                    $peminjaman->id,
                );
            }

            return $transaksi->fresh('peminjamans.eksemplar.buku');
        });

        $daftarBuku = $transaksi->peminjamans->pluck('eksemplar.buku.judul')->implode(', ');
        $jatuhTempo = $transaksi->peminjamans->first()?->tanggal_jatuh_tempo;

        $this->whatsappService->kirimEvent(
            eventCode: 'peminjaman_aktif',
            nomorTujuan: $user->no_telepon,
            variables: ['nama' => $user->nama, 'daftar_buku' => $daftarBuku, 'jatuh_tempo' => (string) $jatuhTempo],
            referenceId: "peminjaman-{$transaksi->id}",
        );

        return $transaksi;
    }

    public function prosesPengembalian(
        Peminjaman $peminjaman,
        KondisiBuku $kondisi,
        ?string $catatan = null,
        ?User $diprosesOleh = null,
    ): Pengembalian {
        if (! in_array($peminjaman->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true)) {
            throw new RuntimeException('Peminjaman ini sudah tidak aktif/terlambat, tidak bisa diproses pengembaliannya.');
        }

        $pengembalian = DB::transaction(function () use ($peminjaman, $kondisi, $catatan, $diprosesOleh) {
            $pengembalian = Pengembalian::create([
                'peminjaman_id' => $peminjaman->id,
                'tanggal_kembali' => now()->toDateString(),
                'kondisi' => $kondisi,
                'catatan' => $catatan,
                'diproses_oleh' => $diprosesOleh?->id,
            ]);

            if ($kondisi === KondisiBuku::Hilang) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kehilangan, $this->hitungDendaKehilangan($peminjaman->eksemplar->buku));
                $peminjaman->update(['status' => StatusPeminjaman::Hilang]);
                $peminjaman->eksemplar->update(['status' => StatusEksemplar::Hilang]);

                $this->pointService->catatEvent(
                    $peminjaman->user,
                    EventTypePoint::Kehilangan,
                    'peminjaman',
                    $peminjaman->id,
                );

                return $pengembalian;
            }

            $hariTelat = $this->hitungHariTelat($peminjaman);
            if ($hariTelat > 0) {
                $this->tandaiDenda($peminjaman, TipeDenda::Keterlambatan, $this->hitungDendaKeterlambatan($hariTelat));
            }

            if ($kondisi === KondisiBuku::Rusak) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kerusakan, $this->hitungDendaKerusakan($peminjaman->eksemplar->buku));
                $peminjaman->eksemplar->update(['status' => StatusEksemplar::Rusak]);

                $this->pointService->catatEvent(
                    $peminjaman->user,
                    EventTypePoint::Kerusakan,
                    'peminjaman',
                    $peminjaman->id,
                );
            } else {
                $peminjaman->eksemplar->update(['status' => StatusEksemplar::Tersedia]);
            }

            $peminjaman->update(['status' => StatusPeminjaman::Selesai]);

            $this->pointService->catatEvent(
                $peminjaman->user,
                EventTypePoint::Pengembalian,
                'peminjaman',
                $peminjaman->id,
            );

            return $pengembalian;
        });

        $peminjaman->refresh();
        $this->whatsappService->kirimEvent(
            eventCode: 'pengembalian_diproses',
            nomorTujuan: $peminjaman->user->no_telepon,
            variables: ['nama' => $peminjaman->user->nama, 'kondisi' => $kondisi->value],
            referenceId: "pengembalian-{$pengembalian->id}",
        );

        return $pengembalian;
    }

    public function koreksiKondisiPengembalian(
        Pengembalian $pengembalian,
        KondisiBuku $kondisiBaru,
        ?string $catatan = null,
        ?User $diprosesOleh = null,
    ): Pengembalian {
        $peminjaman = $pengembalian->peminjaman;
        $kondisiLama = $pengembalian->kondisi;

        if ($kondisiLama === $kondisiBaru) {
            throw new RuntimeException('Kondisi baru sama dengan kondisi sebelumnya, tidak ada yang dikoreksi.');
        }

        if (! in_array($peminjaman->status, [StatusPeminjaman::Selesai, StatusPeminjaman::Hilang], true)) {
            throw new RuntimeException('Peminjaman ini belum berstatus final (Selesai/Hilang), gunakan proses pengembalian normal.');
        }

        $pengembalian = DB::transaction(function () use ($pengembalian, $peminjaman, $kondisiLama, $kondisiBaru, $catatan, $diprosesOleh) {
            $statusEksemplarBaru = match ($kondisiBaru) {
                KondisiBuku::Baik => StatusEksemplar::Tersedia,
                KondisiBuku::Rusak => StatusEksemplar::Rusak,
                KondisiBuku::Hilang => StatusEksemplar::Hilang,
            };
            $peminjaman->eksemplar->update(['status' => $statusEksemplarBaru]);

            if ($kondisiLama === KondisiBuku::Rusak && $kondisiBaru !== KondisiBuku::Rusak) {
                $this->batalkanDenda($peminjaman, TipeDenda::Kerusakan);
            }
            if ($kondisiLama === KondisiBuku::Hilang && $kondisiBaru !== KondisiBuku::Hilang) {
                $this->batalkanDenda($peminjaman, TipeDenda::Kehilangan);
            }

            if ($kondisiBaru === KondisiBuku::Rusak && $kondisiLama !== KondisiBuku::Rusak) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kerusakan, $this->hitungDendaKerusakan($peminjaman->eksemplar->buku));
                $this->pointService->catatEvent($peminjaman->user, EventTypePoint::Kerusakan, 'peminjaman', $peminjaman->id, 'Koreksi kondisi ke rusak');
            }
            if ($kondisiBaru === KondisiBuku::Hilang && $kondisiLama !== KondisiBuku::Hilang) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kehilangan, $this->hitungDendaKehilangan($peminjaman->eksemplar->buku));
                $this->pointService->catatEvent($peminjaman->user, EventTypePoint::Kehilangan, 'peminjaman', $peminjaman->id, 'Koreksi kondisi ke hilang');
            }

            // TODO: GAP-SPEC - Point dari kondisi lama tidak di-reverse, sama
            // seperti perilaku sebelum perubahan ini (sudah dikonfirmasi
            // sebelumnya untuk skema Buku lama).

            $peminjaman->update([
                'status' => $kondisiBaru === KondisiBuku::Hilang ? StatusPeminjaman::Hilang : StatusPeminjaman::Selesai,
            ]);

            $pengembalian->update([
                'kondisi' => $kondisiBaru,
                'catatan' => $catatan ?? $pengembalian->catatan,
                'diproses_oleh' => $diprosesOleh?->id,
            ]);

            return $pengembalian->fresh();
        });

        $this->whatsappService->kirimEvent(
            eventCode: 'koreksi_kondisi_pengembalian',
            nomorTujuan: $peminjaman->user->no_telepon,
            variables: ['nama' => $peminjaman->user->nama, 'kondisi_lama' => $kondisiLama->value, 'kondisi_baru' => $kondisiBaru->value],
            referenceId: "koreksi-pengembalian-{$pengembalian->id}",
        );

        return $pengembalian;
    }

    public function laporkanHilang(Peminjaman $peminjaman): Denda
    {
        if (! in_array($peminjaman->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true)) {
            throw new RuntimeException('Peminjaman ini sudah tidak aktif/terlambat, tidak bisa dilaporkan hilang.');
        }

        // Notifikasi 'denda_dibuat' SUDAH dikirim oleh tandaiDenda() di
        // bawah - jangan kirim ulang di sini (dulu menyebabkan reference_id
        // sama dikirim 2x dengan payload nominal berbeda -> 409 dari
        // gateway, lihat kirimEvent()/kirimPesan() §idempotency).
        $denda = DB::transaction(function () use ($peminjaman) {
            $denda = $this->tandaiDenda(
                $peminjaman,
                TipeDenda::Kehilangan,
                $this->hitungDendaKehilangan($peminjaman->eksemplar->buku),
            );

            $peminjaman->update(['status' => StatusPeminjaman::Hilang]);
            $peminjaman->eksemplar->update(['status' => StatusEksemplar::Hilang]);

            $this->pointService->catatEvent(
                $peminjaman->user,
                EventTypePoint::Kehilangan,
                'peminjaman',
                $peminjaman->id,
            );

            return $denda;
        });

        return $denda;
    }

    /**
     * BARU (iterasi ini) - kebalikan dari laporkanHilang(): dipakai KHUSUS
     * untuk Peminjaman yang jadi Hilang lewat laporkanHilang() (belum
     * pernah ada Pengembalian sama sekali). Untuk Peminjaman yang jadi
     * Hilang lewat prosesPengembalian(kondisi: Hilang), gunakan
     * koreksiKondisiPengembalian() sebagai gantinya (ada Pengembalian yang
     * bisa dikoreksi ke KondisiBuku::Baik) - caller (Filament Action) WAJIB
     * memilih method yang tepat berdasarkan ada/tidaknya $peminjaman->pengembalian.
     *
     * TODO: GAP-SPEC - Point dari event Kehilangan TIDAK direverse di sini,
     * konsisten dengan koreksiKondisiPengembalian() (perilaku yang sama
     * sudah dikonfirmasi sebelumnya untuk kasus itu).
     */
    public function bukuDitemukanKembali(Peminjaman $peminjaman): Peminjaman
    {
        if ($peminjaman->status !== StatusPeminjaman::Hilang) {
            throw new RuntimeException('Peminjaman ini tidak berstatus Hilang.');
        }

        if ($peminjaman->pengembalian) {
            throw new RuntimeException('Peminjaman ini sudah punya data Pengembalian - gunakan aksi Koreksi Kondisi di menu Pengembalian, bukan alur ini.');
        }

        $peminjaman = DB::transaction(function () use ($peminjaman) {
            $peminjaman->eksemplar->update(['status' => StatusEksemplar::Tersedia]);
            $peminjaman->update(['status' => StatusPeminjaman::Selesai]);

            // dihitung PeminjamanService - reuse batalkanDenda() (Aturan
            // poin 3), termasuk logika status_refund & notifikasi WA
            // 'denda_dibatalkan_perlu_refund' jika denda sudah lunas.
            $this->batalkanDenda($peminjaman, TipeDenda::Kehilangan);

            return $peminjaman->fresh();
        });

        // TODO: ASUMSI - eventCode 'buku_ditemukan_kembali' BARU, belum ada
        // di daftar template WhatsApp existing. Sebelum Setting
        // 'wa_template_buku_ditemukan_kembali' diisi Admin, kirimEvent()
        // akan skip otomatis (bukan error) - lihat WhatsappService::kirimEvent().
        $this->whatsappService->kirimEvent(
            eventCode: 'buku_ditemukan_kembali',
            nomorTujuan: $peminjaman->user->no_telepon,
            variables: ['nama' => $peminjaman->user->nama],
            referenceId: "buku-ditemukan-{$peminjaman->id}",
        );

        return $peminjaman;
    }

    /**
     * @return array{reminder_h3: int, reminder_h1: int, jadi_terlambat: int}
     */
    public function prosesCronHarian(): array
    {
        $today = Carbon::today();
        $stat = ['reminder_h3' => 0, 'reminder_h1' => 0, 'jadi_terlambat' => 0];

        Peminjaman::query()
            ->where('status', StatusPeminjaman::Aktif)
            ->with('user', 'eksemplar.buku')
            ->chunkById(200, function ($peminjamans) use ($today, &$stat) {
                foreach ($peminjamans as $peminjaman) {
                    $jatuhTempo = Carbon::parse($peminjaman->tanggal_jatuh_tempo);

                    if ($jatuhTempo->isSameDay($today->copy()->addDays(3))) {
                        $this->whatsappService->kirimEvent(
                            eventCode: 'reminder_h3',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->eksemplar->buku->judul, 'jatuh_tempo' => (string) $peminjaman->tanggal_jatuh_tempo],
                            referenceId: "reminder-h3-{$peminjaman->id}-{$today->toDateString()}",
                        );
                        $stat['reminder_h3']++;
                    } elseif ($jatuhTempo->isSameDay($today->copy()->addDay())) {
                        $this->whatsappService->kirimEvent(
                            eventCode: 'reminder_h1',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->eksemplar->buku->judul, 'jatuh_tempo' => (string) $peminjaman->tanggal_jatuh_tempo],
                            referenceId: "reminder-h1-{$peminjaman->id}-{$today->toDateString()}",
                        );
                        $stat['reminder_h1']++;
                    } elseif ($jatuhTempo->lt($today)) {
                        $peminjaman->update(['status' => StatusPeminjaman::Terlambat]);

                        $this->whatsappService->kirimEvent(
                            eventCode: 'jadi_terlambat',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->eksemplar->buku->judul],
                            referenceId: "terlambat-{$peminjaman->id}-{$today->toDateString()}",
                        );
                        $stat['jadi_terlambat']++;
                    }
                }
            });

        return $stat;
    }

    protected function hitungHariTelat(Peminjaman $peminjaman): int
    {
        $jatuhTempo = Carbon::parse($peminjaman->tanggal_jatuh_tempo)->startOfDay();
        $hariIni = Carbon::today();

        if ($hariIni->lessThanOrEqualTo($jatuhTempo)) {
            return 0;
        }

        return $jatuhTempo->diffInDays($hariIni);
    }

    protected function hitungDendaKeterlambatan(int $hariTelat): float
    {
        $tarifPerHari = (float) Setting::get('tarif_denda_per_hari', 500);

        return $hariTelat * $tarifPerHari;
    }

    protected function hitungDendaKerusakan(Buku $buku): float
    {
        $persentase = (float) Setting::get('persentase_denda_kerusakan', 100);

        return round(((float) $buku->harga_ganti) * ($persentase / 100), 2);
    }

    protected function hitungDendaKehilangan(Buku $buku): float
    {
        return (float) $buku->harga_ganti;
    }

    protected function tandaiDenda(Peminjaman $peminjaman, TipeDenda $tipe, float $nominal): Denda
    {
        $denda = Denda::create([
            'peminjaman_id' => $peminjaman->id,
            'user_id' => $peminjaman->user_id,
            'tipe' => $tipe,
            'nominal' => $nominal,
            'status_lunas' => false,
        ]);

        $this->whatsappService->kirimEvent(
            eventCode: 'denda_dibuat',
            nomorTujuan: $peminjaman->user->no_telepon,
            variables: ['nama' => $peminjaman->user->nama, 'tipe' => $tipe->value, 'nominal' => (string) $nominal],
            referenceId: "denda-{$denda->id}",
        );

        return $denda;
    }

    /**
     * TODO: GAP-SPEC - sebelumnya method ini TIDAK PERNAH men-set
     * status_refund maupun mengirim notifikasi 'denda_dibatalkan_perlu_refund'
     * walau komentar keterangan Denda sudah menyebut "perlu refund manual".
     * Diperbaiki (dikonfirmasi): notifikasi WA dikirim KE USER (bukan
     * Admin/Pustakawan) hanya jika denda yang dibatalkan SUDAH TERBAYAR
     * sebelum koreksi - kalau belum terbayar, tidak ada uang yang perlu
     * direfund sehingga tidak perlu status_refund maupun notifikasi.
     * Nominal ASLI ditangkap sebelum di-nol-kan supaya user tahu jumlah
     * yang dibatalkan/perlu direfund.
     */
    protected function batalkanDenda(Peminjaman $peminjaman, TipeDenda $tipe): void
    {
        $denda = Denda::query()
            ->where('peminjaman_id', $peminjaman->id)
            ->where('tipe', $tipe)
            ->latest()
            ->first();

        if (! $denda || (float) $denda->nominal === 0.0) {
            return;
        }

        $sudahTerbayar = $denda->status_lunas;
        $nominalAsli = $denda->nominal; // ditangkap sebelum di-nol-kan, dipakai untuk notifikasi WA

        $denda->update([
            'nominal' => 0,
            'status_lunas' => true,
            'tanggal_lunas' => now(),
            'status_refund' => $sudahTerbayar ? StatusRefund::PerluRefund : $denda->status_refund,
            'keterangan' => trim(($denda->keterangan ? $denda->keterangan . ' | ' : '')
                . ($sudahTerbayar
                    ? 'Dibatalkan otomatis (SUDAH TERBAYAR SEBELUM KOREKSI - perlu refund manual di luar sistem): koreksi kondisi Pengembalian.'
                    : 'Dibatalkan otomatis: koreksi kondisi Pengembalian.')),
        ]);

        if ($sudahTerbayar) {
            // dikirim ke user (dikonfirmasi) - referenceId stabil per denda
            // supaya tidak dobel kirim jika batalkanDenda() ter-trigger ulang
            // untuk denda yang sama (idempotency window gateway §9).
            $this->whatsappService->kirimEvent(
                eventCode: 'denda_dibatalkan_perlu_refund',
                nomorTujuan: $peminjaman->user->no_telepon,
                variables: [
                    'nama' => $peminjaman->user->nama,
                    'tipe' => $tipe->value,
                    'nominal' => (string) $nominalAsli,
                ],
                referenceId: "denda-dibatalkan-refund-{$denda->id}",
            );
        }
    }
}

```
---

## app/Services/PointService.php
```php
<?php

namespace App\Services;

use App\Enums\EventTypePoint;
use App\Models\LevelBadge;
use App\Models\LevelBadgeLog;
use App\Models\Point;
use App\Models\Punishment;
use App\Models\PunishmentLog;
use App\Models\Reward;
use App\Models\RewardLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function __construct(
        protected WhatsappService $whatsappService,
    ) {}

    /**
     * Catat event Point untuk user, lalu jalankan seluruh alur otomatis:
     * update akumulasi -> cek Badge -> cek Reward -> cek Punishment.
     *
     * $refType/$refId: polymorphic manual (bukan Eloquent morph), misal
     * 'peminjaman' + $peminjaman->id.
     */
    public function catatEvent(
        User $user,
        EventTypePoint $eventType,
        ?string $refType = null,
        ?string $refId = null,
        ?string $keterangan = null,
    ): Point {
        // TODO: ASUMSI - key Setting mengikuti pola 'point_{event_type}', mis.
        // 'point_kunjungan', 'point_peminjaman', 'point_kerusakan' (boleh negatif).
        // Spec tidak menyebutkan nama key pasti.
        $nilai = (int) Setting::get("point_{$eventType->value}", 0);

        return DB::transaction(function () use ($user, $eventType, $nilai, $refType, $refId, $keterangan) {
            $point = Point::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'nilai' => $nilai,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'keterangan' => $keterangan,
            ]);

            $user->increment('akumulasi_point', $nilai);
            $user->refresh();

            $this->cekBadge($user);
            $this->cekReward($user);
            $this->cekPunishment($user);

            return $point;
        });
    }

    /**
     * Update level_badge_id user jika akumulasi_point masuk rentang badge
     * lain. Setiap perubahan JUGA dicatat ke LevelBadgeLog (Aturan poin 3,
     * DRY - mengikuti pola RewardLog/PunishmentLog) sebagai riwayat
     * historis, terpisah dari users.level_badge_id yang tetap jadi
     * snapshot terkini.
     */
    protected function cekBadge(User $user): void
    {
        $badge = LevelBadge::query()
            ->where('min_point', '<=', $user->akumulasi_point)
            ->where(function ($q) use ($user) {
                $q->whereNull('max_point')
                    ->orWhere('max_point', '>=', $user->akumulasi_point);
            })
            ->orderByDesc('urutan')
            ->first();

        if ($badge && $badge->id !== $user->level_badge_id) {
            $user->update(['level_badge_id' => $badge->id]);

            LevelBadgeLog::create([
                'user_id' => $user->id,
                'level_badge_id' => $badge->id,
                'tanggal_didapat' => now(),
            ]);

            // eventCode 'badge_naik' - TODO: ASUMSI, samakan dengan Setting
            // wa_template_badge_naik yang harus diisi Admin di panel WA Gateway.
            // TODO: GAP-SPEC - eventCode ini terpicu di SETIAP perubahan badge,
            // termasuk kalau badge turun (bukan hanya naik) - belum
            // dikonfirmasi apakah perlu dipisah jadi badge_naik/badge_turun.
            $this->whatsappService->kirimEvent(
                eventCode: 'badge_naik',
                nomorTujuan: $user->no_telepon,
                variables: ['nama' => $user->nama, 'badge' => $badge->nama_badge],
                referenceId: "badge-{$user->id}-{$badge->id}",
            );
        }
    }

    /**
     * Cek Reward yang tercapai. KEPUTUSAN FINAL (dikonfirmasi): hanya threshold
     * TERTINGGI yang diproses per pemanggilan, bukan seluruh threshold yang
     * terlampaui sekaligus. Reward dengan threshold_point lebih rendah yang
     * belum pernah didapat TIDAK di-backfill jika user melompati beberapa
     * threshold dalam satu event - hanya akan tercatat jika suatu saat menjadi
     * satu-satunya/tertinggi yang eligible.
     */
    protected function cekReward(User $user): void
    {
        $reward = Reward::query()
            ->where('aktif', true)
            ->where('threshold_point', '<=', $user->akumulasi_point)
            ->whereDoesntHave('rewardLogs', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('threshold_point')
            ->first();

        if (! $reward) {
            return;
        }

        $rewardLog = RewardLog::create([
            'user_id' => $user->id,
            'reward_id' => $reward->id,
            'tanggal_didapat' => now(),
        ]);

        // eventCode 'reward_didapat' - TODO: ASUMSI, samakan dengan Setting
        // wa_template_reward_didapat.
        $this->whatsappService->kirimEvent(
            eventCode: 'reward_didapat',
            nomorTujuan: $user->no_telepon,
            variables: ['nama' => $user->nama, 'reward' => $reward->nama],
            referenceId: "reward-{$rewardLog->id}",
        );
    }

    /**
     * Cek Punishment yang tercapai. KEPUTUSAN FINAL (dikonfirmasi): hanya threshold
     * TERTINGGI yang diproses per pemanggilan, bukan seluruh threshold yang
     * terlampaui sekaligus. Punishment dengan threshold_point lebih rendah yang
     * belum pernah didapat TIDAK di-backfill jika user melompati beberapa
     * threshold dalam satu event - hanya akan tercatat jika suatu saat menjadi
     * satu-satunya/tertinggi yang eligible.
     */
    protected function cekPunishment(User $user): void
    {
        $punishment = Punishment::query()
            ->where('aktif', true)
            ->where('threshold_point_minus', '>=', $user->akumulasi_point)
            ->whereDoesntHave('punishmentLogs', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where(function ($q2) {
                        $q2->whereNull('tanggal_berakhir')
                            ->orWhere('tanggal_berakhir', '>', now());
                    });
            })
            ->orderBy('threshold_point_minus')
            ->first();

        if (! $punishment) {
            return;
        }

        $punishmentLog = PunishmentLog::create([
            'user_id' => $user->id,
            'punishment_id' => $punishment->id,
            'tanggal_diterapkan' => now(),
            'tanggal_berakhir' => $punishment->durasi_suspend_hari
                ? now()->addDays($punishment->durasi_suspend_hari)
                : null,
        ]);

        $user->update(['status_suspend' => true]);

        // eventCode 'punishment_diterapkan' - TODO: ASUMSI, samakan dengan
        // Setting wa_template_punishment_diterapkan.
        $this->whatsappService->kirimEvent(
            eventCode: 'punishment_diterapkan',
            nomorTujuan: $user->no_telepon,
            variables: ['nama' => $user->nama, 'alasan' => $punishment->nama],
            referenceId: "punishment-{$punishmentLog->id}",
        );
    }

    /**
     * Reverse SATU Point log (mis. saat koreksi kondisi Pengembalian
     * membatalkan alasan event tersebut). Insert entry Point BARU dengan
     * nilai negasi (bukan hapus log lama - riwayat harus auditable),
     * turunkan akumulasi_point, lalu cek ulang Badge (bisa turun level).
     *
     * TODO: GAP-SPEC - Reward/Punishment yang SUDAH terlanjur didapat dari
     * akumulasi sebelum reversal ini TIDAK ditarik kembali. Alasan: logic
     * cekReward()/cekPunishment() hanya memproses "threshold tertinggi yang
     * belum pernah didapat" (lihat komentar KEPUTUSAN FINAL di method
     * tersebut) - tidak ada mekanisme "un-award" yang terdefinisi di spec,
     * dan menariknya kembali (mis. reward yang sudah dikirim notifikasi WA
     * atau bahkan sudah diklaim fisik) berisiko lebih besar daripada
     * membiarkannya. Ini keputusan produk yang perlu dikonfirmasi terpisah
     * jika ternyata reward/punishment WAJIB ikut di-reverse.
     */
    public function batalkanEvent(
        Point $pointAsli,
        ?string $keterangan = null,
    ): Point {
        return DB::transaction(function () use ($pointAsli, $keterangan) {
            $pointBalik = Point::create([
                'user_id' => $pointAsli->user_id,
                'event_type' => $pointAsli->event_type,
                'nilai' => -$pointAsli->nilai,
                'ref_type' => $pointAsli->ref_type,
                'ref_id' => $pointAsli->ref_id,
                'keterangan' => $keterangan ?? "Pembatalan otomatis dari Point #{$pointAsli->id}",
            ]);

            $user = $pointAsli->user;
            $user->increment('akumulasi_point', -$pointAsli->nilai);
            $user->refresh();

            $this->cekBadge($user);
            // Reward/Punishment sengaja tidak di-cek ulang di sini - lihat
            // TODO: GAP-SPEC di docblock method ini.

            return $pointBalik;
        });
    }

    /**
     * Cari Point log terakhir milik user untuk ref tertentu (dipakai
     * PeminjamanService::batalkanDenda untuk tahu Point mana yang harus
     * di-reverse saat koreksi kondisi). Dibatasi ke event_type spesifik
     * supaya tidak salah mengambil Point dari event lain yang kebetulan
     * punya ref_type/ref_id sama (mis. 'peminjaman'+id yang sama dipakai
     * beberapa EventTypePoint berbeda: Peminjaman, Pengembalian, Kerusakan,
     * Kehilangan).
     */
    public function cariPointTerakhir(
        int $userId,
        EventTypePoint $eventType,
        string $refType,
        string $refId,
    ): ?Point {
        return Point::query()
            ->where('user_id', $userId)
            ->where('event_type', $eventType)
            ->where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->latest()
            ->first();
    }
}

```
---

## app/Services/RfidResolverService.php
```php
<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

/**
 * Resolusi User dari input reader RFID/keyboard-wedge (tersambung ke komputer)
 * untuk konteks Peminjaman/Pengembalian, maupun dari kartu RFID yang dikirim
 * device Attendance Machine (ESP32) untuk konteks Kunjungan. Satu sumber
 * kebenaran untuk matching kartu-ke-user (Aturan poin 3) - jangan menulis ulang
 * query 'no_kartu_rfid' di tempat lain.
 */
class RfidResolverService
{
    /**
     * Cari user berdasarkan nomor kartu RFID saja (tanpa fallback NISN, tanpa
     * throw). Dipakai konteks yang tidak boleh melempar exception, mis.
     * endpoint device (respons 404/"error" per item, bukan 500).
     */
    public function findByKartu(string $kartu): ?User
    {
        return User::query()->where('no_kartu_rfid', $kartu)->first();
    }

    /**
     * @throws RuntimeException jika user tidak ditemukan dari kartu maupun NISN
     */
    public function resolveUser(string $inputKartuAtauNisn): User
    {
        $user = $this->findByKartu($inputKartuAtauNisn);

        if ($user) {
            return $user;
        }

        $user = User::query()->where('nisn', $inputKartuAtauNisn)->first();

        if ($user) {
            return $user;
        }

        throw new RuntimeException(
            "User tidak ditemukan untuk kartu/NISN '{$inputKartuAtauNisn}'. Pastikan kartu sudah didaftarkan atau gunakan NISN yang valid."
        );
    }
}

```
---

## app/Services/SnapshotHarianService.php
```php
<?php

namespace App\Services;

use App\Enums\RoleUser;
use App\Enums\StatusPeminjaman;
use App\Models\Buku;
use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\SnapshotHarian;
use App\Models\User;
use Carbon\Carbon;

/**
 * Satu sumber kebenaran perhitungan snapshot harian lintas-domain (Aturan
 * poin 3, DRY) - dipanggil ProsesCronHarianPerpustakaan (hari berjalan)
 * dan BackfillSnapshotHarian (histori). Metrik di sini adalah PROXY yang
 * sama persis semantiknya dengan yang sebelumnya dihitung live di
 * PeminjamanStatsWidget (lihat TODO: GAP-SPEC di masing-masing method) -
 * hanya waktu eksekusinya yang dipindah dari "tiap request dashboard" ke
 * "sekali per hari", BUKAN perbaikan akurasi proxy itu sendiri.
 */
class SnapshotHarianService
{
    public function catatUntukTanggal(Carbon $tanggal): SnapshotHarian
    {
        $tanggal = $tanggal->copy()->startOfDay();

        return SnapshotHarian::query()->updateOrCreate(
            ['tanggal' => $tanggal->toDateString()],
            [
                // TODO: GAP-SPEC - proxy "peminjaman baru per hari",
                // dipindahkan apa adanya dari PeminjamanStatsWidget.
                'peminjaman_baru' => Peminjaman::query()
                    ->whereDate('tanggal_pinjam', $tanggal)
                    ->count(),

                // TODO: GAP-SPEC - proxy "jatuh tempo pada tanggal tsb DAN
                // berstatus Terlambat SAAT service ini dijalankan" - untuk
                // snapshot hari berjalan (dipanggil setelah
                // PeminjamanService::prosesCronHarian() di command cron),
                // transisi status hari itu sudah tercermin. Untuk backfill
                // histori jauh ke belakang, angka ini merefleksikan status
                // TERKINI record tsb, bukan status riil pada tanggal
                // tersebut (sama seperti sebelumnya, butuh tabel histori
                // status terpisah untuk akurat penuh).
                'peminjaman_terlambat' => Peminjaman::query()
                    ->whereDate('tanggal_jatuh_tempo', $tanggal)
                    ->where('status', StatusPeminjaman::Terlambat)
                    ->count(),

                // TODO: GAP-SPEC - proxy "denda baru terbit per hari".
                'denda_baru' => Denda::query()
                    ->whereDate('created_at', $tanggal)
                    ->count(),

                // Akurat - Kunjungan.tanggal adalah histori peristiwa asli.
                'kunjungan' => Kunjungan::query()
                    ->whereDate('tanggal', $tanggal)
                    ->count(),

                // Akurat - kumulatif, Buku tidak di-hard-delete dalam alur normal.
                'total_judul_buku' => Buku::query()
                    ->whereDate('created_at', '<=', $tanggal->copy()->endOfDay())
                    ->count(),

                // TODO: GAP-SPEC - kumulatif PENDAFTARAN user, tidak
                // memperhitungkan riwayat status_suspend berubah.
                'total_anggota_aktif' => User::query()
                    ->whereNotIn('role', [RoleUser::Admin, RoleUser::Pustakawan])
                    ->whereDate('created_at', '<=', $tanggal->copy()->endOfDay())
                    ->count(),
            ],
        );
    }
}

```
---

## app/Services/WhatsappService.php
```php
<?php

namespace App\Services;

use App\Exceptions\WhatsappGatewayException;
use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Wrapper untuk WhatsApp Gateway (whatsapp.zedlabs.id API v1, autentikasi HMAC-SHA256).
 * Signature dihitung dari raw body bytes persis seperti yang dikirim - lihat
 * dokumen kontrak API bagian 2.1. Jangan format ulang body setelah signing.
 */
class WhatsappService
{
    protected string $baseUrl;

    protected string $apiKeyId;

    protected string $secret;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.whatsapp_gateway.base_url'), '/');
        $this->apiKeyId = (string) config('services.whatsapp_gateway.api_key_id');
        $this->secret = (string) config('services.whatsapp_gateway.secret');
        $this->timeout = (int) config('services.whatsapp_gateway.timeout', 15);
    }

    /**
     * Kirim pesan berbasis template terdaftar di panel gateway.
     * Dipanggil SINKRON oleh KirimNotifikasiWhatsapp job (bukan langsung
     * oleh Controller/Observer/Service lain) - lihat kirimEvent() di bawah.
     *
     * @param  array<string, mixed>  $variables
     * @param  array<string, mixed>|null  $media  Lihat dokumen kontrak API bagian 2.2 (jenis: dokumen|gambar|video|link|kontak)
     * @return array{job_id: string, status: string}
     *
     * @throws WhatsappGatewayException
     */
    public function kirimPesan(
        string $templateCode,
        string $recipient,
        array $variables = [],
        ?array $media = null,
        ?string $referenceId = null,
    ): array {
        $body = [
            'template_code' => $templateCode,
            'recipient' => $recipient,
            'variables' => $variables,
            'media' => $media,
        ];

        if ($referenceId !== null) {
            $body['reference_id'] = $referenceId;
        }

        // json_encode default PHP tanpa spasi tambahan - konsisten dengan body yang
        // ditandatangani. JSON_UNESCAPED_SLASHES/UNICODE agar tidak ada karakter
        // escape tak perlu yang mengubah representasi byte.
        $bodyString = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        [$status, $payload] = $this->kirimRequest('POST', '/api/v1/messages', $bodyString);

        if (! in_array($status, [200, 202], true)) {
            throw new WhatsappGatewayException($status, $payload['error'] ?? 'unknown error');
        }

        return [
            'job_id' => $payload['job_id'] ?? '',
            'status' => $payload['status'] ?? '',
        ];
    }

    /**
     * Ambil status terkini satu job (queued|processing|sent|delivered|read|failed).
     *
     * @return array{job_id: string, status: string, waktu_antre: string, waktu_kirim: string, keterangan_gagal: string}
     *
     * @throws WhatsappGatewayException
     */
    public function ambilStatus(string $jobId): array
    {
        [$status, $payload] = $this->kirimRequest('GET', "/api/v1/messages/{$jobId}", '');

        if ($status !== 200) {
            throw new WhatsappGatewayException($status, $payload['error'] ?? 'unknown error');
        }

        return $payload;
    }

    /**
     * Titik masuk TUNGGAL untuk seluruh notifikasi WA di aplikasi (Aturan
     * poin 3 - Prinsip DRY). Method ini hanya me-resolve template_code dari
     * Setting lalu men-dispatch KirimNotifikasiWhatsapp ke queue 'whatsapp'.
     *
     * ->afterCommit(): pemanggil (PeminjamanService::tandaiDenda,
     * PointService::catatEvent, dsb.) sering berada di dalam DB::transaction.
     * Tanpa afterCommit(), worker queue 'redis' bisa memproses job sebelum
     * transaksi commit (config/queue.php redis tidak set after_commit=true
     * secara global) - kalau transaksi rollback, notifikasi WA sudah
     * terlanjur terkirim untuk data yang batal tersimpan. Jika dipanggil di
     * luar transaksi (tidak ada transaksi aktif), afterCommit() tidak
     * memberi efek tambahan - job tetap dispatch langsung.
     *
     * Key pola: wa_template_{event_code}, mis. 'wa_template_peminjaman_aktif'.
     *
     * TODO: ASUMSI - nama key Setting per event belum ditentukan spec, memakai pola
     * di atas. Admin wajib mengisi Setting ini + membuat/mengaitkan template_code
     * yang sesuai di panel gateway (dok bagian 4.2) sebelum notifikasi terkirim.
     *
     * Jika template belum dikonfigurasi (Setting kosong), pengiriman di-skip dan
     * dicatat sebagai warning - TIDAK di-dispatch ke queue sama sekali, supaya
     * tidak menumpuk job yang pasti gagal karena template_code kosong.
     */
    public function kirimEvent(
        string $eventCode,
        string $nomorTujuan,
        array $variables = [],
        ?string $referenceId = null,
    ): void {
        $templateCode = Setting::get("wa_template_{$eventCode}");
        if (! $templateCode) {
            Log::warning("WhatsappService: template untuk event '{$eventCode}' belum dikonfigurasi di Setting, notifikasi di-skip.");

            return;
        }
        KirimNotifikasiWhatsapp::dispatch(
            $templateCode,
            $nomorTujuan,
            $variables,
            $referenceId ?? (string) Str::uuid(),
        )->onQueue('whatsapp')->afterCommit();
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    protected function kirimRequest(string $method, string $path, string $bodyString): array
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $bodyString, $this->secret);

        $headers = [
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->apiKeyId,
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ];

        $response = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->withBody($bodyString, 'application/json')
            ->send($method, $this->baseUrl.$path);

        return [$response->status(), $response->json() ?? []];
    }
}

```
---

## routes/api.php
```php
<?php

use App\Http\Controllers\Api\PerpustakaanDeviceController;
use Illuminate\Support\Facades\Route;

/*
 * Endpoint Attendance Machine (ESP32-C3) - path WAJIB persis sama dengan
 * apiBaseUrl + path yang dipanggil firmware (lihat firmware v2.3.1):
 *   GET  /api/perpustakaan/ping
 *   GET  /api/perpustakaan/rfid-list/version
 *   GET  /api/perpustakaan/rfid-list
 *   POST /api/perpustakaan/sync-bulk
 *   POST /api/perpustakaan            (kirimLangsung - real-time, SD tidak tersedia)
 *   POST /api/perpustakaan/heartbeat
 *   GET  /api/perpustakaan/config
 *   POST /api/perpustakaan/firmware/check
 *   POST /api/perpustakaan/firmware/report  (BARU - kontrak OTA report, firmware akan disesuaikan)
 *
 * Semua endpoint di bawah prefix ini wajib header X-API-KEY (lihat
 * AuthenticateDeviceApiKey) - firmware mengirim header ini di SETIAP request
 * termasuk GET. Perubahan path/method di sini WAJIB dicek ulang terhadap
 * firmware yang sudah terpasang di lapangan (Aturan poin 17).
 */

Route::prefix('perpustakaan')
    ->middleware('device.api.key')
    ->group(function () {
        Route::get('/ping', [PerpustakaanDeviceController::class, 'ping']);
        Route::get('/rfid-list/version', [PerpustakaanDeviceController::class, 'rfidListVersion']);
        Route::get('/rfid-list', [PerpustakaanDeviceController::class, 'rfidList']);
        Route::post('/sync-bulk', [PerpustakaanDeviceController::class, 'syncBulk']);
        Route::post('/', [PerpustakaanDeviceController::class, 'kirimLangsung']);
        Route::post('/heartbeat', [PerpustakaanDeviceController::class, 'heartbeat']);
        Route::get('/config', [PerpustakaanDeviceController::class, 'config']);
        Route::post('/firmware/check', [PerpustakaanDeviceController::class, 'firmwareCheck']);
        Route::post('/firmware/report', [PerpustakaanDeviceController::class, 'firmwareReport']);
    });

```
---

## routes/console.php
```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Cron harian Peminjaman (Logic Module §8): reminder H-3/H-1 dan transisi
 * ke Terlambat. Dijadwalkan jam 06:00 - SEBELUM jam operasional device RFID
 * default (Setting device_sleep_end_hour, default 05:00) supaya notifikasi
 * WA dan perubahan status sudah selesai saat perpustakaan mulai beroperasi.
 *
 * TODO: GAP-SPEC - jam 06:00 dipilih sebagai baseline aman (asumsi logis,
 * belum ada Setting khusus untuk jam eksekusi cron ini). Jika Admin butuh
 * jam berbeda, sebaiknya dibuat Setting terpisah (mis. 'cron_harian_jam')
 * daripada hardcode - belum diimplementasikan pada iterasi ini.
 *
 * withoutOverlapping(): mencegah eksekusi ganda jika scheduler:run tumpang
 * tindih (mis. proses sebelumnya masih jalan karena data besar).
 * onOneServer(): aman jika deployment multi-server di masa depan.
 */
Schedule::command('perpustakaan:cron-harian')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer();

```
---

## routes/web.php
```php
<?php

use App\Http\Controllers\ChartExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('dashboard');
});

Route::post('/dashboard/chart-export/pdf', [ChartExportController::class, 'pdf'])
    ->middleware(['web', 'auth'])
    ->name('chart-export.pdf');

```
---

## config/app.php
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'Asia/Jakarta',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'id'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'id'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'id_ID'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];

```
---

## config/auth.php
```php
<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];

```
---

## config/cache.php
```php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => env('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "storage", "octane",
    |                    "session", "failover", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'storage' => [
            'driver' => 'storage',
            'disk' => env('CACHE_STORAGE_DISK'),
            'path' => env('CACHE_STORAGE_PATH', 'framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

        'failover' => [
            'driver' => 'failover',
            'stores' => [
                'database',
                'array',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-cache-'),

    /*
    |--------------------------------------------------------------------------
    | Serializable Classes
    |--------------------------------------------------------------------------
    |
    | This value determines the classes that can be unserialized from cache
    | storage. By default, no PHP classes will be unserialized from your
    | cache to prevent gadget chain attacks if your APP_KEY is leaked.
    |
    */

    'serializable_classes' => false,

];

```
---

## config/database.php
```php
<?php

use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];

```
---

## config/filament.php
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | By uncommenting the Laravel Echo configuration, you may connect Filament
    | to any Pusher-compatible websockets server.
    |
    | This will allow your users to receive real-time notifications.
    |
    */

    'broadcasting' => [

        // 'echo' => [
        //     'broadcaster' => 'pusher',
        //     'key' => env('VITE_PUSHER_APP_KEY'),
        //     'cluster' => env('VITE_PUSHER_APP_CLUSTER'),
        //     'wsHost' => env('VITE_PUSHER_HOST'),
        //     'wsPort' => env('VITE_PUSHER_PORT'),
        //     'wssPort' => env('VITE_PUSHER_PORT'),
        //     'authEndpoint' => '/broadcasting/auth',
        //     'disableStats' => true,
        //     'encrypted' => true,
        //     'forceTLS' => env('VITE_PUSHER_SCHEME', 'https') === 'https',
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | This is the storage disk Filament will use to store files. You may use
    | any of the disks defined in the `config/filesystems.php`.
    |
    */

    'default_filesystem_disk' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Temporary File URL Expiry
    |--------------------------------------------------------------------------
    |
    | When Filament generates temporary URLs for previewing private files
    | (file uploads, image columns, image entries, rich editor attachments,
    | etc.), this value controls how many minutes those URLs remain valid.
    |
    | The generated URL's expiry is rounded up to the end of the hour it
    | falls in, so the effective lifetime will be between this value and
    | this value plus up to 60 minutes.
    |
    */

    'temporary_file_url_expiry_minutes' => 30,

    /*
    |--------------------------------------------------------------------------
    | Assets Path
    |--------------------------------------------------------------------------
    |
    | This is the directory where Filament's assets will be published to. It
    | is relative to the `public` directory of your Laravel application.
    |
    | After changing the path, you should run `php artisan filament:assets`.
    |
    */

    'assets_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Cache Path
    |--------------------------------------------------------------------------
    |
    | This is the directory that Filament will use to store cache files that
    | are used to optimize the registration of components.
    |
    | After changing the path, you should run `php artisan filament:cache-components`.
    |
    */

    'cache_path' => base_path('bootstrap/cache/filament'),

    /*
    |--------------------------------------------------------------------------
    | Livewire Loading Delay
    |--------------------------------------------------------------------------
    |
    | This sets the delay before loading indicators appear.
    |
    | Setting this to 'none' makes indicators appear immediately, which can be
    | desirable for high-latency connections. Setting it to 'default' applies
    | Livewire's standard 200ms delay.
    |
    */

    'livewire_loading_delay' => 'default',

    /*
    |--------------------------------------------------------------------------
    | File Generation
    |--------------------------------------------------------------------------
    |
    | Artisan commands that generate files can be configured here by setting
    | configuration flags that will impact their location or content.
    |
    | Often, this is useful to preserve file generation behavior from a
    | previous version of Filament, to ensure consistency between older and
    | newer generated files. These flags are often documented in the upgrade
    | guide for the version of Filament you are upgrading to.
    |
    */

    'file_generation' => [
        'flags' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Route Prefix
    |--------------------------------------------------------------------------
    |
    | This is the prefix used for the system routes that Filament registers,
    | such as the routes for downloading exports and failed import rows.
    |
    */

    'system_route_prefix' => 'filament',

];

```
---

## config/filament-shield.php
```php
<?php

declare(strict_types=1);
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

return [

    /*
    |--------------------------------------------------------------------------
    | Shield Resource
    |--------------------------------------------------------------------------
    |
    | Here you may configure the built-in role management resource. You can
    | customize the URL, choose whether to show model paths, group it under
    | a cluster, and decide which permission tabs to display.
    |
    */

    'shield_resource' => [
        'slug' => 'shield/roles',
        'show_model_path' => true,
        'cluster' => null,
        'tabs' => [
            'pages' => true,
            'widgets' => true,
            'resources' => true,
            'custom_permissions' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | When your application supports teams, Shield will automatically detect
    | and configure the tenant model during setup. This enables tenant-scoped
    | roles and permissions throughout your application.
    |
    */

    'tenant_model' => null,

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This value contains the class name of your user model. This model will
    | be used for role assignments and must implement the HasRoles trait
    | provided by the Spatie\Permission package.
    |
    */

    'auth_provider_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Here you may define a super admin that has unrestricted access to your
    | application. You can choose to implement this via Laravel's gate system
    | or as a traditional role with all permissions explicitly assigned.
    |
    */

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before',
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel User
    |--------------------------------------------------------------------------
    |
    | When enabled, Shield will create a basic panel user role that can be
    | assigned to users who should have access to your Filament panels but
    | don't need any specific permissions beyond basic authentication.
    |
    */

    'panel_user' => [
        'enabled' => true,
        'name' => 'panel_user',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Builder
    |--------------------------------------------------------------------------
    |
    | You can customize how permission keys are generated to match your
    | preferred naming convention and organizational standards. Shield uses
    | these settings when creating permission names from your resources.
    |
    | Supported formats: snake, kebab, pascal, camel, upper_snake, lower_snake
    |
    | Note: The separator must not conflict with the case format's own
    | delimiter. For example, `_` cannot be used with snake/lower_snake/
    | upper_snake, and `-` cannot be used with kebab.
    |
    | When `format_custom_permission_keys` is true (default), custom
    | permissions defined below will have their keys formatted according to
    | the case setting. If your custom permissions come from external sources
    | (e.g. Terraform, Keycloak) and must remain unchanged, set this to false.
    | When using the separator in custom permission definitions, each segment
    | will be formatted independently (e.g. 'view:system_log' with pascal
    | case becomes 'View:SystemLog').
    |
    */

    'permissions' => [
        'separator' => ':',
        'case' => 'pascal',
        'generate' => true,
        'format_custom_permission_keys' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | Shield can automatically generate Laravel policies for your resources.
    | Generated policies mirror each model's location: models under
    | app/Models map into the path below (keeping their nesting), models in
    | any other "Models" directory get a sibling "Policies" directory, and
    | vendor models fall back to the path below. When merge is enabled, the
    | methods below will be combined with any resource-specific methods you
    | define in the resources section.
    |
    */

    'policies' => [
        'path' => app_path('Policies'),
        'merge' => true,
        'generate' => true,
        'methods' => [
            'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore',
            'forceDelete', 'forceDeleteAny', 'restoreAny', 'replicate', 'reorder',
        ],
        'single_parameter_methods' => [
            'viewAny',
            'create',
            'deleteAny',
            'forceDeleteAny',
            'restoreAny',
            'reorder',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Shield supports multiple languages out of the box. When enabled, you
    | can provide translated labels for permissions to create a more
    | localized experience for your international users.
    |
    */

    'localization' => [
        'enabled' => false,
        'key' => 'filament-shield::filament-shield.resource_permission_prefixes_labels',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Here you can fine-tune permissions for specific Filament resources.
    | Use the 'manage' array to override the default policy methods for
    | individual resources, giving you granular control over permissions.
    |
    */

    'resources' => [
        'subject' => 'model',
        'manage' => [
            RoleResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
            ],
        ],
        'exclude' => [
            //
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Most Filament pages only require view permissions. Pages listed in the
    | exclude array will be skipped during permission generation and won't
    | appear in your role management interface.
    |
    */

    'pages' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            Dashboard::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    |
    | Like pages, widgets typically only need view permissions. Add widgets
    | to the exclude array if you don't want them to appear in your role
    | management interface.
    |
    */

    'widgets' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            AccountWidget::class,
            FilamentInfoWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Permissions
    |--------------------------------------------------------------------------
    |
    | Sometimes you need permissions that don't map to resources, pages, or
    | widgets. Define any custom permissions here and they'll be available
    | when editing roles in your application.
    |
    | Keys are formatted per the Permission Builder settings above; set
    | permissions.format_custom_permission_keys to false to use them as-is.
    |
    */

    'custom_permissions' => [],

    /*
    |--------------------------------------------------------------------------
    | Entity Discovery
    |--------------------------------------------------------------------------
    |
    | By default, Shield only looks for entities in your default Filament
    | panel. Enable these options if you're using multiple panels and want
    | Shield to discover entities across all of them.
    |
    */

    'discovery' => [
        'discover_all_resources' => false,
        'discover_all_widgets' => false,
        'discover_all_pages' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Policy
    |--------------------------------------------------------------------------
    |
    | Shield can automatically register a policy for role management itself.
    | This lets you control who can manage roles using Laravel's built-in
    | authorization system. Requires a RolePolicy class in your app.
    |
    */

    'register_role_policy' => true,

];

```
---

## config/filesystems.php
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

```
---

## config/logging.php
```php
<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', env('APP_NAME', 'Laravel')),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];

```
---

## config/mail.php
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

];

```
---

## config/octane.php
```php
<?php

use Laravel\Octane\Contracts\OperationTerminated;
use Laravel\Octane\Events\RequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\TickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Events\WorkerStopping;
use Laravel\Octane\Listeners\CloseMonologHandlers;
use Laravel\Octane\Listeners\CollectGarbage;
use Laravel\Octane\Listeners\DisconnectFromDatabases;
use Laravel\Octane\Listeners\EnsureUploadedFilesAreValid;
use Laravel\Octane\Listeners\EnsureUploadedFilesCanBeMoved;
use Laravel\Octane\Listeners\FlushOnce;
use Laravel\Octane\Listeners\FlushTemporaryContainerInstances;
use Laravel\Octane\Listeners\FlushUploadedFiles;
use Laravel\Octane\Listeners\ReportException;
use Laravel\Octane\Listeners\StopWorkerIfNecessary;
use Laravel\Octane\Octane;

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value determines the default "server" that will be used by Octane
    | when starting, restarting, or stopping your server via the CLI. You
    | are free to change this to the supported server of your choosing.
    |
    | Supported: "roadrunner", "swoole", "frankenphp"
    |
    */

    'server' => env('OCTANE_SERVER', 'roadrunner'),

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS
    |--------------------------------------------------------------------------
    |
    | When this configuration value is set to "true", Octane will inform the
    | framework that all absolute links must be generated using the HTTPS
    | protocol. Otherwise your links may be generated using plain HTTP.
    |
    */

    'https' => env('OCTANE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    |
    | All of the event listeners for Octane's events are defined below. These
    | listeners are responsible for resetting your application's state for
    | the next request. You may even add your own listeners to the list.
    |
    */

    'listeners' => [
        WorkerStarting::class => [
            EnsureUploadedFilesAreValid::class,
            EnsureUploadedFilesCanBeMoved::class,
        ],

        RequestReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            ...Octane::prepareApplicationForNextRequest(),
            //
        ],

        RequestHandled::class => [
            //
        ],

        RequestTerminated::class => [
            // FlushUploadedFiles::class,
        ],

        TaskReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TaskTerminated::class => [
            //
        ],

        TickReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TickTerminated::class => [
            //
        ],

        OperationTerminated::class => [
            FlushOnce::class,
            FlushTemporaryContainerInstances::class,
            // DisconnectFromDatabases::class,
            // CollectGarbage::class,
        ],

        WorkerErrorOccurred::class => [
            ReportException::class,
            StopWorkerIfNecessary::class,
        ],

        WorkerStopping::class => [
            CloseMonologHandlers::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Bindings
    |--------------------------------------------------------------------------
    |
    | The bindings listed below will either be pre-warmed when a worker boots
    | or they will be flushed before every new request. Flushing a binding
    | will force the container to resolve that binding again when asked.
    |
    */

    'warm' => [
        ...Octane::defaultServicesToWarm(),
    ],

    'flush' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Tables
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may define additional tables as required by the
    | application. These tables can be used to store data that needs to be
    | quickly accessed by other workers on the particular Swoole server.
    |
    */

    'tables' => [
        'example:1000' => [
            'name' => 'string:1000',
            'votes' => 'int',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Swoole Cache Table
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may leverage the Octane cache, which is powered
    | by a Swoole table. You may set the maximum number of rows as well as
    | the number of bytes per row using the configuration options below.
    |
    */

    'cache' => [
        'rows' => 1000,
        'bytes' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watching
    |--------------------------------------------------------------------------
    |
    | The following list of files and directories will be watched when using
    | the --watch option offered by Octane. If any of the directories and
    | files are changed, Octane will automatically reload your workers.
    |
    */

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        '.env',
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection Threshold
    |--------------------------------------------------------------------------
    |
    | When executing long-lived PHP scripts such as Octane, memory can build
    | up before being cleared by PHP. You can force Octane to run garbage
    | collection if your application consumes this amount of megabytes.
    |
    */

    'garbage' => 50,

    /*
    |--------------------------------------------------------------------------
    | Maximum Execution Time
    |--------------------------------------------------------------------------
    |
    | The following setting configures the maximum execution time for requests
    | being handled by Octane. You may set this value to 0 to indicate that
    | there isn't a specific time limit on Octane request execution time.
    |
    */

    'max_execution_time' => 30,

    /*
    |--------------------------------------------------------------------------
    | Octane Server State File
    |--------------------------------------------------------------------------
    |
    | This value determines where Octane stores the state file used to track
    | the running server's master process ID and admin endpoint, which is
    | read by various Octane commands. You may tweak this if necessary.
    |
    */

    'state_file' => env('OCTANE_STATE_FILE', storage_path('logs/octane-server-state.json')),

];

```
---

## config/permission.php
```php
<?php

use Spatie\Permission\DefaultTeamResolver;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return [

    'models' => [

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your permissions. Of course, it
         * is often just the "Permission" model but you may use whatever you like.
         *
         * The model you want to use as a Permission model needs to implement the
         * `Spatie\Permission\Contracts\Permission` contract.
         */

        'permission' => Permission::class,

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your roles. Of course, it
         * is often just the "Role" model but you may use whatever you like.
         *
         * The model you want to use as a Role model needs to implement the
         * `Spatie\Permission\Contracts\Role` contract.
         */

        'role' => Role::class,

        /*
         * When using the "Teams" feature from this package, we need to know which
         * Eloquent model should be used to retrieve your teams. Of course, it
         * is often just the "Team" model but you may use whatever you like.
         */
        'team' => null,

        /*
         * When using the "HasModels" trait and passing raw IDs to syncModels,
         * attachModels, or detachModels, this model class will be used to
         * resolve those IDs. If null, defaults to the guard's model.
         */
        'default_model' => null,
    ],

    'table_names' => [

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'roles' => 'roles',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your permissions. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'permissions' => 'permissions',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your models permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_permissions' => 'model_has_permissions',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your models roles. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_roles' => 'model_has_roles',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        /*
         * Change this if you want to name the related pivots other than defaults
         */
        'role_pivot_key' => null, // default 'role_id',
        'permission_pivot_key' => null, // default 'permission_id',

        /*
         * Change this if you want to name the related model primary key other than
         * `model_id`.
         *
         * For example, this would be nice if your primary keys are all UUIDs. In
         * that case, name this `model_uuid`.
         */

        'model_morph_key' => 'model_id',

        /*
         * Change this if you want to use the teams feature and your related model's
         * foreign key is other than `team_id`.
         */

        'team_foreign_key' => 'team_id',
    ],

    /*
     * When set to true, the method for checking permissions will be registered on the gate.
     * Set this to false if you want to implement custom logic for checking permissions.
     */

    'register_permission_check_method' => true,

    /*
     * When set to true, Laravel\Octane\Events\OperationTerminated event listener will be registered
     * this will refresh permissions on every TickTerminated, TaskTerminated and RequestTerminated
     * NOTE: This should not be needed in most cases, but an Octane/Vapor combination benefited from it.
     */
    'register_octane_reset_listener' => false,

    /*
     * Events will fire when a role or permission is assigned/unassigned:
     * \Spatie\Permission\Events\RoleAttachedEvent
     * \Spatie\Permission\Events\RoleDetachedEvent
     * \Spatie\Permission\Events\PermissionAttachedEvent
     * \Spatie\Permission\Events\PermissionDetachedEvent
     *
     * To enable, set to true, and then create listeners to watch these events.
     */
    'events_enabled' => false,

    /*
     * Teams Feature.
     * When set to true the package implements teams using the 'team_foreign_key'.
     * If you want the migrations to register the 'team_foreign_key', you must
     * set this to true before doing the migration.
     * If you already did the migration then you must make a new migration to also
     * add 'team_foreign_key' to 'roles', 'model_has_roles', and 'model_has_permissions'
     * (view the latest version of this package's migration file)
     */

    'teams' => false,

    /*
     * The class to use to resolve the permissions team id
     */
    'team_resolver' => DefaultTeamResolver::class,

    /*
     * Passport Client Credentials Grant
     * When set to true the package will use Passports Client to check permissions
     */

    'use_passport_client_credentials' => false,

    /*
     * When set to true, the required permission names are added to exception messages.
     * This could be considered an information leak in some contexts, so the default
     * setting is false here for optimum safety.
     */

    'display_permission_in_exception' => false,

    /*
     * When set to true, the required role names are added to exception messages.
     * This could be considered an information leak in some contexts, so the default
     * setting is false here for optimum safety.
     */

    'display_role_in_exception' => false,

    /*
     * By default wildcard permission lookups are disabled.
     * See documentation to understand supported syntax.
     */

    'enable_wildcard_permission' => false,

    /*
     * The class to use for interpreting wildcard permissions.
     * If you need to modify delimiters, override the class and specify its name here.
     */
    // 'wildcard_permission' => Spatie\Permission\WildcardPermission::class,

    /* Cache-specific settings */

    'cache' => [

        /*
         * By default all permissions are cached for 24 hours to speed up performance.
         * When permissions or roles are updated the cache is flushed automatically.
         */

        'expiration_time' => DateInterval::createFromDateString('24 hours'),

        /*
         * The cache key used to store all permissions.
         */

        'key' => 'spatie.permission.cache',

        /*
         * You may optionally indicate a specific cache driver to use for permission and
         * role caching using any of the `store` drivers listed in the cache.php config
         * file. Using 'default' here means to use the `default` set in cache.php.
         */

        'store' => 'default',
    ],
];

```
---

## config/queue.php
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis",
    |          "deferred", "background", "failover", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

        'deferred' => [
            'driver' => 'deferred',
        ],

        'background' => [
            'driver' => 'background',
        ],

        'failover' => [
            'driver' => 'failover',
            'connections' => [
                'database',
                'deferred',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];

```
---

## config/services.php
```php
<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp_gateway' => [
        'base_url' => env('WHATSAPP_GATEWAY_BASE_URL', 'https://whatsapp.zedlabs.id'),
        'api_key_id' => env('WHATSAPP_GATEWAY_API_KEY_ID'),
        'secret' => env('WHATSAPP_GATEWAY_SECRET'),
        'timeout' => env('WHATSAPP_GATEWAY_TIMEOUT', 15),
    ],

    'device_gateway' => [
        // Satu key statis untuk seluruh Attendance Machine (ESP32) - lihat
        // AuthenticateDeviceApiKey. Rotasi key wajib disertai reconfigure
        // seluruh device via provisioning mode (poin 17 Aturan).
        'api_key' => env('DEVICE_GATEWAY_API_KEY'),
    ],

];

```
---

## config/session.php
```php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option determines the default session driver that is utilized for
    | incoming requests. Laravel supports a variety of storage options to
    | persist session data. Database storage is a great default choice.
    |
    | Supported: "file", "cookie", "database", "memcached",
    |            "redis", "dynamodb", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to expire immediately when the browser is closed then you may
    | indicate that via the expire_on_close configuration option.
    |
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify that all of your session data
    | should be encrypted before it's stored. All encryption is performed
    | automatically by Laravel and you may use the session like normal.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When utilizing the "file" session driver, the session files are placed
    | on disk. The default storage location is defined here; however, you
    | are free to provide another location where they should be stored.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | When using the "database" or "redis" session drivers, you may specify a
    | connection that should be used to manage these sessions. This should
    | correspond to a connection in your database configuration options.
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table to
    | be used to store sessions. Of course, a sensible default is defined
    | for you; however, you're welcome to change this to another table.
    |
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Cache Store
    |--------------------------------------------------------------------------
    |
    | When using one of the framework's cache driven session backends, you may
    | define the cache store which should be used to store the session data
    | between requests. This must match one of your defined cache stores.
    |
    | Affects: "dynamodb", "memcached", "redis"
    |
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery
    |--------------------------------------------------------------------------
    |
    | Some session drivers must manually sweep their storage location to get
    | rid of old sessions from storage. Here are the chances that it will
    | happen on a given request. By default, the odds are 2 out of 100.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the session cookie that is created by
    | the framework. Typically, you should not need to change this value
    | since doing so does not grant a meaningful security improvement.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | The session cookie path determines the path for which the cookie will
    | be regarded as available. Typically, this will be the root path of
    | your application, but you're free to change this when necessary.
    |
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | This value determines the domain and subdomains the session cookie is
    | available to. By default, the cookie will be available to the root
    | domain without subdomains. Typically, this shouldn't be changed.
    |
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    |
    | By setting this option to true, session cookies will only be sent back
    | to the server if the browser has a HTTPS connection. This will keep
    | the cookie from being sent to you when it can't be done securely.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie and the cookie will only be accessible through
    | the HTTP protocol. It's unlikely you should disable this option.
    |
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines how your cookies behave when cross-site requests
    | take place, and can be used to mitigate CSRF attacks. By default, we
    | will set this value to "lax" to permit secure cross-site requests.
    |
    | See: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value
    |
    | Supported: "lax", "strict", "none", null
    |
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Partitioned Cookies
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will tie the cookie to the top-level site for
    | a cross-site context. Partitioned cookies are accepted by the browser
    | when flagged "secure" and the Same-Site attribute is set to "none".
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Serialization
    |--------------------------------------------------------------------------
    |
    | This value controls the serialization strategy for session data, which
    | is JSON by default. Setting this to "php" allows the storage of PHP
    | objects in the session but can make an application vulnerable to
    | "gadget chain" serialization attacks if the APP_KEY is leaked.
    |
    | Supported: "json", "php"
    |
    */

    'serialization' => 'json',

];

```
---

## database/factories/BukuFactory.php
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BukuFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'judul' => fake()->sentence(3),
            'cover' => fake()->word(),
            'penulis' => fake()->name(),
            'penerbit' => fake()->company(),
            'isbn' => fake()->unique()->isbn13(),
            'tahun_terbit' => fake()->year(),
            'harga_ganti' => fake()->randomFloat(2, 0, 500000),
            'deskripsi' => fake()->text(),
        ];
    }
}

```
---

## database/factories/DendaFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\TipeDenda;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DendaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $statusLunas = fake()->boolean();

        return [
            'peminjaman_id' => Peminjaman::factory(),
            'user_id' => User::factory(),
            'tipe' => fake()->randomElement(TipeDenda::cases()),
            'nominal' => fake()->randomFloat(2, 5000, 500000),
            'status_lunas' => $statusLunas,
            'tanggal_lunas' => $statusLunas ? fake()->dateTime() : null,
            'keterangan' => fake()->text(),
        ];
    }
}

```
---

## database/factories/EksemplarFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\StatusEksemplar;
use App\Models\Buku;
use App\Models\Rak;
use Illuminate\Database\Eloquent\Factories\Factory;

class EksemplarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'buku_id' => Buku::factory(),
            'barcode' => fake()->unique()->ean13(),
            'rak_id' => Rak::factory(),
            'status' => StatusEksemplar::Tersedia,
        ];
    }
}

```
---

## database/factories/KategoriFactory.php
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class KategoriFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->word(),
            'deskripsi' => fake()->text(),
        ];
    }
}

```
---

## database/factories/KunjunganFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\SourceKunjungan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KunjunganFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tanggal' => fake()->date(),
            'jam_tap' => fake()->time(),
            'source' => fake()->randomElement(SourceKunjungan::cases()),
        ];
    }
}

```
---

## database/factories/LevelBadgeFactory.php
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LevelBadgeFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // TODO: GAP-SPEC - min_point dijamin < max_point (asumsi logis; sebelumnya di-random independen dan bisa terbalik)
        $min = fake()->numberBetween(0, 5000);

        return [
            'nama_badge' => fake()->word(),
            'min_point' => $min,
            'max_point' => $min + fake()->numberBetween(100, 5000),
            'icon' => fake()->word(),
            'urutan' => fake()->numberBetween(0, 10),
        ];
    }
}

```
---

## database/factories/PeminjamanFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\StatusPeminjaman;
use App\Models\Eksemplar;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeminjamanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transaksi_id' => Transaksi::factory(),
            'user_id' => User::factory(),
            'eksemplar_id' => Eksemplar::factory(),
            'tanggal_pinjam' => fake()->date(),
            'tanggal_jatuh_tempo' => fake()->date(),
            'status' => fake()->randomElement(StatusPeminjaman::cases()),
            'diproses_oleh' => User::factory(),
        ];
    }
}

```
---

## database/factories/PengembalianFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\KondisiBuku;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PengembalianFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'peminjaman_id' => Peminjaman::factory(),
            'tanggal_kembali' => fake()->date(),
            'kondisi' => fake()->randomElement(KondisiBuku::cases()),
            'catatan' => fake()->text(),
            'diproses_oleh' => User::factory(),
        ];
    }
}

```
---

## database/factories/PointFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\EventTypePoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_type' => fake()->randomElement(EventTypePoint::cases()),
            'nilai' => fake()->numberBetween(-100, 100),
            'ref_type' => fake()->randomElement(['peminjaman', 'pengembalian', 'kunjungan']),
            'ref_id' => fake()->uuid(),
            'keterangan' => fake()->word(),
        ];
    }
}

```
---

## database/factories/PunishmentFactory.php
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PunishmentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->word(),
            'deskripsi' => fake()->text(),
            'threshold_point_minus' => fake()->numberBetween(-10000, -1),
            // durasi_suspend_hari harus positif - dipakai sebagai
            // now()->addDays() di PointService::cekPunishment(). Nilai
            // negatif sebelumnya menghasilkan tanggal_berakhir di masa lalu,
            // membuat punishment otomatis "berakhir" saat baru dibuat.
            'durasi_suspend_hari' => fake()->numberBetween(1, 30),
            'aktif' => fake()->boolean(),
        ];
    }
}

```
---

## database/factories/PunishmentLogFactory.php
```php
<?php

namespace Database\Factories;

use App\Models\Punishment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PunishmentLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'punishment_id' => Punishment::factory(),
            'tanggal_diterapkan' => fake()->dateTime(),
            // TODO: GAP-SPEC - null jika punishment masih aktif/belum berakhir (asumsi logis)
            'tanggal_berakhir' => fake()->boolean(70) ? fake()->dateTime() : null,
        ];
    }
}

```
---

## database/factories/RakFactory.php
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RakFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->word(),
            'lokasi' => fake()->word(),
        ];
    }
}

```
---

## database/factories/RewardFactory.php
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RewardFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->word(),
            'deskripsi' => fake()->text(),
            'threshold_point' => fake()->numberBetween(-10000, 10000),
            'aktif' => fake()->boolean(),
        ];
    }
}

```
---

## database/factories/RewardLogFactory.php
```php
<?php

namespace Database\Factories;

use App\Models\Reward;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RewardLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reward_id' => Reward::factory(),
            'tanggal_didapat' => fake()->dateTime(),
        ];
    }
}

```
---

## database/factories/SettingFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\GroupSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'value' => fake()->text(),
            'group' => fake()->randomElement(GroupSetting::cases()),
            'keterangan' => fake()->word(),
        ];
    }
}

```
---

## database/factories/TransaksiFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\JenisTransaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaksiFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'jenis' => fake()->randomElement(JenisTransaksi::cases()),
            'diproses_oleh' => User::factory(),
            'tanggal' => fake()->dateTime(),
            'keterangan' => fake()->text(),
        ];
    }
}

```
---

## database/factories/UserFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\RoleUser;
use App\Enums\StatusAkademik;
use App\Models\LevelBadge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'avatar' => fake()->word(),
            'nama' => fake()->name(),
            'role' => fake()->randomElement(RoleUser::cases()),
            'nisn' => fake()->unique()->numerify('NISN######'),
            'nip' => fake()->unique()->numerify('NIP##########'),
            // kelas_tahun_pelajaran_id sengaja dibiarkan null (default) -
            // belum ada data master Kelas/TahunPelajaran/KTP di seeder,
            // assignment kelas dilakukan manual lewat Resource setelah
            // data akademik (Jurusan/TahunPelajaran/Kelas/KTP) dibuat.
            'status_akademik' => StatusAkademik::Aktif,
            'jabatan' => fake()->word(),
            'no_telepon' => fake()->unique()->numerify('628##########'),
            'no_kartu_rfid' => fake()->unique()->numerify('########'),
            'password' => Hash::make('password'),
            'status_suspend' => fake()->boolean(),
            'akumulasi_point' => fake()->numberBetween(-10000, 10000),
            'level_badge_id' => LevelBadge::factory(),
        ];
    }
}

```
---

## database/migrations/0001_01_01_000001_create_cache_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->bigInteger('expiration')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};

```
---

## database/migrations/0001_01_01_000002_create_jobs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedSmallInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('connection');
            $table->string('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();

            $table->index(['connection', 'queue', 'failed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};

```
---

## database/migrations/2026_07_29_180455_create_users_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('avatar')->nullable();
            $table->string('nama');
            $table->enum('role', ['siswa', 'pegawai', 'pustakawan', 'super_admin'])->default('siswa');
            $table->string('nis')->nullable()->unique();
            $table->string('nip')->nullable()->unique();
            $table->string('kelas')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('no_telepon')->unique();
            $table->string('no_kartu_rfid')->nullable()->unique();
            // Nullable: user yang hanya pernah login via OTP WhatsApp tidak wajib punya password.
            $table->string('password')->nullable();
            $table->boolean('status_suspend')->default(false);
            $table->integer('akumulasi_point')->default(0);
            // FK ke level_badges ditambahkan di migration terpisah (lihat add_level_badge_fk_to_users_table)
            // karena level_badges dibuat belakangan dalam urutan file.
            $table->uuid('level_badge_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

```
---

## database/migrations/2026_07_29_180456_create_kategoris_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategoris', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategoris');
    }
};

```
---

## database/migrations/2026_07_29_180457_create_raks_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->string('lokasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raks');
    }
};

```
---

## database/migrations/2026_07_29_180458_create_bukus_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bukus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('judul');
            $table->string('cover')->nullable();
            $table->string('penulis')->nullable();
            $table->string('penerbit')->nullable();
            $table->string('isbn')->nullable()->unique();
            $table->string('barcode')->unique();
            $table->foreignUuid('rak_id')->nullable()->constrained('raks');
            $table->decimal('harga_ganti', 10, 2)->default(0);
            $table->integer('stok')->default(1);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukus');
    }
};

```
---

## database/migrations/2026_07_29_180459_create_transaksis_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('jenis', ['peminjaman', 'kunjungan', 'pembayaran_denda'])->default('peminjaman');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users');
            $table->dateTime('tanggal');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};

```
---

## database/migrations/2026_07_29_180500_create_peminjamans_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('peminjamans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaksi_id')->constrained('transaksis');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('buku_id')->constrained('bukus');
            $table->date('tanggal_pinjam');
            $table->date('tanggal_jatuh_tempo');
            $table->enum('status', ['aktif', 'terlambat', 'selesai', 'hilang'])->default('aktif');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjamans');
    }
};

```
---

## database/migrations/2026_07_29_180501_create_pengembalians_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengembalians', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peminjaman_id')->constrained('peminjamans');
            $table->date('tanggal_kembali');
            $table->enum('kondisi', ['baik', 'rusak', 'hilang'])->default('baik');
            $table->text('catatan')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengembalians');
    }
};

```
---

## database/migrations/2026_07_29_180502_create_dendas_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dendas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peminjaman_id')->constrained('peminjamans');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('tipe', ['keterlambatan', 'kerusakan', 'kehilangan']);
            $table->decimal('nominal', 10, 2);
            $table->boolean('status_lunas')->default(false);
            $table->dateTime('tanggal_lunas')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dendas');
    }
};

```
---

## database/migrations/2026_07_29_180503_create_points_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('event_type', ['kunjungan', 'peminjaman', 'pengembalian', 'kerusakan', 'kehilangan']);
            $table->integer('nilai');
            // ref_type/ref_id: polymorphic manual, BUKAN Eloquent morph — lihat PointService
            $table->string('ref_type')->nullable();
            $table->uuid('ref_id')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points');
    }
};

```
---

## database/migrations/2026_07_29_180504_create_level_badges_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_badge');
            $table->integer('min_point');
            $table->integer('max_point')->nullable();
            $table->string('icon')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_badges');
    }
};

```
---

## database/migrations/2026_07_29_180505_create_rewards_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->integer('threshold_point');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};

```
---

## database/migrations/2026_07_29_180506_create_reward_logs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('reward_id')->constrained('rewards');
            $table->dateTime('tanggal_didapat');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_logs');
    }
};

```
---

## database/migrations/2026_07_29_180507_create_punishments_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('punishments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->integer('threshold_point_minus');
            $table->integer('durasi_suspend_hari')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punishments');
    }
};

```
---

## database/migrations/2026_07_29_180508_create_punishment_logs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('punishment_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('punishment_id')->constrained('punishments');
            $table->dateTime('tanggal_diterapkan');
            $table->dateTime('tanggal_berakhir')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punishment_logs');
    }
};

```
---

## database/migrations/2026_07_29_180509_create_kunjungans_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kunjungans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->date('tanggal');
            $table->time('jam_tap');
            $table->enum('source', ['rfid', 'manual'])->default('rfid');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungans');
    }
};

```
---

## database/migrations/2026_07_29_180510_create_settings_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('group', ['peminjaman', 'point', 'notifikasi', 'denda', 'device', 'whatsapp'])->default('peminjaman');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

```
---

## database/migrations/2026_07_29_180511_create_buku_kategori_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buku_kategori', function (Blueprint $table) {
            $table->foreignUuid('buku_id')->constrained('bukus')->cascadeOnDelete();
            $table->foreignUuid('kategori_id')->constrained('kategoris')->cascadeOnDelete();
            $table->primary(['buku_id', 'kategori_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buku_kategori');
    }
};

```
---

## database/migrations/2026_07_29_180512_create_kategori_rak_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_rak', function (Blueprint $table) {
            $table->foreignUuid('kategori_id')->constrained('kategoris')->cascadeOnDelete();
            $table->foreignUuid('rak_id')->constrained('raks')->cascadeOnDelete();
            $table->primary(['kategori_id', 'rak_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_rak');
    }
};

```
---

## database/migrations/2026_07_29_181943_add_level_badge_fk_to_users_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('level_badge_id')->references('id')->on('level_badges');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['level_badge_id']);
        });
    }
};

```
---

## database/migrations/2026_07_29_222935_create_permission_tables.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        throw_if($teams && empty($columnNames['team_foreign_key'] ?? null), 'Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.');

        /**
         * See `docs/prerequisites.md` for suggested lengths on 'name' and 'guard_name' if "1071 Specified key was too long" errors are encountered.
         */
        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->id(); // permission id
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        /**
         * See `docs/prerequisites.md` for suggested lengths on 'name' and 'guard_name' if "1071 Specified key was too long" errors are encountered.
         */
        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->id(); // role id
            if ($teams || config('permission.testing')) { // permission.testing is a fix for sqlite testing
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on($tableNames['roles'])
                ->cascadeOnDelete();
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        throw_if(empty($tableNames), 'Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};

```
---

## database/migrations/2026_07_30_000001_add_unique_user_tanggal_to_kunjungans_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            // Lapisan kedua di DB selain validasi unik-per-hari di device (lihat Logic
            // Module bagian 6). SoftDeletes tidak diikutsertakan di index ini secara
            // sengaja - lihat TODO: GAP-SPEC di bawah.
            $table->unique(['user_id', 'tanggal'], 'kunjungans_user_tanggal_unique');
        });
    }

    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropUnique('kunjungans_user_tanggal_unique');
        });
    }
};

```
---

## database/migrations/2026_07_30_000002_fix_unique_kunjungan_softdelete_aware.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TODO: verifikasi signature terhadap versi package yang terpasang -
 * cabang SQLite di bawah HANYA untuk kebutuhan testing (phpunit.xml
 * memakai DB_CONNECTION=sqlite), TIDAK mengubah perilaku production
 * yang berjalan di MariaDB sama sekali (Aturan poin 16/17 - skema
 * production tidak berubah). SQLite mendukung partial unique index
 * (WHERE clause) yang mencapai efek fungsional sama (unique aktif per
 * user_id+tanggal, mengabaikan baris ter-soft-delete) tanpa perlu
 * generated column STORED yang merupakan sintaks spesifik MariaDB/MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('
                CREATE UNIQUE INDEX kunjungans_unik_aktif_unique
                ON kunjungans (user_id, tanggal)
                WHERE deleted_at IS NULL
            ');

            return;
        }

        // MariaDB mewajibkan index yang meng-cover kolom FK (user_id) selalu ada.
        // Index unique lama adalah satu-satunya index yang mencakup user_id, jadi
        // tambahkan index biasa dulu untuk user_id sebelum index lama di-drop,
        // supaya FK constraint tetap punya index pendukung.
        Schema::table('kunjungans', function ($table) {
            $table->index('user_id', 'kunjungans_user_id_index');
        });

        Schema::table('kunjungans', function ($table) {
            $table->dropUnique('kunjungans_user_tanggal_unique');
        });

        // Generated column: bernilai 'user_id-tanggal' HANYA jika baris aktif
        // (deleted_at IS NULL), NULL jika sudah di-soft-delete. MariaDB
        // memperbolehkan banyak NULL pada unique index, sehingga baris yang
        // sudah di-soft-delete tidak lagi memblokir insert baru dengan
        // kombinasi user_id+tanggal yang sama.
        // Verified: MariaDB 11.8.6 mendukung generated column STORED + unique index.
        DB::statement("
            ALTER TABLE kunjungans
            ADD COLUMN unik_aktif VARCHAR(300)
                GENERATED ALWAYS AS (
                    CASE WHEN deleted_at IS NULL
                        THEN CONCAT(user_id, '-', tanggal)
                        ELSE NULL
                    END
                ) STORED
        ");

        DB::statement('
            ALTER TABLE kunjungans
            ADD UNIQUE INDEX kunjungans_unik_aktif_unique (unik_aktif)
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX kunjungans_unik_aktif_unique');

            return;
        }

        DB::statement('ALTER TABLE kunjungans DROP INDEX kunjungans_unik_aktif_unique');
        DB::statement('ALTER TABLE kunjungans DROP COLUMN unik_aktif');

        Schema::table('kunjungans', function ($table) {
            $table->unique(['user_id', 'tanggal'], 'kunjungans_user_tanggal_unique');
        });

        Schema::table('kunjungans', function ($table) {
            $table->dropIndex('kunjungans_user_id_index');
        });
    }
};

```
---

## database/migrations/2026_07_30_000003_rename_nis_to_nisn_in_users_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('nis', 'nisn');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('nisn', 'nis');
        });
    }
};

```
---

## database/migrations/2026_07_30_000004_create_device_logs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // device_id dari firmware: MAC-based (ESP32_XXXX) atau nama custom jika diisi saat provisioning.
            $table->string('device_id')->unique();
            $table->string('device_name')->nullable();
            $table->string('firmware_version')->nullable();
            $table->unsignedBigInteger('uptime_sec')->default(0);
            $table->unsignedBigInteger('heap_free')->default(0);
            $table->unsignedInteger('pending_records')->default(0);
            $table->unsignedInteger('scan_today')->default(0);
            $table->integer('rssi')->default(0);
            $table->boolean('sd_ok')->default(false);
            $table->unsignedInteger('rfid_db_entries')->default(0);
            $table->boolean('online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_logs');
    }
};

```
---

## database/migrations/2026_07_30_000005_create_firmware_releases_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firmware_releases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('version')->unique(); // format semver: x.y.z, dibandingkan dengan compareFirmwareVersion() di firmware
            $table->string('url'); // URL binary .bin, wajib https, wajib bisa diverifikasi lewat X-API-KEY yang sama
            $table->string('md5')->nullable();
            $table->boolean('aktif')->default(true);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmware_releases');
    }
};

```
---

## database/migrations/2026_07_30_000006_create_password_reset_otps_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel baru murni (tidak mengubah tabel existing) untuk OTP reset password
 * via WhatsApp - user tidak punya kolom email, jadi Password Broker Laravel
 * (email + password_reset_tokens) tidak dipakai. Aman di-rollback kapan pun,
 * tidak berdampak ke data peminjaman/denda/point (poin 16 Aturan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->string('no_telepon')->index();
            $table->string('otp'); // disimpan hashed (Hash::make), bukan plain
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};

```
---

## database/migrations/2026_07_30_000007_add_indexes_untuk_performa_query.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index tambahan sesuai Logic Module §11 checklist - kolom yang sering
 * di-query: filter status Peminjaman aktif/terlambat (cron harian, cek
 * limit peminjaman), sort/filter jatuh tempo (cron reminder), filter Denda
 * belum lunas (DendaObserver, halaman "denda saya"), dan unique-per-hari
 * Kunjungan (RFID). Additive only - tidak mengubah data/kolom existing,
 * aman rollback via dropIndex().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->index('status');
            $table->index('tanggal_jatuh_tempo');
        });

        Schema::table('dendas', function (Blueprint $table) {
            $table->index('status_lunas');
        });

        Schema::table('kunjungans', function (Blueprint $table) {
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['tanggal_jatuh_tempo']);
        });

        Schema::table('dendas', function (Blueprint $table) {
            $table->dropIndex(['status_lunas']);
        });

        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropIndex(['tanggal']);
        });
    }
};

```
---

## database/migrations/2026_07_30_000008_add_status_refund_to_dendas_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menutup gap refund (lihat PeminjamanService::batalkanDenda). Saat Denda
 * yang SUDAH TERBAYAR dibatalkan (koreksi kondisi Pengembalian), sistem
 * tidak bisa mengembalikan uang secara otomatis - kolom ini hanya menandai
 * status refund manual di luar sistem, supaya Admin/Pustakawan punya
 * catatan tugas yang jelas, bukan hilang begitu saja di kolom 'keterangan'.
 *
 * PERUBAHAN SKEMA EKSPLISIT (Aturan poin 16): kolom baru, nullable,
 * default 'tidak_perlu' - tidak mengubah/menghapus kolom existing, aman
 * untuk data produksi yang sudah ada (semua baris lama otomatis
 * 'tidak_perlu', tidak salah secara historis karena Denda lama tidak
 * pernah dibatalkan oleh mekanisme ini).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dendas', function (Blueprint $table) {
            $table->enum('status_refund', ['tidak_perlu', 'perlu_refund', 'sudah_direfund'])
                ->default('tidak_perlu')
                ->after('status_lunas');
        });
    }

    public function down(): void
    {
        Schema::table('dendas', function (Blueprint $table) {
            $table->dropColumn('status_refund');
        });
    }
};

```
---

## database/migrations/2026_07_31_051302_create_imports_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('importer');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')->default(0);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};

```
---

## database/migrations/2026_07_31_051303_create_exports_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_disk');
            $table->string('file_name')->nullable();
            $table->string('exporter');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')->default(0);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};

```
---

## database/migrations/2026_07_31_051304_create_failed_import_rows_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('failed_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->json('data');
            $table->foreignId('import_id')->constrained()->cascadeOnDelete();
            $table->text('validation_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_import_rows');
    }
};

```
---

## database/migrations/2026_07_31_052251_create_notifications_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

```
---

## database/migrations/2026_08_01_000001_create_jurusans_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurusans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->string('kode')->unique();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurusans');
    }
};

```
---

## database/migrations/2026_08_01_000002_create_tahun_pelajarans_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama')->unique(); // mis. "2025/2026"
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('aktif')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_pelajarans');
    }
};

```
---

## database/migrations/2026_08_01_000003_create_kelas_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama'); // mis. "X IPA 1"
            $table->unsignedTinyInteger('tingkat'); // 10, 11, 12 - dipakai urutan kenaikan
            $table->uuid('jurusan_id')->nullable();
            $table->foreign('jurusan_id')->references('id')->on('jurusans')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};

```
---

## database/migrations/2026_08_01_000004_create_kelas_tahun_pelajarans_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_tahun_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kelas_id');
            $table->foreign('kelas_id')->references('id')->on('kelas')->cascadeOnDelete();
            $table->uuid('tahun_pelajaran_id');
            $table->foreign('tahun_pelajaran_id')->references('id')->on('tahun_pelajarans')->cascadeOnDelete();
            $table->foreignId('wali_kelas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['kelas_id', 'tahun_pelajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_tahun_pelajarans');
    }
};

```
---

## database/migrations/2026_08_01_000005_create_riwayat_kelas_siswas_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('kelas_tahun_pelajaran_id');
            $table->foreign('kelas_tahun_pelajaran_id', 'rks_ktp_fk')
                ->references('id')->on('kelas_tahun_pelajarans')->cascadeOnDelete();
            $table->enum('status', ['aktif', 'naik', 'tinggal', 'lulus', 'keluar'])->default('aktif');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['user_id', 'kelas_tahun_pelajaran_id'], 'rks_user_ktp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kelas_siswas');
    }
};

```
---

## database/migrations/2026_08_01_000006_replace_kelas_column_in_users_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MENGUBAH SKEMA users - dampak ke data existing (Aturan poin 16).
 * Kolom 'kelas' (string bebas) DIHAPUS, diganti relasi
 * kelas_tahun_pelajaran_id + status_akademik. Data lama di kolom
 * 'kelas' string TIDAK dimigrasikan otomatis ke KTP (karena tidak ada
 * mapping otomatis nama-string -> Kelas/TahunPelajaran yang valid) -
 * WAJIB assignment ulang manual oleh Admin setelah migrasi ini jalan.
 *
 * TODO: GAP-SPEC - pertimbangkan backup nilai 'kelas' lama (mis. ke
 * kolom 'kelas_lama_arsip') sebelum drop, supaya Admin punya rujukan
 * saat assignment ulang manual. BELUM diimplementasikan di migration
 * ini - konfirmasi dulu apakah diperlukan sebelum dijalankan ke
 * production yang sudah ada data siswa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kelas');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('kelas_tahun_pelajaran_id')->nullable()->after('jabatan');
            $table->foreign('kelas_tahun_pelajaran_id', 'users_ktp_fk')
                ->references('id')->on('kelas_tahun_pelajarans')->nullOnDelete();
            $table->string('status_akademik')->default('aktif')->after('kelas_tahun_pelajaran_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_ktp_fk');
            $table->dropColumn(['kelas_tahun_pelajaran_id', 'status_akademik']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('kelas')->nullable();
        });
    }
};

```
---

## database/migrations/2026_08_01_000007_drop_unique_riwayat_kelas_siswas.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PERUBAHAN SKEMA - riwayat_kelas_siswas (Aturan poin 16).
 *
 * Constraint unik lama ('rks_user_ktp_unique' pada [user_id,
 * kelas_tahun_pelajaran_id]) hanya mengizinkan SATU baris riwayat
 * sepanjang masa untuk kombinasi siswa+KTP tertentu. Ini keliru secara
 * desain: RiwayatKelasSiswa adalah log historis, bukan status tunggal -
 * siswa yang keluar dari suatu KTP lalu di-assign kembali ke KTP yang
 * sama di kemudian hari (dikonfirmasi sebagai skenario valid) butuh
 * baris riwayat baru untuk pasangan yang sama.
 *
 * Constraint unik DIHAPUS TOTAL (dikonfirmasi: cukup mengandalkan logic
 * aplikasi) - KenaikanKelasService::tutupRiwayatAktif() tetap menjadi
 * satu-satunya penjaga agar tidak ada dua baris status='aktif' untuk
 * user yang sama secara bersamaan (Aturan poin 3, DRY - satu sumber
 * kebenaran di service, bukan di constraint DB).
 *
 * PENTING - urutan operasi: index baru dibuat TERLEBIH DAHULU sebelum
 * index lama di-drop. Index 'rks_user_ktp_unique' (dimulai dari kolom
 * user_id) dipakai MySQL sebagai index pendukung foreign key user_id
 * (foreignId('user_id')->constrained('users')) - men-drop-nya tanpa ada
 * index pengganti akan gagal dengan error 1553 "needed in a foreign key
 * constraint". Index baru (user_id, status) dimulai dari kolom yang
 * sama sehingga bisa langsung menggantikan peran tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'rks_user_status_idx');
        });

        Schema::table('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->dropUnique('rks_user_ktp_unique');
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->unique(['user_id', 'kelas_tahun_pelajaran_id'], 'rks_user_ktp_unique');
        });

        Schema::table('riwayat_kelas_siswas', function (Blueprint $table) {
            $table->dropIndex('rks_user_status_idx');
        });
    }
};

```
---

## database/migrations/2026_08_02_000001_add_unique_nama_to_kelas_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MENGUBAH SKEMA kelas - dampak ke data existing (Aturan poin 16).
 * 'nama' dibuat unik secara global (dikonfirmasi) - sebelumnya dua Kelas
 * beda Jurusan boleh punya nama sama (mis. "X-1" di IPA dan "X-1" di
 * IPS). VERIFIKASI SEBELUM MIGRATE: dicek via tinker pada tanggal
 * pembuatan migration ini, hasil 0 baris nama Kelas duplikat - AMAN
 * dijalankan saat itu. Jika ada Kelas baru ditambahkan antara saat
 * verifikasi dan saat migration ini benar-benar dijalankan di
 * production, migration akan GAGAL (bukan menghapus/mengubah data
 * diam-diam) - cek ulang duplikat sebelum migrate jika jeda waktunya
 * lama.
 *
 * TODO: GAP-SPEC - unique index standar TIDAK soft-delete aware (mirip
 * kasus yang sudah ditangani untuk Kunjungan di migration
 * 2026_07_30_000002_fix_unique_kunjungan_softdelete_aware.php). Artinya
 * Kelas yang sudah di-soft-delete tetap "menahan" nama-nya - admin
 * tidak akan bisa membuat Kelas baru dengan nama yang sama sampai
 * Kelas lama di-restore atau di-force-delete. BELUM dikonfirmasi apakah
 * perilaku ini bisa diterima atau perlu unique index partial/composite
 * dengan deleted_at seperti pola Kunjungan - dibiarkan standar dulu
 * sampai ada keputusan eksplisit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->unique('nama', 'kelas_nama_unique');
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropUnique('kelas_nama_unique');
        });
    }
};

```
---

## database/migrations/2026_08_02_000002_create_eksemplars_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eksemplars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('buku_id')->constrained('bukus')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('barcode')->unique();
            $table->foreignUuid('rak_id')->nullable()->constrained('raks');
            $table->enum('status', ['tersedia', 'dipinjam', 'rusak', 'hilang'])->default('tersedia');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eksemplars');
    }
};

```
---

## database/migrations/2026_08_02_000003_alter_bukus_table_drop_barcode_rak_stok.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // TODO: verifikasi driver DB (sqlite dev vs mysql prod) sebelum jalan -
    // dropColumn multi-kolom + foreign key butuh doctrine/dbal di SQLite
    // pada beberapa versi Laravel. Cek composer.json dulu.
    public function up(): void
    {
        Schema::table('bukus', function (Blueprint $table) {
            $table->dropForeign(['rak_id']);
            $table->dropColumn(['barcode', 'rak_id', 'stok']);
        });
    }

    public function down(): void
    {
        Schema::table('bukus', function (Blueprint $table) {
            $table->string('barcode')->unique()->nullable();
            $table->foreignUuid('rak_id')->nullable()->constrained('raks');
            $table->integer('stok')->default(1);
        });
    }
};

```
---

## database/migrations/2026_08_02_000004_alter_peminjamans_table_buku_to_eksemplar.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['buku_id']);
            $table->dropColumn('buku_id');
            $table->foreignUuid('eksemplar_id')->after('transaksi_id')->constrained('eksemplars');
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['eksemplar_id']);
            $table->dropColumn('eksemplar_id');
            $table->foreignUuid('buku_id')->constrained('bukus');
        });
    }
};

```
---

## database/migrations/2026_08_02_000005_add_tahun_terbit_to_bukus_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bukus', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun_terbit')->nullable()->after('isbn');
        });
    }

    public function down(): void
    {
        Schema::table('bukus', function (Blueprint $table) {
            $table->dropColumn('tahun_terbit');
        });
    }
};

```
---

## database/migrations/2026_08_02_000006_create_level_badge_logs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log historis perubahan LevelBadge user (Aturan poin 3 - DRY, mengikuti
 * pola RewardLog/PunishmentLog). Kolom users.level_badge_id TETAP ada dan
 * TETAP jadi sumber snapshot terkini (dipakai PointService::cekBadge()
 * untuk cek cepat tanpa query log) - tabel ini murni tambahan append-only,
 * TIDAK mengubah struktur/data tabel users.
 *
 * TODO: GAP-SPEC - histori baru mulai tercatat sejak migration ini
 * dijalankan (dikonfirmasi user). Badge yang sudah nempel ke user
 * SEBELUM migration ini tidak akan muncul di riwayat karena memang belum
 * pernah ter-log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_badge_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('level_badge_id')->constrained('level_badges');
            $table->dateTime('tanggal_didapat');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_badge_logs');
    }
};

```
---

## database/migrations/2026_08_02_000007_make_eksemplar_id_nullable_in_peminjamans_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GAP-SPEC ditutup (dikonfirmasi user): force-delete Buku/Eksemplar yang
 * punya riwayat Peminjaman DIIZINKAN - eksemplar_id di riwayat Peminjaman
 * jadi null (Opsi B), bukan RESTRICT (penyebab error 1451 sebelumnya).
 * Riwayat Peminjaman/Denda/Point TETAP ada, hanya jejak eksemplar fisik
 * yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['eksemplar_id']);
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->uuid('eksemplar_id')->nullable()->change();
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->foreign('eksemplar_id')
                ->references('id')->on('eksemplars')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['eksemplar_id']);
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->uuid('eksemplar_id')->nullable(false)->change();
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->foreign('eksemplar_id')
                ->references('id')->on('eksemplars');
        });
    }
};

```
---

## database/migrations/2026_08_02_000008_add_ota_report_columns_to_device_logs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kontrak BARU (belum ada di firmware lama) - endpoint report status OTA
 * akan ditambahkan ke firmware bersamaan dengan perubahan ini (dikonfirmasi
 * user: kode firmware akan disesuaikan). Field mengikuti keputusan yang
 * dikonfirmasi: simpan di DeviceLog existing, bukan tabel baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_logs', function (Blueprint $table) {
            $table->string('ota_status')->nullable()->after('firmware_version');
            $table->text('ota_error')->nullable()->after('ota_status');
            $table->timestamp('ota_reported_at')->nullable()->after('ota_error');
        });
    }

    public function down(): void
    {
        Schema::table('device_logs', function (Blueprint $table) {
            $table->dropColumn(['ota_status', 'ota_error', 'ota_reported_at']);
        });
    }
};

```
---

## database/migrations/2026_08_02_000009_create_login_otps_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel baru murni untuk OTP LOGIN (beda dari password_reset_otps yang
 * dipakai alur reset password) - dipisah supaya semantik jelas: verifikasi
 * di sini TIDAK mengubah password, hanya men-trigger Auth::login().
 * Aman di-rollback, tidak berdampak ke data users/peminjaman/denda/point.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_otps', function (Blueprint $table) {
            $table->id();
            $table->string('no_telepon')->index();
            $table->string('otp'); // disimpan hashed (Hash::make), bukan plain
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_otps');
    }
};

```
---

## database/migrations/2026_08_02_000010_add_jenis_kelamin_to_users_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom baru murni, nullable, default null - AMAN untuk data users
 * existing (poin 16 Aturan). Data lama tetap null sampai diisi manual
 * lewat form UserResource (tidak ada backfill otomatis - tidak ada
 * sumber data untuk menebak jenis kelamin user lama).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable()->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('jenis_kelamin');
        });
    }
};

```
---

## database/migrations/2026_08_02_000011_create_whatsapp_logs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel baru murni - tidak mengubah tabel/job existing secara struktural
 * (poin 16/17 Aturan). Satu baris per reference_id (upsert di job, bukan
 * insert per percobaan) supaya retry job.tries=3 tidak menumpuk baris -
 * kolom percobaan_ke merekam berapa kali handle() dijalankan untuk
 * reference_id yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('reference_id')->nullable()->unique();
            $table->string('template_code');
            $table->string('nomor_tujuan');
            $table->json('variables')->nullable();
            $table->enum('status', ['terkirim', 'gagal_transient', 'gagal_permanen'])->default('gagal_transient');
            $table->text('keterangan')->nullable();
            $table->unsignedTinyInteger('percobaan_ke')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};

```
---

## database/migrations/2026_08_02_000012_create_snapshot_harians_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel baru murni - tidak mengubah tabel/data existing (poin 16 Aturan).
 * Satu baris per tanggal, diisi SnapshotHarianService::catatUntukTanggal()
 * lewat cron harian (hari berjalan) dan command backfill (histori).
 * Menggantikan query agregat berulang di PeminjamanStatsWidget yang
 * sebelumnya dihitung ulang setiap dashboard dibuka (Aturan poin 3/9 -
 * performa untuk skala data besar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snapshot_harians', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->unsignedInteger('peminjaman_baru')->default(0);
            $table->unsignedInteger('peminjaman_terlambat')->default(0);
            $table->unsignedInteger('denda_baru')->default(0);
            $table->unsignedInteger('kunjungan')->default(0);
            $table->unsignedInteger('total_judul_buku')->default(0);
            $table->unsignedInteger('total_anggota_aktif')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshot_harians');
    }
};

```
---

## database/seeders/DatabaseSeeder.php
```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(SettingSeeder::class);

        User::factory()->create([
            'nama' => 'Admin Perpustakaan',
            'role' => 'super_admin',
            'no_telepon' => '62895351856267',
            'password' => Hash::make('password'),
        ]);

        $this->call(ShieldSeeder::class);
    }
}

```
---

## database/seeders/SettingSeeder.php
```php
<?php

namespace Database\Seeders;

use App\Enums\GroupSetting;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Baseline Setting agar aplikasi tidak diam-diam berjalan dengan default
 * hardcode di kode (Setting::get($key, $default)). Nilai berkategori
 * "bisnis" (bukan teknis/device) ditandai TODO: ASUMSI - wajib direview
 * Admin lewat panel sebelum dianggap final, terutama nilai Point yang
 * menentukan kecepatan naik Badge dan pemicu Punishment.
 *
 * wa_template_* SEKARANG ikut diseed (berubah dari iterasi sebelumnya) -
 * template_code yang dipakai di bawah ini diasumsikan SAMA PERSIS dengan
 * "Kode Template" pada dokumen Template WhatsApp - Perpustakaan (11 event).
 * TODO: ASUMSI - WAJIB dicek ulang terhadap template_code yang benar-benar
 * terdaftar di panel gateway zedlabs; kalau berbeda, WhatsappService akan
 * mengirim template_code yang salah dan gateway akan menolak (lihat kontrak
 * API dok bagian 2.2, kemungkinan respons 4xx dari WhatsappGatewayException).
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // --- Kategori A: teknis/device - konsisten dengan default firmware ESP32 ---
            ['key' => 'rfid_db_ver', 'value' => '0', 'group' => GroupSetting::Device, 'keterangan' => 'Versi daftar kartu RFID aktif, dinaikkan otomatis oleh UserObserver.'],
            ['key' => 'device_sleep_start_hour', 'value' => '18', 'group' => GroupSetting::Device, 'keterangan' => 'Jam mulai device deep sleep (0-23).'],
            ['key' => 'device_sleep_end_hour', 'value' => '5', 'group' => GroupSetting::Device, 'keterangan' => 'Jam device bangun dari deep sleep (0-23).'],
            ['key' => 'device_oled_dim_start_hour', 'value' => '8', 'group' => GroupSetting::Device, 'keterangan' => 'Jam mulai OLED device dimatikan sementara (0-23).'],
            ['key' => 'device_oled_dim_end_hour', 'value' => '12', 'group' => GroupSetting::Device, 'keterangan' => 'Jam OLED device kembali menyala (0-23).'],
            ['key' => 'device_sync_interval_ms', 'value' => '300000', 'group' => GroupSetting::Device, 'keterangan' => 'Interval sinkronisasi data offline device ke server (ms).'],
            ['key' => 'device_ota_check_interval_ms', 'value' => '30000', 'group' => GroupSetting::Device, 'keterangan' => 'Interval device mengecek update firmware (ms).'],

            // --- Kategori B.1: aturan peminjaman & denda ---
            // TODO: ASUMSI - baseline dari default fallback di PeminjamanService, wajib direview Admin.
            ['key' => 'max_peminjaman_aktif', 'value' => '3', 'group' => GroupSetting::Peminjaman, 'keterangan' => 'TODO: ASUMSI - maksimal jumlah Peminjaman berstatus aktif per user.'],
            ['key' => 'lama_peminjaman_hari', 'value' => '7', 'group' => GroupSetting::Peminjaman, 'keterangan' => 'TODO: ASUMSI - masa pinjam dalam hari sejak tanggal_pinjam.'],
            ['key' => 'tarif_denda_per_hari', 'value' => '500', 'group' => GroupSetting::Denda, 'keterangan' => 'TODO: ASUMSI - tarif denda keterlambatan per hari (rupiah).'],
            ['key' => 'persentase_denda_kerusakan', 'value' => '100', 'group' => GroupSetting::Denda, 'keterangan' => 'TODO: ASUMSI - persentase dari Buku.harga_ganti untuk denda kerusakan.'],

            // --- Kategori B.2: nilai Point per event ---
            // TODO: ASUMSI - angka belum ditentukan spec, dipilih sebagai baseline awal
            // supaya sistem Badge/Reward/Punishment tidak mati total (default kode = 0).
            // Kerusakan/Kehilangan sengaja negatif sesuai Logic Module §4.
            ['key' => 'point_kunjungan', 'value' => '1', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point per kunjungan (tap RFID).'],
            ['key' => 'point_peminjaman', 'value' => '2', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point per buku dipinjam.'],
            ['key' => 'point_pengembalian', 'value' => '3', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point per pengembalian kondisi baik/tepat waktu.'],
            ['key' => 'point_kerusakan', 'value' => '-10', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point (negatif) saat buku dikembalikan rusak.'],
            ['key' => 'point_kehilangan', 'value' => '-20', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point (negatif) saat buku dilaporkan/berstatus hilang.'],

            // --- Kategori C: template_code WhatsApp (11 event) ---
            // TODO: ASUMSI - value di bawah diasumsikan sama persis dengan template_code
            // yang Anda daftarkan di panel gateway zedlabs. WAJIB dicocokkan manual.
            ['key' => 'wa_template_peminjaman_aktif', 'value' => 'peminjaman_aktif', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_reminder_h3', 'value' => 'reminder_h3', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_reminder_h1', 'value' => 'reminder_h1', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_jadi_terlambat', 'value' => 'jadi_terlambat', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_pengembalian_diproses', 'value' => 'pengembalian_diproses', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_denda_dibuat', 'value' => 'denda_dibuat', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_denda_lunas', 'value' => 'denda_lunas', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_badge_naik', 'value' => 'badge_naik', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_reward_didapat', 'value' => 'reward_didapat', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_punishment_diterapkan', 'value' => 'punishment_diterapkan', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_reset_password_otp', 'value' => 'reset_password_otp', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway.'],
            ['key' => 'wa_template_koreksi_kondisi_pengembalian', 'value' => 'koreksi_kondisi_pengembalian', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway. Dikirim saat Pustakawan/Admin mengoreksi kondisi Pengembalian yang sudah final.'],
            ['key' => 'wa_template_denda_dibatalkan_perlu_refund', 'value' => 'denda_dibatalkan_perlu_refund', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway. Dikirim saat Denda yang SUDAH TERBAYAR dibatalkan akibat koreksi kondisi - Admin wajib menindaklanjuti refund manual (lihat Denda.status_refund).'],
            ['key' => 'wa_template_login_otp', 'value' => 'login_otp', 'group' => GroupSetting::Whatsapp, 'keterangan' => 'TODO: ASUMSI - cocokkan dengan template_code di panel gateway. Dikirim saat user login via OTP WhatsApp (setara reset password, tapi TIDAK mengubah password).'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'keterangan' => $setting['keterangan'],
                ]
            );
        }
    }
}

```
---

## database/seeders/ShieldSeeder.php
```php
<?php

namespace Database\Seeders;

use App\Enums\RoleUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        // Permission manual untuk halaman non-Resource (LaporanBulanan,
        // PengaturanSistem) - Shield tidak auto-generate permission untuk
        // Filament Page biasa (hanya untuk Resource).
        Permission::firstOrCreate([
            'name' => 'ViewAny:LaporanBulanan',
            'guard_name' => 'web',
        ]);

        // BARU iterasi ini - sengaja TIDAK dimasukkan ke daftar permission
        // pustakawan di bawah, karena scope Setting = Admin (dok Logic
        // Module §1). Hanya super_admin yang otomatis dapat lewat
        // syncPermissions(Permission::all()).
        Permission::firstOrCreate([
            'name' => 'ViewAny:PengaturanSistem',
            'guard_name' => 'web',
        ]);

        // PERBAIKAN - permission Eksemplar TERLEWAT saat EksemplarPolicy
        // dibuat sebelumnya. Eksemplar bukan Filament Resource sendiri
        // (hanya diakses via RelationManager di BukuResource/RakResource),
        // jadi shield:generate tidak pernah membuat permission ini secara
        // otomatis - harus manual seperti LevelBadgeLog/FirmwareRelease.
        foreach (
            [
                'ViewAny:Eksemplar',
                'View:Eksemplar',
                'Create:Eksemplar',
                'Update:Eksemplar',
                'Delete:Eksemplar',
                'DeleteAny:Eksemplar',
                'Restore:Eksemplar',
                'RestoreAny:Eksemplar',
                'ForceDelete:Eksemplar',
                'ForceDeleteAny:Eksemplar',
                'Replicate:Eksemplar',
                'Reorder:Eksemplar',
            ] as $permissionName
        ) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        $superAdmin->syncPermissions(Permission::all());

        $pustakawan = Role::firstOrCreate([
            'name' => 'pustakawan',
            'guard_name' => 'web',
        ]);
        $pustakawan->syncPermissions(
            Permission::whereIn('name', [
                'ViewAny:LevelBadgeLog',
                'View:LevelBadgeLog',

                'ViewAny:Buku',
                'View:Buku',
                'Create:Buku',
                'Update:Buku',
                'Delete:Buku',
                'DeleteAny:Buku',
                'Restore:Buku',
                'RestoreAny:Buku',
                'ForceDelete:Buku',
                'ForceDeleteAny:Buku',
                'Replicate:Buku',
                'Reorder:Buku',

                // BARU iterasi ini - Pustakawan diberi CRUD penuh untuk
                // Eksemplar, sepadan dengan akses Buku (Pustakawan adalah
                // pengelola operasional harian koleksi fisik per dok
                // Logic Module §1). Termasuk akses tombol Import/Export
                // Eksemplar di EksemplarsRelationManager.
                'ViewAny:Eksemplar',
                'View:Eksemplar',
                'Create:Eksemplar',
                'Update:Eksemplar',
                'Delete:Eksemplar',
                'DeleteAny:Eksemplar',
                'Restore:Eksemplar',
                'RestoreAny:Eksemplar',
                'ForceDelete:Eksemplar',
                'ForceDeleteAny:Eksemplar',
                'Replicate:Eksemplar',
                'Reorder:Eksemplar',

                'ViewAny:Kategori',
                'View:Kategori',
                'Create:Kategori',
                'Update:Kategori',
                'Delete:Kategori',
                'DeleteAny:Kategori',
                'Restore:Kategori',
                'RestoreAny:Kategori',
                'ForceDelete:Kategori',
                'ForceDeleteAny:Kategori',
                'Replicate:Kategori',
                'Reorder:Kategori',

                'ViewAny:Rak',
                'View:Rak',
                'Create:Rak',
                'Update:Rak',
                'Delete:Rak',
                'DeleteAny:Rak',
                'Restore:Rak',
                'RestoreAny:Rak',
                'ForceDelete:Rak',
                'ForceDeleteAny:Rak',
                'Replicate:Rak',
                'Reorder:Rak',

                'ViewAny:Peminjaman',
                'View:Peminjaman',
                'Create:Peminjaman',

                'ViewAny:Pengembalian',
                'View:Pengembalian',
                'Update:Pengembalian',

                'ViewAny:Denda',
                'View:Denda',
                'Update:Denda',
                'ViewAny:Kunjungan',
                'View:Kunjungan',
                'ViewAny:Transaksi',
                'View:Transaksi',

                'ViewAny:RiwayatKelasSiswa',
                'View:RiwayatKelasSiswa',

                // BARU iterasi ini - LevelBadge/Reward/Punishment adalah
                // master data (threshold badge & aturan reward/punishment),
                // diberi CRUD penuh sama seperti Buku/Kategori/Rak -
                // dikonfirmasi Pustakawan dapat akses ke 5 resource baru
                // (poin & reward) ini.
                'ViewAny:LevelBadge',
                'View:LevelBadge',
                'Create:LevelBadge',
                'Update:LevelBadge',
                'Delete:LevelBadge',
                'DeleteAny:LevelBadge',
                'Restore:LevelBadge',
                'RestoreAny:LevelBadge',
                'ForceDelete:LevelBadge',
                'ForceDeleteAny:LevelBadge',
                'Replicate:LevelBadge',
                'Reorder:LevelBadge',

                'ViewAny:Reward',
                'View:Reward',
                'Create:Reward',
                'Update:Reward',
                'Delete:Reward',
                'DeleteAny:Reward',
                'Restore:Reward',
                'RestoreAny:Reward',
                'ForceDelete:Reward',
                'ForceDeleteAny:Reward',
                'Replicate:Reward',
                'Reorder:Reward',

                'ViewAny:Punishment',
                'View:Punishment',
                'Create:Punishment',
                'Update:Punishment',
                'Delete:Punishment',
                'DeleteAny:Punishment',
                'Restore:Punishment',
                'RestoreAny:Punishment',
                'ForceDelete:Punishment',
                'ForceDeleteAny:Punishment',
                'Replicate:Punishment',
                'Reorder:Punishment',

                // RewardLog/PunishmentLog - read-only (dihasilkan otomatis
                // oleh PointService), Pustakawan hanya diberi akses lihat,
                // sama pola dengan Denda/Kunjungan di atas.
                'ViewAny:RewardLog',
                'View:RewardLog',
                'ViewAny:PunishmentLog',
                'View:PunishmentLog',

                'ViewAny:LaporanBulanan',

                'ViewAny:Peminjaman',
                'View:Peminjaman',
                'Create:Peminjaman',
                'Update:Peminjaman',
                
                // Catatan: 'ViewAny:PengaturanSistem' SENGAJA tidak
                // ditambahkan di sini - lihat komentar di atas.
            ])->get()
        );

        Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'pegawai', 'guard_name' => 'web']);

        User::where('role', RoleUser::Admin)->each(
            fn ($user) => $user->syncRoles(['super_admin'])
        );
    }
}

```
---

## bootstrap/app.php
```php
<?php

use App\Http\Middleware\AuthenticateDeviceApiKey;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'device.api.key' => AuthenticateDeviceApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

```
---

## bootstrap/cache/packages.php
```php
<?php return array (
  'anourvalar/eloquent-serialize' => 
  array (
    'aliases' => 
    array (
      'EloquentSerialize' => 'AnourValar\\EloquentSerialize\\Facades\\EloquentSerializeFacade',
    ),
  ),
  'barryvdh/laravel-dompdf' => 
  array (
    'aliases' => 
    array (
      'PDF' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
      'Pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
    ),
    'providers' => 
    array (
      0 => 'Barryvdh\\DomPDF\\ServiceProvider',
    ),
  ),
  'bezhansalleh/filament-plugin-essentials' => 
  array (
    'providers' => 
    array (
      0 => 'BezhanSalleh\\PluginEssentials\\PluginEssentialsServiceProvider',
    ),
  ),
  'bezhansalleh/filament-shield' => 
  array (
    'aliases' => 
    array (
      'FilamentShield' => 'BezhanSalleh\\FilamentShield\\Facades\\FilamentShield',
    ),
    'providers' => 
    array (
      0 => 'BezhanSalleh\\FilamentShield\\FilamentShieldServiceProvider',
    ),
  ),
  'blade-ui-kit/blade-heroicons' => 
  array (
    'providers' => 
    array (
      0 => 'BladeUI\\Heroicons\\BladeHeroiconsServiceProvider',
    ),
  ),
  'blade-ui-kit/blade-icons' => 
  array (
    'providers' => 
    array (
      0 => 'BladeUI\\Icons\\BladeIconsServiceProvider',
    ),
  ),
  'filament/actions' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Actions\\ActionsServiceProvider',
    ),
  ),
  'filament/filament' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\FilamentServiceProvider',
    ),
  ),
  'filament/forms' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Forms\\FormsServiceProvider',
    ),
  ),
  'filament/infolists' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Infolists\\InfolistsServiceProvider',
    ),
  ),
  'filament/notifications' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Notifications\\NotificationsServiceProvider',
    ),
  ),
  'filament/query-builder' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\QueryBuilder\\QueryBuilderServiceProvider',
    ),
  ),
  'filament/schemas' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Schemas\\SchemasServiceProvider',
    ),
  ),
  'filament/support' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Support\\SupportServiceProvider',
    ),
  ),
  'filament/tables' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Tables\\TablesServiceProvider',
    ),
  ),
  'filament/widgets' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Widgets\\WidgetsServiceProvider',
    ),
  ),
  'kirschbaum-development/eloquent-power-joins' => 
  array (
    'providers' => 
    array (
      0 => 'Kirschbaum\\PowerJoins\\PowerJoinsServiceProvider',
    ),
  ),
  'laravel-shift/blueprint' => 
  array (
    'providers' => 
    array (
      0 => 'Blueprint\\BlueprintServiceProvider',
    ),
  ),
  'laravel/octane' => 
  array (
    'aliases' => 
    array (
      'Octane' => 'Laravel\\Octane\\Facades\\Octane',
    ),
    'providers' => 
    array (
      0 => 'Laravel\\Octane\\OctaneServiceProvider',
    ),
  ),
  'laravel/pail' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Pail\\PailServiceProvider',
    ),
  ),
  'laravel/pao' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Pao\\Laravel\\ServiceProvider',
    ),
  ),
  'laravel/tinker' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Tinker\\TinkerServiceProvider',
    ),
  ),
  'livewire/livewire' => 
  array (
    'aliases' => 
    array (
      'Livewire' => 'Livewire\\Livewire',
    ),
    'providers' => 
    array (
      0 => 'Livewire\\LivewireServiceProvider',
    ),
  ),
  'nesbot/carbon' => 
  array (
    'providers' => 
    array (
      0 => 'Carbon\\Laravel\\ServiceProvider',
    ),
  ),
  'nunomaduro/collision' => 
  array (
    'providers' => 
    array (
      0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    ),
  ),
  'nunomaduro/termwind' => 
  array (
    'providers' => 
    array (
      0 => 'Termwind\\Laravel\\TermwindServiceProvider',
    ),
  ),
  'ryangjchandler/blade-capture-directive' => 
  array (
    'aliases' => 
    array (
      'BladeCaptureDirective' => 'RyanChandler\\BladeCaptureDirective\\Facades\\BladeCaptureDirective',
    ),
    'providers' => 
    array (
      0 => 'RyanChandler\\BladeCaptureDirective\\BladeCaptureDirectiveServiceProvider',
    ),
  ),
  'spatie/laravel-permission' => 
  array (
    'providers' => 
    array (
      0 => 'Spatie\\Permission\\PermissionServiceProvider',
    ),
  ),
);
```
---

## bootstrap/cache/services.php
```php
<?php return array (
  'providers' => 
  array (
    0 => 'Illuminate\\Auth\\AuthServiceProvider',
    1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    2 => 'Illuminate\\Bus\\BusServiceProvider',
    3 => 'Illuminate\\Cache\\CacheServiceProvider',
    4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
    6 => 'Illuminate\\Cookie\\CookieServiceProvider',
    7 => 'Illuminate\\Database\\DatabaseServiceProvider',
    8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
    9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
    10 => 'Illuminate\\Image\\ImageServiceProvider',
    11 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
    12 => 'Illuminate\\Hashing\\HashServiceProvider',
    13 => 'Illuminate\\Mail\\MailServiceProvider',
    14 => 'Illuminate\\Notifications\\NotificationServiceProvider',
    15 => 'Illuminate\\Pagination\\PaginationServiceProvider',
    16 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
    17 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
    18 => 'Illuminate\\Queue\\QueueServiceProvider',
    19 => 'Illuminate\\Redis\\RedisServiceProvider',
    20 => 'Illuminate\\Session\\SessionServiceProvider',
    21 => 'Illuminate\\Translation\\TranslationServiceProvider',
    22 => 'Illuminate\\Validation\\ValidationServiceProvider',
    23 => 'Illuminate\\View\\ViewServiceProvider',
    24 => 'Barryvdh\\DomPDF\\ServiceProvider',
    25 => 'BezhanSalleh\\PluginEssentials\\PluginEssentialsServiceProvider',
    26 => 'BezhanSalleh\\FilamentShield\\FilamentShieldServiceProvider',
    27 => 'BladeUI\\Heroicons\\BladeHeroiconsServiceProvider',
    28 => 'BladeUI\\Icons\\BladeIconsServiceProvider',
    29 => 'Filament\\Actions\\ActionsServiceProvider',
    30 => 'Filament\\FilamentServiceProvider',
    31 => 'Filament\\Forms\\FormsServiceProvider',
    32 => 'Filament\\Infolists\\InfolistsServiceProvider',
    33 => 'Filament\\Notifications\\NotificationsServiceProvider',
    34 => 'Filament\\QueryBuilder\\QueryBuilderServiceProvider',
    35 => 'Filament\\Schemas\\SchemasServiceProvider',
    36 => 'Filament\\Support\\SupportServiceProvider',
    37 => 'Filament\\Tables\\TablesServiceProvider',
    38 => 'Filament\\Widgets\\WidgetsServiceProvider',
    39 => 'Kirschbaum\\PowerJoins\\PowerJoinsServiceProvider',
    40 => 'Blueprint\\BlueprintServiceProvider',
    41 => 'Laravel\\Octane\\OctaneServiceProvider',
    42 => 'Laravel\\Pail\\PailServiceProvider',
    43 => 'Laravel\\Pao\\Laravel\\ServiceProvider',
    44 => 'Laravel\\Tinker\\TinkerServiceProvider',
    45 => 'Livewire\\LivewireServiceProvider',
    46 => 'Carbon\\Laravel\\ServiceProvider',
    47 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    48 => 'Termwind\\Laravel\\TermwindServiceProvider',
    49 => 'RyanChandler\\BladeCaptureDirective\\BladeCaptureDirectiveServiceProvider',
    50 => 'Spatie\\Permission\\PermissionServiceProvider',
    51 => 'App\\Providers\\AppServiceProvider',
    52 => 'App\\Providers\\Filament\\DashboardPanelProvider',
  ),
  'eager' => 
  array (
    0 => 'Illuminate\\Auth\\AuthServiceProvider',
    1 => 'Illuminate\\Cookie\\CookieServiceProvider',
    2 => 'Illuminate\\Database\\DatabaseServiceProvider',
    3 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
    4 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
    5 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
    6 => 'Illuminate\\Notifications\\NotificationServiceProvider',
    7 => 'Illuminate\\Pagination\\PaginationServiceProvider',
    8 => 'Illuminate\\Session\\SessionServiceProvider',
    9 => 'Illuminate\\View\\ViewServiceProvider',
    10 => 'Barryvdh\\DomPDF\\ServiceProvider',
    11 => 'BezhanSalleh\\PluginEssentials\\PluginEssentialsServiceProvider',
    12 => 'BezhanSalleh\\FilamentShield\\FilamentShieldServiceProvider',
    13 => 'BladeUI\\Heroicons\\BladeHeroiconsServiceProvider',
    14 => 'BladeUI\\Icons\\BladeIconsServiceProvider',
    15 => 'Filament\\Actions\\ActionsServiceProvider',
    16 => 'Filament\\FilamentServiceProvider',
    17 => 'Filament\\Forms\\FormsServiceProvider',
    18 => 'Filament\\Infolists\\InfolistsServiceProvider',
    19 => 'Filament\\Notifications\\NotificationsServiceProvider',
    20 => 'Filament\\QueryBuilder\\QueryBuilderServiceProvider',
    21 => 'Filament\\Schemas\\SchemasServiceProvider',
    22 => 'Filament\\Support\\SupportServiceProvider',
    23 => 'Filament\\Tables\\TablesServiceProvider',
    24 => 'Filament\\Widgets\\WidgetsServiceProvider',
    25 => 'Kirschbaum\\PowerJoins\\PowerJoinsServiceProvider',
    26 => 'Laravel\\Octane\\OctaneServiceProvider',
    27 => 'Laravel\\Pail\\PailServiceProvider',
    28 => 'Laravel\\Pao\\Laravel\\ServiceProvider',
    29 => 'Livewire\\LivewireServiceProvider',
    30 => 'Carbon\\Laravel\\ServiceProvider',
    31 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    32 => 'Termwind\\Laravel\\TermwindServiceProvider',
    33 => 'RyanChandler\\BladeCaptureDirective\\BladeCaptureDirectiveServiceProvider',
    34 => 'Spatie\\Permission\\PermissionServiceProvider',
    35 => 'App\\Providers\\AppServiceProvider',
    36 => 'App\\Providers\\Filament\\DashboardPanelProvider',
  ),
  'deferred' => 
  array (
    'Illuminate\\Broadcasting\\BroadcastManager' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Contracts\\Broadcasting\\Factory' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Contracts\\Broadcasting\\Broadcaster' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Bus\\Dispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Contracts\\Bus\\Dispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Contracts\\Bus\\QueueingDispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Bus\\BatchRepository' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Bus\\DatabaseBatchRepository' => 'Illuminate\\Bus\\BusServiceProvider',
    'cache' => 'Illuminate\\Cache\\CacheServiceProvider',
    'cache.store' => 'Illuminate\\Cache\\CacheServiceProvider',
    'cache.psr6' => 'Illuminate\\Cache\\CacheServiceProvider',
    'memcached.connector' => 'Illuminate\\Cache\\CacheServiceProvider',
    'Illuminate\\Cache\\RateLimiter' => 'Illuminate\\Cache\\CacheServiceProvider',
    'Illuminate\\Foundation\\Console\\AboutCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\ClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\ForgetCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ClearCompiledCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Auth\\Console\\ClearResetsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigShowCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\DbCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\MonitorCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\PruneCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\ShowCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\TableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\WipeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\DownCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnvironmentCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnvironmentDecryptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnvironmentEncryptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Concurrency\\Console\\InvokeSerializedClosureCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\KeyGenerateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\OptimizeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\OptimizeClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\PackageDiscoverCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\PruneStaleTagsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ListFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\FlushFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ForgetFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ListenCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\MonitorCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\PauseCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\PruneBatchesCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\PruneFailedJobsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RestartCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ResumeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RetryCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RetryBatchCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\WorkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ReloadCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\DumpCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Seeds\\SeedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleFinishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleRunCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleClearCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleTestCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleWorkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleInterruptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\SchedulePauseCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleResumeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\ShowModelCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\StorageLinkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\StorageUnlinkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\UpCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ViewCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ViewClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ApiInstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\CacheTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\CastMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ChannelListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ChannelMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ClassMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ComponentMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConsoleMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Routing\\Console\\ControllerMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\DevCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\DevListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\DocsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnumMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventGenerateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ExceptionMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Factories\\FactoryMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\InterfaceMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\JobMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\JobMiddlewareMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\LangPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ListenerMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\MailMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Routing\\Console\\MiddlewareMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ModelMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\NotificationMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Notifications\\Console\\NotificationTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ObserverMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\PolicyMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ProviderMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\FailedTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\TableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\BatchesTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RequestMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ResourceMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RuleMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ScopeMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Seeds\\SeederMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Session\\Console\\SessionTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ServeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\StubPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\TestMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\TraitMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\VendorPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ViewMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migrator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migration.repository' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migration.creator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Migrations\\Migrator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\MigrateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\FreshCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\InstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\RefreshCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\ResetCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\RollbackCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\StatusCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\MigrateMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'composer' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Concurrency\\ConcurrencyManager' => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
    'image' => 'Illuminate\\Image\\ImageServiceProvider',
    'hash' => 'Illuminate\\Hashing\\HashServiceProvider',
    'hash.driver' => 'Illuminate\\Hashing\\HashServiceProvider',
    'mail.manager' => 'Illuminate\\Mail\\MailServiceProvider',
    'mailer' => 'Illuminate\\Mail\\MailServiceProvider',
    'Illuminate\\Mail\\Markdown' => 'Illuminate\\Mail\\MailServiceProvider',
    'auth.password' => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
    'auth.password.broker' => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
    'Illuminate\\Contracts\\Pipeline\\Hub' => 'Illuminate\\Pipeline\\PipelineServiceProvider',
    'pipeline' => 'Illuminate\\Pipeline\\PipelineServiceProvider',
    'queue' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.connection' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.failer' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.listener' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.routes' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.worker' => 'Illuminate\\Queue\\QueueServiceProvider',
    'redis' => 'Illuminate\\Redis\\RedisServiceProvider',
    'redis.connection' => 'Illuminate\\Redis\\RedisServiceProvider',
    'translator' => 'Illuminate\\Translation\\TranslationServiceProvider',
    'translation.loader' => 'Illuminate\\Translation\\TranslationServiceProvider',
    'validator' => 'Illuminate\\Validation\\ValidationServiceProvider',
    'validation.presence' => 'Illuminate\\Validation\\ValidationServiceProvider',
    'Illuminate\\Contracts\\Validation\\UncompromisedVerifier' => 'Illuminate\\Validation\\ValidationServiceProvider',
    'command.blueprint.build' => 'Blueprint\\BlueprintServiceProvider',
    'command.blueprint.erase' => 'Blueprint\\BlueprintServiceProvider',
    'command.blueprint.trace' => 'Blueprint\\BlueprintServiceProvider',
    'command.blueprint.new' => 'Blueprint\\BlueprintServiceProvider',
    'command.blueprint.init' => 'Blueprint\\BlueprintServiceProvider',
    'Blueprint\\Blueprint' => 'Blueprint\\BlueprintServiceProvider',
    'command.tinker' => 'Laravel\\Tinker\\TinkerServiceProvider',
  ),
  'when' => 
  array (
    'Illuminate\\Broadcasting\\BroadcastServiceProvider' => 
    array (
    ),
    'Illuminate\\Bus\\BusServiceProvider' => 
    array (
    ),
    'Illuminate\\Cache\\CacheServiceProvider' => 
    array (
    ),
    'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider' => 
    array (
    ),
    'Illuminate\\Concurrency\\ConcurrencyServiceProvider' => 
    array (
    ),
    'Illuminate\\Image\\ImageServiceProvider' => 
    array (
    ),
    'Illuminate\\Hashing\\HashServiceProvider' => 
    array (
    ),
    'Illuminate\\Mail\\MailServiceProvider' => 
    array (
    ),
    'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider' => 
    array (
    ),
    'Illuminate\\Pipeline\\PipelineServiceProvider' => 
    array (
    ),
    'Illuminate\\Queue\\QueueServiceProvider' => 
    array (
    ),
    'Illuminate\\Redis\\RedisServiceProvider' => 
    array (
    ),
    'Illuminate\\Translation\\TranslationServiceProvider' => 
    array (
    ),
    'Illuminate\\Validation\\ValidationServiceProvider' => 
    array (
    ),
    'Blueprint\\BlueprintServiceProvider' => 
    array (
    ),
    'Laravel\\Tinker\\TinkerServiceProvider' => 
    array (
    ),
  ),
);
```
---

## bootstrap/providers.php
```php
<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\DashboardPanelProvider;

return [
    AppServiceProvider::class,
    DashboardPanelProvider::class,
];

```
---

## resources/css/app.css
```css
@import 'tailwindcss';
@import '@fontsource/lexend';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../views/filament/pages/*.blade.php';

@theme {
    --font-sans: 'Lexend', ui-sans-serif, system-ui, sans-serif,
        'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';
}

```
---

## resources/js/app.js
```js
//

```
---

## resources/js/chart-export.js
```js
import { jsPDF } from "jspdf";

// Logika export grafik terpusat - dipakai oleh chart-widget.blade.php dan
// stats-overview-widget/stat.blade.php (override) supaya tidak duplikasi
// logika toDataURL/jsPDF di banyak tempat (Aturan poin 3).

function flattenToWhiteBackground(canvas) {
    // Canvas Chart.js transparan - flatten ke putih dulu supaya PNG/PDF
    // hasil download tetap terbaca di luar dashboard (mode gelap dsb).
    const flattened = document.createElement("canvas");
    flattened.width = canvas.width;
    flattened.height = canvas.height;

    const ctx = flattened.getContext("2d");
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, flattened.width, flattened.height);
    ctx.drawImage(canvas, 0, 0);

    return flattened;
}

function triggerDownload(dataUrl, filename) {
    const link = document.createElement("a");
    link.href = dataUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function downloadChartImage(canvas, filename) {
    if (!canvas) {
        return;
    }

    const flattened = flattenToWhiteBackground(canvas);
    triggerDownload(flattened.toDataURL("image/png"), `${filename}.png`);
}

function downloadChartPdf(canvas, filename) {
    if (!canvas) {
        return;
    }

    const flattened = flattenToWhiteBackground(canvas);
    const imgData = flattened.toDataURL("image/png");
    const isLandscape = canvas.width >= canvas.height;

    const pdf = new jsPDF({
        orientation: isLandscape ? "landscape" : "portrait",
        unit: "pt",
    });

    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const margin = 24;
    const maxWidth = pageWidth - margin * 2;
    const maxHeight = pageHeight - margin * 2;
    const ratio = Math.min(maxWidth / canvas.width, maxHeight / canvas.height);
    const drawWidth = canvas.width * ratio;
    const drawHeight = canvas.height * ratio;
    const x = (pageWidth - drawWidth) / 2;
    const y = (pageHeight - drawHeight) / 2;

    pdf.addImage(imgData, "PNG", x, y, drawWidth, drawHeight);
    pdf.save(`${filename}.pdf`);
}

window.ChartExport = { downloadChartImage, downloadChartPdf };

```
---

## resources/views/filament/pages/auth/request-password-reset.blade.php
```blade
<x-filament-panels::page.simple>
    <form wire:submit="kirim">
        {{ $this->form }}

        <div style="margin-top: 1.5rem;">
            <x-filament::button type="submit" class="w-full">
                Kirim OTP ke WhatsApp
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page.simple>

```
---

## resources/views/filament/pages/auth/reset-password.blade.php
```blade
<x-filament-panels::page.simple>
    <form wire:submit="prosesReset">
        {{ $this->form }}

        <div style="margin-top: 1.5rem;">
            <x-filament::button type="submit" class="w-full">
                Reset Password
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page.simple>

```
---

## resources/views/filament/pages/laporan-bulanan.blade.php
```blade
<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form wire:submit="generate">
                {{ $this->form }}

                <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem;">
                    <x-filament::button
                        type="submit"
                        icon="heroicon-o-document-arrow-down"
                        size="lg"
                        wire:loading.attr="disabled"
                        wire:target="generate"
                    >
                        <span wire:loading.remove wire:target="generate">
                            Generate &amp; Download PDF
                        </span>
                        <span wire:loading wire:target="generate">
                            Menyusun laporan...
                        </span>
                    </x-filament::button>

                    <x-filament::loading-indicator
                        wire:loading
                        wire:target="generate"
                        class="h-5 w-5 text-primary-500"
                    />
                </div>
            </form>
        </div>

</x-filament-panels::page>

```
---

## resources/views/filament/pages/pengaturan-sistem.blade.php
```blade
<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}

        <div style="margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem;">
            <x-filament::button
                type="button"
                wire:click="simpanUmum"
                icon="heroicon-o-check"
            >
                Simpan Pengaturan Umum
            </x-filament::button>

            <x-filament::button
                type="button"
                color="warning"
                icon="heroicon-o-exclamation-triangle"
                x-on:click.prevent="
                    if (confirm('Perubahan ini mempengaruhi device RFID yang sudah aktif di lapangan. Lanjutkan menyimpan?')) {
                        $wire.simpanDevice()
                    }
                "
            >
                Simpan Pengaturan Device
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

```
---

## resources/views/filament/pages/proses-kenaikan-kelas.blade.php
```blade
<x-filament-panels::page>
    <form wire:submit.prevent="proses">
        {{ $this->form }}

        <div style="margin-top: 1.5rem;">
            <x-filament::button type="submit" icon="heroicon-o-check">
                Proses Kenaikan Kelas
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

```
---

## resources/views/filament/pages/transaksi-cepat.blade.php
```blade
<x-filament-panels::page>
    <div style="max-width: 480px; margin: 0 auto;">

        @if (! $user)
            <div
                x-data
                x-init="$nextTick(() => $refs.kartu.focus())"
                style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 4rem 1.5rem;"
            >
                <div style="display: flex; align-items: center; justify-content: center; width: 96px; height: 96px; border-radius: 50%; background: var(--primary-50); margin-bottom: 1.5rem;">
                    <x-filament::icon icon="heroicon-o-credit-card" style="width: 44px; height: 44px; color: var(--primary-600);" />
                </div>

                <h2 class="text-gray-950 dark:text-white" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Tempelkan kartu RFID</h2>
                <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.875rem; margin-bottom: 1.5rem;">Scan kartu siswa atau pegawai untuk memulai transaksi.</p>

                <input
                    x-ref="kartu"
                    type="text"
                    wire:model="kartuInput"
                    wire:keydown.enter="scanKartu"
                    autofocus
                    class="fi-input"
                    style="width: 100%; max-width: 280px; border-radius: 9999px; text-align: center; padding: 0.75rem 1.5rem;"
                    placeholder="Tempelkan/scan kartu..."
                />
            </div>
        @else
            <div
                x-data="{ show: false }"
                x-init="requestAnimationFrame(() => show = true)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                style="display: flex; flex-direction: column; align-items: center; text-align: center;"
            >
                <div style="display: flex; align-items: center; justify-content: center; width: 72px; height: 72px; border-radius: 50%; font-weight: 600; font-size: 22px; color: #fff; background: {{ $user->status_suspend ? 'var(--danger-500)' : 'var(--primary-500)' }}; margin-bottom: 0.75rem;">
                    {{ collect(explode(' ', $user->nama))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                </div>

                <h2 class="text-gray-950 dark:text-white" style="font-size: 1.125rem; font-weight: 600; line-height: 1.3;">{{ $user->nama }}</h2>

                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.5rem; margin-bottom: 1.5rem;">
                    <x-filament::badge :color="$user->status_suspend ? 'danger' : 'success'">
                        {{ $user->status_suspend ? 'Suspend' : 'Aktif' }}
                    </x-filament::badge>
                    <x-filament::badge :color="$bisaMeminjam ? 'success' : 'gray'">
                        {{ $bisaMeminjam ? 'Bisa meminjam' : 'Tidak bisa meminjam baru' }}
                    </x-filament::badge>
                </div>

                @if ($user->status_suspend)
                    <div class="bg-warning-50 dark:bg-warning-500/10 text-warning-600 dark:text-warning-400" style="display: flex; align-items: flex-start; gap: 0.5rem; border-radius: 12px; padding: 0.75rem; font-size: 0.875rem; margin-bottom: 1.5rem; text-align: left; width: 100%;">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;" />
                        <span>User masih bisa mengembalikan buku, tapi tidak bisa meminjam baru sampai Denda lunas.</span>
                    </div>
                @endif

                <div x-data x-init="$refs.kode.focus()" style="width: 100%; margin-bottom: 1rem;">
                    <input
                        x-ref="kode"
                        type="text"
                        wire:model="kodeInput"
                        wire:keydown.enter="scanKode"
                        autofocus
                        class="fi-input"
                        style="width: 100%; border-radius: 9999px; text-align: center; padding: 0.75rem 1.5rem; font-size: 1rem;"
                        placeholder="Scan barcode eksemplar atau ISBN buku..."
                    />
                </div>

                <div style="margin-bottom: 2rem;">
                    <x-filament::button
                        wire:click="selesai"
                        color="gray"
                        icon="heroicon-o-arrow-path"
                        size="sm"
                    >
                        Ganti user
                    </x-filament::button>
                </div>

                @php
                    $totalDipinjam = collect($riwayatScan)->where('aksi', 'dipinjamkan')->where('sukses', true)->count();
                    $totalDikembalikan = collect($riwayatScan)->where('aksi', 'dikembalikan')->where('sukses', true)->count();
                @endphp
                @if (count($riwayatScan) > 0)
                    <div style="display: flex; align-items: center; justify-content: center; gap: 2.5rem; margin-bottom: 2rem;">
                        <div style="text-align: center;">
                            <p class="text-primary-600 dark:text-primary-400" style="font-size: 1.5rem; font-weight: 600; margin: 0;">{{ $totalDipinjam }}</p>
                            <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0;">Dipinjamkan</p>
                        </div>
                        <div class="bg-gray-200 dark:bg-gray-700" style="height: 32px; width: 1px;"></div>
                        <div style="text-align: center;">
                            <p class="text-success-600 dark:text-success-400" style="font-size: 1.5rem; font-weight: 600; margin: 0;">{{ $totalDikembalikan }}</p>
                            <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0;">Dikembalikan</p>
                        </div>
                    </div>
                @endif

                <div style="width: 100%; text-align: left; display: flex; flex-direction: column; gap: 0.5rem;">
                    @forelse ($riwayatScan as $item)
                        <div
                            x-data="{ show: false }"
                            x-init="requestAnimationFrame(() => show = true)"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            class="bg-gray-50 dark:bg-white/5"
                            style="display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px;"
                        >
                            <div style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0; background: {{ $item['sukses'] ? 'var(--success-100)' : 'var(--danger-100)' }}; color: {{ $item['sukses'] ? 'var(--success-700)' : 'var(--danger-700)' }};">
                                @if (! $item['sukses'])
                                    <x-filament::icon icon="heroicon-o-x-mark" style="width: 16px; height: 16px;" />
                                @elseif ($item['aksi'] === 'dipinjamkan')
                                    <x-filament::icon icon="heroicon-o-arrow-up-circle" style="width: 16px; height: 16px;" />
                                @else
                                    <x-filament::icon icon="heroicon-o-arrow-down-circle" style="width: 16px; height: 16px;" />
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <p class="text-gray-950 dark:text-white" style="font-weight: 500; font-size: 0.875rem; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['judul'] }}</p>
                                <p class="text-gray-500 dark:text-gray-400" style="font-size: 0.75rem; margin: 2px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['pesan'] }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-400 dark:text-gray-500" style="text-align: center; padding: 2rem 0;">
                            <x-filament::icon icon="heroicon-o-book-open" style="width: 32px; height: 32px; margin: 0 auto 0.5rem;" />
                            <p style="font-size: 0.875rem; margin: 0;">Belum ada buku yang di-scan.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>

```
---

## resources/views/filament/partials/chart-export-script.blade.php
```blade
@once
<script>
window.ChartExport = window.ChartExport || (function () {
    function flattenToWhite(canvas) {
        // Canvas Chart.js transparan - flatten ke putih dulu supaya hasil
        // download tetap terbaca di luar dashboard (mode gelap dsb).
        const flat = document.createElement('canvas');
        flat.width = canvas.width;
        flat.height = canvas.height;

        const ctx = flat.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, flat.width, flat.height);
        ctx.drawImage(canvas, 0, 0);

        return flat;
    }

    function downloadImage(canvas, filename) {
        if (!canvas) {
            return;
        }

        const flat = flattenToWhite(canvas);
        const link = document.createElement('a');
        link.href = flat.toDataURL('image/png');
        link.download = filename + '.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function downloadPdf(canvas, filename, meta) {
    if (!canvas) {
        return;
    }

    meta = meta || {};

    const flat = flattenToWhite(canvas);
    const dataUrl = flat.toDataURL('image/png');
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    fetch('{{ route('chart-export.pdf') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken ?? '',
            Accept: 'application/pdf',
        },
        body: JSON.stringify({
            image: dataUrl,
            filename: filename,
            widget: meta.widget,
            type: meta.type,
            stat_label: meta.statLabel,
        }),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Gagal membuat PDF di server');
            }

            return response.blob();
        })
        .then((blob) => {
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename + '.pdf';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        })
        .catch(() => {
            alert('Gagal membuat PDF. Silakan coba lagi.');
        });
}

    return { downloadImage, downloadPdf };
})();
</script>
@endonce

```
---

## resources/views/pdf/chart-export.blade.php
```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { margin: 0; padding: 24px; font-family: sans-serif; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { font-size: 10px; color: #666; margin-bottom: 16px; }
        .section-title { font-size: 12px; font-weight: bold; margin: 14px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f3f3f3; }
        img { max-width: 100%; height: auto; margin-top: 8px; }
    </style>
</head>
<body>
    <h1>{{ $heading }}</h1>
    <div class="meta">Diekspor pada {{ now()->translatedFormat('d F Y H:i') }} WIB</div>

    @if (count($summary))
        <div class="section-title">Ringkasan</div>
        <table>
            <thead>
                <tr><th>Seri Data</th><th>Total</th><th>Rata-rata</th></tr>
            </thead>
            <tbody>
                @foreach ($summary as $s)
                    <tr>
                        <td>{{ $s['label'] }}</td>
                        <td>{{ number_format($s['total'], 0, ',', '.') }}</td>
                        <td>{{ number_format($s['rata_rata'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (count($rows))
        <div class="section-title">Data</div>
        <table>
            <thead>
                <tr>
                    @foreach (array_keys($rows[0]) as $col)
                        <th>{{ $col === 'label' ? 'Label' : $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $val)
                            <td>{{ $val }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Grafik</div>
    <img src="{{ $image }}" alt="Grafik">
</body>
</html>

```
---

## resources/views/pdf/label-barcode.blade.php
```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Label Barcode Eksemplar</title>
    <style>
        @font-face {
            font-family: 'Lexend';
            src: url('{{ public_path('fonts/pdf/lexend-regular.woff2') }}') format('woff2');
            font-weight: 400;
        }

        @font-face {
            font-family: 'Lexend';
            src: url('{{ public_path('fonts/pdf/lexend-bold.woff2') }}') format('woff2');
            font-weight: 700;
        }

        * {
            font-family: 'Lexend', sans-serif;
            box-sizing: border-box;
        }

        body {
            margin: 8mm 6mm;
        }

        table.grid {
            width: 100%;
            border-collapse: collapse;
        }

        table.grid td {
            width: 33.33%;
            padding: 2mm;
            vertical-align: top;
        }

        .label-box {
            border: 1px dashed #999;
            padding: 3mm;
            text-align: center;
            height: 32mm;
            overflow: hidden;
        }

        .label-box .judul {
            font-size: 9px;
            font-weight: 700;
            margin-bottom: 2mm;
            height: 10mm;
            overflow: hidden;
        }

        .label-box img {
            width: 100%;
            max-height: 12mm;
        }

        .label-box .kode-text {
            font-size: 8px;
            margin-top: 1mm;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <table class="grid">
        @foreach (array_chunk($labels, 3) as $baris)
            <tr>
                @foreach ($baris as $label)
                    <td>
                        <div class="label-box">
                            <div class="judul">{{ $label['judul'] }}</div>
                            <img src="{{ $label['gambar'] }}" alt="barcode">
                            <div class="kode-text">{{ $label['barcode'] }}</div>
                        </div>
                    </td>
                @endforeach
                @for ($i = count($baris); $i < 3; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>

```
---

## resources/views/pdf/laporan-bulanan.blade.php
```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Bulanan Perpustakaan</title>
    <style>
        @font-face {
            font-family: 'Lexend';
            src: url('{{ public_path('fonts/pdf/lexend-regular.woff2') }}') format('woff2');
            font-weight: 400;
        }

        @font-face {
            font-family: 'Lexend';
            src: url('{{ public_path('fonts/pdf/lexend-bold.woff2') }}') format('woff2');
            font-weight: 700;
        }

        * {
            font-family: 'Lexend', sans-serif;
            box-sizing: border-box;
        }

        body {
            font-size: 11px;
            color: #111;
            margin: 10px 20px;
        }

        h1 {
            font-size: 16px;
            margin-bottom: 0;
            text-align: center;
            text-transform: uppercase;
        }

        h2 {
            font-size: 13px;
            margin-top: 24px;
            margin-bottom: 6px;
            padding: 6px 8px;
            background-color: #D0F0C0;
            font-weight: 700;
        }

        .subheading {
            color: #555;
            text-align: center;
            margin-top: 2px;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th, td {
            border: 1px solid #D0F0C0;
            padding: 4px 6px;
            text-align: left;
        }

        th {
            background: #D0F0C0;
            font-weight: 700;
        }

        .ringkasan-box {
            margin-bottom: 8px;
            background-color: #f9f9f9;
            border: 1px solid #D0F0C0;
            padding: 6px 8px;
        }

        .ringkasan-box span {
            display: inline-block;
            margin-right: 16px;
        }

        .section {
            page-break-after: always;
        }

        .section:last-child {
            page-break-after: auto;
        }

        .badge-list, .reward-list, .punishment-list {
            margin: 0;
            padding-left: 14px;
        }

        .badge-list li, .reward-list li, .punishment-list li {
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
    <h1>Laporan Bulanan Perpustakaan</h1>
    <p class="subheading">Periode: {{ $periode_label }}</p>

    {{-- PEMINJAMAN --}}
    <div class="section">
        <h2>Peminjaman</h2>
        <div class="ringkasan-box">
            <span><strong>Total:</strong> {{ $peminjaman['total'] }}</span>
            @foreach ($peminjaman['per_status'] as $status => $jumlah)
                <span><strong>{{ ucfirst($status) }}:</strong> {{ $jumlah }}</span>
            @endforeach
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal Pinjam</th>
                    <th>Peminjam</th>
                    <th>Buku</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($peminjaman['detail'] as $p)
                    <tr>
                        <td>{{ $p->tanggal_pinjam->format('d-m-Y') }}</td>
                        <td>{{ $p->user->nama }}</td>
                        <td>{{ $p->eksemplar->buku->judul }}</td>
                        <td>{{ $p->tanggal_jatuh_tempo->format('d-m-Y') }}</td>
                        <td>{{ ucfirst($p->status->value) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PENGEMBALIAN --}}
    <div class="section">
        <h2>Pengembalian</h2>
        <div class="ringkasan-box">
            <span><strong>Total:</strong> {{ $pengembalian['total'] }}</span>
            @foreach ($pengembalian['per_kondisi'] as $kondisi => $jumlah)
                <span><strong>{{ ucfirst($kondisi) }}:</strong> {{ $jumlah }}</span>
            @endforeach
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal Kembali</th>
                    <th>Peminjam</th>
                    <th>Buku</th>
                    <th>Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pengembalian['detail'] as $p)
                    <tr>
                        <td>{{ $p->tanggal_kembali->format('d-m-Y') }}</td>
                        <td>{{ $p->peminjaman->user->nama }}</td>
                        <td>{{ $p->peminjaman->eksemplar->buku->judul }}</td>
                        <td>{{ ucfirst($p->kondisi->value) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- DENDA --}}
    <div class="section">
        <h2>Denda</h2>
        <div class="ringkasan-box">
            <span><strong>Total Transaksi:</strong> {{ $denda['total'] }}</span>
            <span><strong>Total Nominal:</strong> Rp {{ number_format($denda['total_nominal'], 0, ',', '.') }}</span>
            <span><strong>Sudah Lunas:</strong> Rp {{ number_format($denda['total_nominal_lunas'], 0, ',', '.') }}</span>
            <span><strong>Belum Lunas:</strong> Rp {{ number_format($denda['total_nominal_belum_lunas'], 0, ',', '.') }}</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>User</th>
                    <th>Tipe</th>
                    <th>Nominal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($denda['detail'] as $d)
                    <tr>
                        <td>{{ $d->created_at->format('d-m-Y') }}</td>
                        <td>{{ $d->user->nama }}</td>
                        <td>{{ ucfirst($d->tipe->value) }}</td>
                        <td>Rp {{ number_format($d->nominal, 0, ',', '.') }}</td>
                        <td>{{ $d->status_lunas ? 'Lunas' : 'Belum Lunas' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- KUNJUNGAN --}}
    <div class="section">
        <h2>Kunjungan</h2>
        <div class="ringkasan-box">
            <span><strong>Total Kunjungan:</strong> {{ $kunjungan['total'] }}</span>
            <span><strong>Pengunjung Unik:</strong> {{ $kunjungan['user_unik'] }}</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Pengunjung</th>
                    <th>Sumber</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kunjungan['detail'] as $k)
                    <tr>
                        <td>{{ $k->tanggal->format('d-m-Y') }}</td>
                        <td>{{ $k->jam_tap }}</td>
                        <td>{{ $k->user->nama }}</td>
                        <td>{{ ucfirst($k->source->value) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- POINT --}}
    <div class="section">
        <h2>Point</h2>
        <div class="ringkasan-box">
            <span><strong>Total Transaksi:</strong> {{ $point['total_transaksi'] }}</span>
            <span><strong>Total Nilai:</strong> {{ $point['total_nilai'] }}</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Nilai</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($point['detail'] as $p)
                    <tr>
                        <td>{{ $p->created_at->format('d-m-Y') }}</td>
                        <td>{{ $p->user->nama }}</td>
                        <td>{{ ucfirst($p->event_type->value) }}</td>
                        <td>{{ $p->nilai }}</td>
                        <td>{{ $p->keterangan }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- BADGE, REWARD, PUNISHMENT --}}
    <div class="section">
        <h2>User Pemilik Badge, Reward &amp; Punishment</h2>
        <div class="ringkasan-box">
            <span><strong>Total Badge Baru:</strong> {{ $poin_reward_punishment['total_badge'] }}</span>
            <span><strong>Total Reward Didapat:</strong> {{ $poin_reward_punishment['total_reward'] }}</span>
            <span><strong>Total Punishment Diterapkan:</strong> {{ $poin_reward_punishment['total_punishment'] }}</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 18%;">User</th>
                    <th style="width: 27%;">Riwayat Badge</th>
                    <th style="width: 27%;">Riwayat Reward</th>
                    <th style="width: 28%;">Riwayat Punishment</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($poin_reward_punishment['per_user'] as $userId => $data)
                    <tr>
                        <td>{{ $data['nama'] }}</td>
                        <td>
                            @if ($data['badge']->isEmpty())
                                -
                            @else
                                <ul class="badge-list">
                                    @foreach ($data['badge'] as $b)
                                        <li>{{ $b->levelBadge->nama_badge }} ({{ $b->tanggal_didapat->format('d-m-Y') }})</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td>
                            @if ($data['reward']->isEmpty())
                                -
                            @else
                                <ul class="reward-list">
                                    @foreach ($data['reward'] as $r)
                                        <li>{{ $r->reward->nama }} ({{ $r->tanggal_didapat->format('d-m-Y') }})</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td>
                            @if ($data['punishment']->isEmpty())
                                -
                            @else
                                <ul class="punishment-list">
                                    @foreach ($data['punishment'] as $pl)
                                        <li>{{ $pl->punishment->nama }} ({{ $pl->tanggal_diterapkan->format('d-m-Y') }})</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Tidak ada data badge/reward/punishment bulan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>

```
---

## resources/views/welcome.blade.php
```blade

```
---
