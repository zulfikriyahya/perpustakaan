# SOURCE CODE - perpustakaan

## app/Console/Commands/ProsesCronHarianPerpustakaan.php
```php
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

class BukuExporter extends Exporter
{
    protected static ?string $model = Buku::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('judul'),
            ExportColumn::make('penulis'),
            ExportColumn::make('penerbit'),
            ExportColumn::make('isbn')->label('ISBN'),
            ExportColumn::make('barcode'),
            ExportColumn::make('rak.nama')->label('Rak'),
            ExportColumn::make('kategoris.nama')->label('Kategori'), // dipisah koma otomatis oleh Filament untuk relasi many
            ExportColumn::make('harga_ganti')->label('Harga Ganti'),
            ExportColumn::make('stok'),
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
            ExportColumn::make('kategoris.nama')->label('Kategori Terkait'),
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
            ExportColumn::make('kelas'),
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

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Rak;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * TODO: GAP-SPEC - resolveRecord() upsert berdasarkan 'barcode' (create
 * jika tidak ada, update jika sudah ada). Belum dikonfirmasi apakah
 * perilaku yang diinginkan justru reject baris dengan barcode duplikat.
 */
class BukuImporter extends Importer
{
    protected static ?string $model = Buku::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('judul')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('penulis')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('penerbit')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('isbn')
                ->label('ISBN')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('barcode')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('rak')
                ->label('Rak (nama)')
                ->rules(['nullable', 'string']),
            ImportColumn::make('kategori')
                ->label('Kategori (nama, pisah titik-koma jika lebih dari satu)')
                ->rules(['nullable', 'string']),
            ImportColumn::make('harga_ganti')
                ->label('Harga Ganti')
                ->numeric()
                ->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('stok')
                ->numeric()
                ->rules(['required', 'integer', 'min:0']),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?Buku
    {
        // upsert - lihat TODO: GAP-SPEC di atas class.
        return Buku::query()->firstOrNew(['barcode' => $this->data['barcode']]);
    }

    /**
     * Dipanggil setelah field kolom dasar di-assign, sebelum save() -
     * dipakai untuk resolusi 'rak'/'kategori' by nama (bukan foreign key
     * mentah), karena kolom ini bukan field langsung di tabel bukus.
     */
    protected function beforeSave(): void
    {
        if (! empty($this->data['rak'])) {
            $rak = Rak::query()->where('nama', trim($this->data['rak']))->first();
            $this->record->rak_id = $rak?->id;
        }
    }

    protected function afterSave(): void
    {
        if (! empty($this->data['kategori'])) {
            $namaKategoris = array_filter(array_map('trim', explode(';', $this->data['kategori'])));
            $kategoriIds = Kategori::query()->whereIn('nama', $namaKategoris)->pluck('id');
            $this->record->kategoris()->sync($kategoriIds);
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

## app/Filament/Imports/KategoriImporter.php
```php
<?php

namespace App\Filament\Imports;

use App\Models\Kategori;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

// TODO: GAP-SPEC - upsert berdasarkan 'nama' (case-sensitive) - sama seperti BukuImporter.
class KategoriImporter extends Importer
{
    protected static ?string $model = Kategori::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?Kategori
    {
        return Kategori::query()->firstOrNew(['nama' => $this->data['nama']]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Kategori selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
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
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

// TODO: GAP-SPEC - upsert berdasarkan 'nama' - sama seperti BukuImporter/KategoriImporter.
class RakImporter extends Importer
{
    protected static ?string $model = Rak::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('lokasi')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('kategori')
                ->label('Kategori (nama, pisah titik-koma jika lebih dari satu)')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?Rak
    {
        return Rak::query()->firstOrNew(['nama' => $this->data['nama']]);
    }

    protected function afterSave(): void
    {
        if (! empty($this->data['kategori'])) {
            $namaKategoris = array_filter(array_map('trim', explode(';', $this->data['kategori'])));
            $kategoriIds = Kategori::query()->whereIn('nama', $namaKategoris)->pluck('id');
            $this->record->kategoris()->sync($kategoriIds);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Rak selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

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
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;

/**
 * TODO: GAP-SPEC - 'role' dan 'no_kartu_rfid' SENGAJA tidak termasuk kolom
 * import (dikonfirmasi: harus manual lewat form demi keamanan). User baru
 * hasil import otomatis role='siswa' (default migration/kolom).
 *
 * Upsert berdasarkan 'nisn' jika ada, fallback 'nip' - baris tanpa
 * keduanya akan gagal (lihat rules 'required_without').
 *
 * Password digenerate random - TIDAK ada mekanisme kirim WA/email
 * notifikasi password ke user baru dalam iterasi ini (lihat status
 * verifikasi di respons utama untuk gap ini).
 */
class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('nisn')
                ->label('NISN')
                ->rules(['nullable', 'required_without:nip', 'string', 'max:255']),
            ImportColumn::make('nip')
                ->label('NIP')
                ->rules(['nullable', 'required_without:nisn', 'string', 'max:255']),
            ImportColumn::make('kelas')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('jabatan')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('no_telepon')
                ->label('No. Telepon')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?User
    {
        if (! empty($this->data['nisn'])) {
            $record = User::query()->firstOrNew(['nisn' => $this->data['nisn']]);
        } else {
            $record = User::query()->firstOrNew(['nip' => $this->data['nip']]);
        }

        if (! $record->exists) {
            $record->role = RoleUser::Siswa;
            $record->password = Str::random(12); // di-hash otomatis via cast 'hashed'
        }

        return $record;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import User selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
        }

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
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

// TODO: verifikasi signature terhadap versi package yang terpasang (filament/filament ^5.7).
// Nama namespace parent (Filament\Auth\Pages\Login vs Filament\Pages\Auth\Login) dan
// method getCredentialsFromFormData() perlu dicek ulang terhadap source filament/filament
// versi 5.7 yang ter-install - saya tidak bisa memverifikasi langsung dari sini.
class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getLoginFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getRememberFormComponent(),
        ]);
    }

    protected function getLoginFormComponent(): TextInput
    {
        return TextInput::make('login')
            ->label('NISN / NIP / No. Telepon')
            ->required()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    /**
     * Resolusi identifier login ke kolom yang sesuai (Aturan: NISN untuk
     * Siswa, NIP untuk Pegawai/Pustakawan/Admin, atau no_telepon sebagai
     * fallback universal). TODO: GAP-SPEC - jika suatu saat NISN dan NIP
     * bisa bentrok nilai (kemungkinan kecil, belum ada constraint silang),
     * urutan pengecekan di bawah (nisn -> nip -> no_telepon) menentukan
     * prioritas resolusi.
     */
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

## app/Filament/Pages/LaporanBulanan.php
```php
<?php

namespace App\Filament\Pages;

use App\Services\LaporanBulananService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
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

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('bulan')
                ->label('Bulan')
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
                ->options(
                    collect(range((int) now()->format('Y'), 2024))
                        ->mapWithKeys(fn ($y) => [$y => $y])
                )
                ->required(),
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
 * komponen Tabs/Tab diasumsikan berada di Filament\Schemas\Components
 * (mengikuti pola Schema/Select yang sudah dipakai LaporanBulanan),
 * cek ulang jika filament/filament ^5.7 punya lokasi berbeda.
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

                    Tab::make('Point')
                        ->schema([
                            TextInput::make('point_kunjungan')->label('Point Kunjungan')->numeric()->integer()->required(),
                            TextInput::make('point_peminjaman')->label('Point Peminjaman')->numeric()->integer()->required(),
                            TextInput::make('point_pengembalian')->label('Point Pengembalian')->numeric()->integer()->required(),
                            TextInput::make('point_kerusakan')->label('Point Kerusakan (negatif)')->numeric()->integer()->maxValue(0)->required(),
                            TextInput::make('point_kehilangan')->label('Point Kehilangan (negatif)')->numeric()->integer()->maxValue(0)->required(),
                        ]),

                    Tab::make('WhatsApp Template')
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
                                'wa_template_koreksi_kondisi_pengembalian' => 'Koreksi Kondisi Pengembalian',
                                'wa_template_denda_dibatalkan_perlu_refund' => 'Denda Dibatalkan (Perlu Refund)',
                            ])->map(
                                fn (string $label, string $key) => TextInput::make($key)
                                    ->label($label)
                                    ->required()
                                    ->helperText('Wajib sama persis dengan template_code di panel gateway.')
                            )->values()->all()
                        ),

                    Tab::make('Device')
                        ->schema([
                            Placeholder::make('rfid_db_ver')
                                ->label('Versi Daftar Kartu RFID (otomatis)')
                                ->content(fn () => (string) Setting::get('rfid_db_ver', 0)),
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
 * dapat diakses via URL dengan parameter ?ktp=... dari Resource.
 */
class ProsesKenaikanKelas extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.proses-kenaikan-kelas';

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
            $this->ktp->siswaAktif->mapWithKeys(fn($siswa) => [
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
                fn($siswa) => Select::make((string) $siswa->id)
                    ->label($siswa->nama . ' (' . ($siswa->nisn ?? '-') . ')')
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
                ->body(collect($gagal)->map(fn($pesan, $nama) => "{$nama}: {$pesan}")->implode('; '))
                ->send();
        }

        $this->redirect(\App\Filament\Resources\KelasTahunPelajaranResource::getUrl());
    }
}

```
---

## app/Filament/Pages/TransaksiCepat.php
```php
<?php

namespace App\Filament\Pages;

use App\Enums\KondisiBuku;
use App\Enums\StatusPeminjaman;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Models\User;
use App\Services\PeminjamanService;
use App\Services\RfidResolverService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use RuntimeException;

/**
 * Halaman transaksi cepat: scan kartu (identifikasi user) -> scan barcode
 * buku satu per satu -> sistem OTOMATIS deteksi pinjam/kembali per buku,
 * diproses langsung tiap scan (TIDAK dikumpulkan dulu, sesuai keputusan
 * QA). Seluruh logic bisnis (limit, stok, Denda, Point, WA) tetap lewat
 * PeminjamanService - halaman ini murni orkestrasi UI (Aturan poin 3).
 *
 * Reader RFID di komputer = USB keyboard-wedge (ketik ke input fokus,
 * seperti barcode scanner), BUKAN endpoint device Attendance Machine -
 * jangan disamakan dengan PerpustakaanDeviceController.
 *
 * Otorisasi: reuse Policy existing, tidak ada permission baru untuk
 * halaman ini sendiri - akses digerbang oleh Create:Peminjaman.
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

    public ?string $kartuInput = '';

    public ?string $barcodeInput = '';

    public ?User $user = null;

    public bool $bisaMeminjam = false;

    /**
     * @var array<int, array{barcode: string, judul: string, aksi: string, pesan: string, sukses: bool}>
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

    public function scanBarcode(): void
    {
        $barcode = trim((string) $this->barcodeInput);
        $this->barcodeInput = '';

        if ($barcode === '' || ! $this->user) {
            return;
        }

        $buku = Buku::query()->where('barcode', $barcode)->first();

        if (! $buku) {
            $this->tambahRiwayat($barcode, '-', 'error', 'Barcode tidak ditemukan.', false);

            return;
        }

        // Deteksi otomatis: ada Peminjaman aktif/terlambat milik user ini
        // untuk buku ini -> kembalikan. Kalau tidak -> pinjam baru.
        $peminjamanAktif = Peminjaman::query()
            ->where('buku_id', $buku->id)
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
                $this->tambahRiwayat($barcode, $buku->judul, 'dikembalikan', 'Berhasil dikembalikan (kondisi: baik).', true);
            } else {
                $service->pinjamBuku(
                    user: $this->user,
                    bukuIds: [$buku->id],
                    diprosesOleh: auth()->user(),
                );
                $this->tambahRiwayat($barcode, $buku->judul, 'dipinjamkan', 'Berhasil dipinjamkan.', true);
            }
        } catch (RuntimeException $e) {
            $this->tambahRiwayat($barcode, $buku->judul, 'error', $e->getMessage(), false);
        }

        $this->bisaMeminjam = app(PeminjamanService::class)->bisaMeminjam($this->user->fresh());
    }

    public function selesai(): void
    {
        $this->user = null;
        $this->riwayatScan = [];
        $this->bisaMeminjam = false;
        $this->kartuInput = '';
        $this->barcodeInput = '';
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

use App\Filament\Resources\BukuResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBuku extends CreateRecord
{
    protected static string $resource = BukuResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
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

use App\Filament\Exports\BukuExporter;
use App\Filament\Imports\BukuImporter;
use App\Filament\Resources\BukuResource\Pages;
use App\Models\Buku;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                ->maxLength(255),
            TextInput::make('barcode')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Select::make('rak_id')
                ->label('Rak')
                ->relationship('rak', 'nama')
                ->searchable()
                ->preload(),
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
                // TODO: GAP-SPEC - dipakai sebagai basis Denda.kerusakan (persentase
                // dari Setting persentase_denda_kerusakan) dan Denda.kehilangan
                // (penuh) - lihat PeminjamanService::hitungDendaKerusakan/Kehilangan.
                // Wajib diisi akurat oleh Pustakawan/Admin saat input buku baru.
                ->helperText('Dipakai sebagai basis perhitungan Denda kerusakan/kehilangan.'),
            TextInput::make('stok')
                ->numeric()
                ->required()
                ->default(1),
            Textarea::make('deskripsi')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(BukuImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Buku::class) ?? false),
                ExportAction::make()
                    ->exporter(BukuExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Buku::class) ?? false),
            ])
            ->columns([
                ImageColumn::make('cover')
                    ->square(),
                TextColumn::make('judul')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('rak.nama')
                    ->label('Rak')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('stok')
                    ->sortable(),
                TextColumn::make('harga_ganti')
                    ->label('Harga Ganti')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('rak_id')
                    ->label('Rak')
                    ->relationship('rak', 'nama'),
                SelectFilter::make('kategoris')
                    ->label('Kategori')
                    ->relationship('kategoris', 'nama'),
            ]);
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

use App\Enums\StatusRefund;
use App\Enums\TipeDenda;
use App\Filament\Resources\DendaResource\Pages;
use App\Models\Denda;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
            ->columns([
                TextColumn::make('user.nama')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('peminjaman.buku.judul')
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
                    ->dateTime()
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
                    ->dateTime()
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

                DeleteAction::make(), // digerbang DendaPolicy::delete() - hanya Admin, lihat ShieldSeeder
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

use App\Filament\Resources\JurusanResource\Pages;
use App\Models\Jurusan;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('kode')->searchable(),
                TextColumn::make('kelas_count')->label('Jumlah Kelas')->counts('kelas'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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
                TextColumn::make('created_at')
                    ->dateTime()
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

use App\Filament\Resources\KelasResource\Pages;
use App\Models\Kelas;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('tingkat')->sortable(),
                TextColumn::make('jurusan.nama')->label('Jurusan')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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

use App\Filament\Pages\ProsesKenaikanKelas;
use App\Filament\Resources\KelasTahunPelajaranResource\Pages;
use App\Filament\Resources\KelasTahunPelajaranResource\RelationManagers\SiswaAktifRelationManager;
use App\Models\KelasTahunPelajaran;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
                ->relationship('waliKelas', 'nama', fn ($query) => $query->whereIn('role', ['pustakawan', 'pegawai', 'super_admin']))
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                ->url(fn(KelasTahunPelajaran $record) => ProsesKenaikanKelas::getUrl(['ktp' => $record->id])),
                DeleteAction::make()
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

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only - assignment siswa ke KTP dilakukan lewat UserResource
 * (edit user, pilih kelas_tahun_pelajaran_id) atau Action Kenaikan Kelas
 * massal (menyusul), BUKAN attach/detach di sini, karena relasi ini
 * belongsTo di sisi User (kelas_tahun_pelajaran_id), bukan pivot.
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
            ->headerActions([])
            ->recordActions([])
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
use App\Filament\Resources\KunjunganResource\Pages;
use App\Models\Kunjungan;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
            ->columns([
                TextColumn::make('user.nama')
                    ->label('Pengunjung')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->date()
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
                    ->dateTime()
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
                bukuIds: $data['buku_ids'],
                diprosesOleh: auth()->user(),
            );

            // Filament expects instance dari $this->getModel() (Peminjaman) -
            // kembalikan salah satu baris hasil transaksi (bisa multi-buku).
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
use App\Enums\StatusPeminjaman;
use App\Filament\Resources\PeminjamanResource\Pages;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use Filament\Actions\Action;
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
            Select::make('buku_ids')
                ->label('Buku')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(fn () => Buku::query()->where('stok', '>', 0)->pluck('judul', 'id'))
                ->helperText('Hanya menampilkan buku dengan stok tersedia. Validasi limit peminjaman aktif & status suspend dicek otomatis saat submit.')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nama')
                    ->label('Peminjam')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('buku.judul')
                    ->label('Buku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_pinjam')
                    ->date()
                    ->sortable(),
                TextColumn::make('tanggal_jatuh_tempo')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (StatusPeminjaman $state) => match ($state) {
                        StatusPeminjaman::Aktif => 'success',
                        StatusPeminjaman::Terlambat => 'danger',
                        StatusPeminjaman::Selesai => 'gray',
                        StatusPeminjaman::Hilang => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(StatusPeminjaman::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])),
            ])
            ->recordActions([
                Action::make('proses_pengembalian')
                    ->label('Proses Pengembalian')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (Peminjaman $record) => in_array($record->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true))
                    ->schema([
                        Select::make('kondisi')
                            ->label('Kondisi Buku')
                            ->options(collect(KondisiBuku::cases())->mapWithKeys(fn ($k) => [$k->value => ucfirst($k->value)]))
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
                    ->requiresConfirmation()
                    ->modalDescription('Buku belum dikembalikan secara fisik. Denda kehilangan penuh (Buku.harga_ganti) akan langsung dicatat.')
                    ->visible(fn (Peminjaman $record) => in_array($record->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true))
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
use App\Filament\Resources\PengembalianResource\Pages;
use App\Models\Pengembalian;
use App\Services\PeminjamanService;
use Filament\Actions\Action;
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
 * TODO: ShieldSeeder perlu diberi permission 'Update:Pengembalian' untuk
 * role Pustakawan DAN Admin (dikonfirmasi keduanya boleh koreksi) - Action
 * ini digerbang oleh PengembalianPolicy::update().
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
            ->columns([
                TextColumn::make('peminjaman.user.nama')
                    ->label('Peminjam')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('peminjaman.buku.judul')
                    ->label('Buku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_kembali')
                    ->date()
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
use App\Filament\Resources\RakResource\RelationManagers\BukusRelationManager;
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
                TextColumn::make('bukus_count')
                    ->label('Jumlah Buku')
                    ->counts('bukus')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
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
            BukusRelationManager::class,
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

## app/Filament/Resources/RakResource/RelationManagers/BukusRelationManager.php
```php
<?php

namespace App\Filament\Resources\RakResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Rak -> Buku adalah relasi one-to-many ASLI (Buku.rak_id), bukan pivot -
 * jadi RelationManager ini murni untuk LIHAT buku yang ada di rak ini.
 * Create/Edit/Delete Buku tetap lewat BukuResource langsung (di sana Admin/
 * Pustakawan bisa ganti rak_id-nya) - supaya tidak ada dua tempat berbeda
 * yang bisa mengubah data Buku yang sama (Aturan poin 3, DRY).
 */
class BukusRelationManager extends RelationManager
{
    protected static string $relationship = 'bukus';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('judul')
            ->columns([
                TextColumn::make('judul')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('stok'),
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

use App\Filament\Resources\TahunPelajaranResource\Pages;
use App\Models\TahunPelajaran;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('tanggal_mulai')->date(),
                TextColumn::make('tanggal_selesai')->date(),
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
use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\RelationManagers\PeminjamansRelationManager;
use App\Models\Transaksi;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
                    ->dateTime()
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
                TextColumn::make('buku.judul')
                    ->label('Buku')
                    ->searchable(),
                TextColumn::make('tanggal_jatuh_tempo')
                    ->date(),
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
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
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
            DeleteAction::make(),
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

use App\Enums\RoleUser;
use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
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
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use App\Services\KenaikanKelasService;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select as FormSelect; // hindari bentrok nama jika perlu, atau pakai Select yang sudah ada
use App\Models\KelasTahunPelajaran;

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
                ->options(collect(RoleUser::cases())->mapWithKeys(fn($r) => [$r->value => ucfirst(str_replace('_', ' ', $r->value))]))
                ->required(),
            TextInput::make('nisn')
                ->label('NISN')
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('nip')
                ->label('NIP')
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('kelas')
                ->maxLength(255),
            TextInput::make('jabatan')
                ->maxLength(255),
            TextInput::make('no_telepon')
                ->label('No. Telepon')
                ->required()
                ->maxLength(255),
            TextInput::make('no_kartu_rfid')
                ->label('No. Kartu RFID')
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn(string $operation) => $operation === 'create')
                ->dehydrated(fn(?string $state) => filled($state))
                ->maxLength(255)
                ->helperText('Kosongkan jika tidak ingin mengubah password.'),
            FileUpload::make('avatar')
                ->image()
                ->directory('user-avatar'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(UserImporter::class)
                    ->authorize(fn() => auth()->user()?->can('create', User::class) ?? false),
                ExportAction::make()
                    ->exporter(UserExporter::class)
                    ->authorize(fn() => auth()->user()?->can('viewAny', User::class) ?? false),
            ])
            ->columns([
                ImageColumn::make('avatar')
                    ->circular(),
                TextColumn::make('nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn(RoleUser $state) => match ($state) {
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
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(collect(RoleUser::cases())->mapWithKeys(fn($r) => [$r->value => ucfirst(str_replace('_', ' ', $r->value))])),
                TernaryFilter::make('status_suspend')
                    ->label('Status Suspend'),
            ])
            ->recordActions([
                DeleteAction::make()
                    // super_admin tidak boleh dihapus, termasuk oleh
                    // sesama super_admin - mencegah lock-out akun sistem.
                    ->authorize(fn(User $record) => ! $record->hasRole('super_admin')
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
                                    ->mapWithKeys(fn(KelasTahunPelajaran $ktp) => [
                                        $ktp->id => "{$ktp->kelas->nama} - {$ktp->tahunPelajaran->nama}",
                                    ])
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Support\Collection $records, array $data) {
                        $ktp = KelasTahunPelajaran::query()->findOrFail($data['kelas_tahun_pelajaran_id']);
                        $service = app(KenaikanKelasService::class);

                        $records->each(fn(User $user) => $service->assignKelas($user, $ktp));

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title($records->count() . ' user berhasil di-assign ke kelas.')
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make()
                    // Filter record super_admin keluar dari proses bulk
                    // delete - baris super_admin yang ikut ter-select akan
                    // dilewati (tidak ikut terhapus), bukan meng-error-kan
                    // seluruh aksi.
                    ->action(function (Collection $records) {
                        $dilindungi = $records->filter(fn(User $u) => $u->hasRole('super_admin'));
                        $bolehHapus = $records->reject(fn(User $u) => $u->hasRole('super_admin'));

                        $bolehHapus->each->delete();

                        if ($dilindungi->isNotEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('Sebagian user tidak dihapus')
                                ->body($dilindungi->count() . ' user dengan role super_admin dilewati (tidak bisa dihapus lewat bulk delete).')
                                ->send();
                        }
                    })
                    ->authorize(fn() => auth()->user()?->can('deleteAny', User::class) ?? false),
            ])
            // Checkbox baris super_admin dinonaktifkan supaya tidak bisa
            // ikut ter-select sama sekali (lapisan pencegahan pertama,
            // sebelum sampai ke action() di atas).
            ->checkIfRecordIsSelectableUsing(fn(User $record) => ! $record->hasRole('super_admin'));
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

## app/Filament/Widgets/DendaTerbaruWidget.php
```php
<?php

namespace App\Filament\Widgets;

use App\Models\Denda;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Denda terbaru yang belum lunas - untuk Admin & Pustakawan.
 */
class DendaTerbaruWidget extends TableWidget
{
    protected static ?int $sort = 4;

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
                    ->where('status_lunas', false)
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('user.nama')->label('User'),
                TextColumn::make('tipe')->label('Tipe')->badge(),
                TextColumn::make('nominal')->label('Nominal')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextColumn::make('created_at')->label('Tanggal')->dateTime('d M Y H:i'),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Peminjaman yang mendekati/melewati jatuh tempo - untuk Admin & Pustakawan.
 * TODO: verifikasi signature terhadap versi filament/filament ^5.7 -
 * TableWidget/table() API diasumsikan sama seperti pola Resource table().
 */
class PeminjamanJatuhTempoWidget extends TableWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getTableHeading(): string
    {
        return 'Peminjaman Perlu Perhatian (Jatuh Tempo Terdekat)';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Peminjaman::query()
                    ->whereIn('status', [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat])
                    ->orderBy('tanggal_jatuh_tempo')
            )
            ->columns([
                TextColumn::make('user.nama')->label('Peminjam'),
                TextColumn::make('buku.judul')->label('Buku'),
                TextColumn::make('tanggal_jatuh_tempo')->label('Jatuh Tempo')->date('d M Y'),
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

use App\Enums\StatusPeminjaman;
use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ringkasan operasional harian - untuk Admin & Pustakawan.
 * TODO: verifikasi signature terhadap versi package yang terpasang
 * (filament/filament ^5.7) - namespace Filament\Widgets\StatsOverviewWidget
 * diasumsikan stabil sejak v3, belum dicek ulang untuk v5.
 */
class PeminjamanStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getStats(): array
    {
        $aktif = Peminjaman::query()->where('status', StatusPeminjaman::Aktif)->count();
        $terlambat = Peminjaman::query()->where('status', StatusPeminjaman::Terlambat)->count();

        $dendaBelumLunas = Denda::query()->where('status_lunas', false);
        $jumlahDendaBelumLunas = $dendaBelumLunas->count();
        $nominalDendaBelumLunas = (clone $dendaBelumLunas)->sum('nominal');

        $kunjunganHariIni = Kunjungan::query()->whereDate('tanggal', now()->toDateString())->count();

        return [
            Stat::make('Peminjaman Aktif', (string) $aktif)
                ->color('success'),

            Stat::make('Peminjaman Terlambat', (string) $terlambat)
                ->color($terlambat > 0 ? 'danger' : 'gray'),

            Stat::make('Denda Belum Lunas', $jumlahDendaBelumLunas.' transaksi')
                ->description('Rp '.number_format((float) $nominalDendaBelumLunas, 0, ',', '.'))
                ->color($jumlahDendaBelumLunas > 0 ? 'warning' : 'gray'),

            Stat::make('Kunjungan Hari Ini', (string) $kunjunganHariIni)
                ->color('info'),
        ];
    }
}

```
---

## app/Filament/Widgets/TrenKunjunganChartWidget.php
```php
<?php

namespace App\Filament\Widgets;

use App\Models\Kunjungan;
use Filament\Widgets\ChartWidget;

/**
 * Tren kunjungan 14 hari terakhir - untuk Admin & Pustakawan.
 * TODO: verifikasi signature getData()/getType() terhadap versi
 * filament/filament ^5.7 yang terpasang.
 */
class TrenKunjunganChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return 'Tren Kunjungan (14 Hari Terakhir)';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getData(): array
    {
        $mulai = now()->subDays(13)->startOfDay();

        $data = Kunjungan::query()
            ->selectRaw('DATE(tanggal) as tgl, COUNT(*) as total')
            ->where('tanggal', '>=', $mulai->toDateString())
            ->groupBy('tgl')
            ->orderBy('tgl')
            ->pluck('total', 'tgl');

        $labels = [];
        $values = [];

        for ($i = 0; $i < 14; $i++) {
            $tanggal = $mulai->copy()->addDays($i);
            $key = $tanggal->toDateString();

            $labels[] = $tanggal->translatedFormat('d M');
            $values[] = (int) ($data[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Kunjungan',
                    'data' => $values,
                    'borderColor' => '#06b6d4',
                    'backgroundColor' => 'rgba(6, 182, 212, 0.15)',
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
}

```
---

## app/Http/Controllers/Api/PerpustakaanDeviceController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventTypePoint;
use App\Enums\SourceKunjungan;
use App\Http\Controllers\Controller;
use App\Models\DeviceLog;
use App\Models\FirmwareRelease;
use App\Models\Kunjungan;
use App\Models\Setting;
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

        return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'ok'];
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

## app/Jobs/KirimNotifikasiWhatsapp.php
```php
<?php

namespace App\Jobs;

use App\Exceptions\WhatsappGatewayException;
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
 */
class KirimNotifikasiWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Konsisten dengan --tries=3 pada supervisor worker queue 'whatsapp'
     * (lihat conf.d/*.conf, program *-whatsapp).
     */
    public int $tries = 3;

    /**
     * Konsisten dengan --timeout=30 pada supervisor worker queue 'whatsapp'.
     * Job tidak boleh berjalan lebih lama dari timeout worker.
     */
    public int $timeout = 25;

    /**
     * Backoff singkat karena kegagalan WA umumnya transient (rate limit,
     * sesi belum ready) - lihat dok kontrak API §9 Guard Rail.
     */
    public array $backoff = [5, 15, 30];

    /**
     * Status code gateway yang bersifat PERMANEN (retry tidak akan mengubah
     * hasil, sesuai kontrak API §2.2):
     * - 400: body/media/variabel tidak valid - kesalahan payload yang kita
     *   kirim sendiri, tidak berubah walau di-retry.
     * - 403: template_code tidak ditemukan/tidak terkait ke API key -
     *   kesalahan konfigurasi Admin di panel gateway, bukan transient.
     * - 409: reference_id sudah dipakai dengan payload BERBEDA - retry
     *   dengan payload sama akan 409 lagi terus (lihat kontrak API §2.2).
     *
     * Di luar daftar ini (401 HMAC, 429 guard rail, 500 internal) dianggap
     * transient dan tetap mengikuti siklus retry/backoff normal.
     */
    private const STATUS_PERMANEN = [400, 403, 409];

    public function __construct(
        protected string $templateCode,
        protected string $nomorTujuan,
        protected array $variables,
        protected ?string $referenceId,
    ) {}

    public function handle(WhatsappService $whatsappService): void
    {
        try {
            $whatsappService->kirimPesan(
                templateCode: $this->templateCode,
                recipient: $this->nomorTujuan,
                variables: $this->variables,
                referenceId: $this->referenceId,
            );
        } catch (WhatsappGatewayException $e) {
            if (in_array($e->statusCode, self::STATUS_PERMANEN, true)) {
                Log::error("KirimNotifikasiWhatsapp: kegagalan permanen (status {$e->statusCode}), tidak di-retry. Template '{$this->templateCode}' ke {$this->nomorTujuan}: {$e->getMessage()}");

                // fail() langsung memindahkan job ke failed_jobs tanpa
                // menghabiskan sisa percobaan $tries - retry dipastikan
                // sia-sia untuk status di STATUS_PERMANEN.
                $this->fail($e);

                return;
            }

            Log::error("KirimNotifikasiWhatsapp: gagal mengirim template '{$this->templateCode}' ke {$this->nomorTujuan}: {$e->getMessage()}");

            // Transient (401/429/500 dsb.) - lempar ulang supaya queue
            // worker retry sesuai $tries/$backoff.
            throw $e;
        }
    }

    /**
     * Dipanggil otomatis oleh queue setelah seluruh percobaan ($tries) habis
     * ATAU setelah $this->fail() dipanggil eksplisit di handle().
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("KirimNotifikasiWhatsapp: job gagal permanen. Template '{$this->templateCode}' ke {$this->nomorTujuan}: {$exception->getMessage()}");
    }
}

```
---

## app/Models/Buku.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'barcode',
        'rak_id',
        'harga_ganti',
        'stok',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'harga_ganti' => 'decimal:2',
        ];
    }

    public function rak(): BelongsTo
    {
        return $this->belongsTo(Rak::class);
    }

    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class);
    }

    public function peminjamans(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
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
    ];

    protected function casts(): array
    {
        return [
            'sd_ok' => 'boolean',
            'online' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
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

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kategori extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    public function bukus(): BelongsToMany
    {
        return $this->belongsToMany(Buku::class);
    }

    public function raks(): BelongsToMany
    {
        return $this->belongsToMany(Rak::class);
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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
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
        'buku_id',
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

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class);
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

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rak extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama',
        'lokasi',
    ];

    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class);
    }

    public function bukus(): HasMany
    {
        return $this->hasMany(Buku::class);
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
            'status_akademik' => StatusAkademik::class,
            'status_suspend' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getFilamentName(): string
    {
        return $this->nama;
    }

    /**
     * Konfirmasi Aturan: SATU panel untuk semua role, pembatasan akses
     * dilakukan lewat Policy per Resource (bukan di sini). Semua role yang
     * berhasil login (termasuk yang status_suspend = true, karena mereka
     * tetap perlu melihat Denda/Punishment miliknya sendiri untuk tahu
     * alasan suspend) lolos ke panel. Guard sesungguhnya (Siswa tidak bisa
     * CRUD Buku, Pustakawan tidak bisa ubah Setting, dst.) ditulis di
     * masing-masing app/Policies/*Policy.php, di-enforce via Shield.
     *
     * status_akademik = Lulus TETAP bisa akses panel (dikonfirmasi Aturan
     * - akun tidak dinonaktifkan saat lulus).
     */
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

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Buku;
use Illuminate\Auth\Access\HandlesAuthorization;

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

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Denda;
use Illuminate\Auth\Access\HandlesAuthorization;

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

## app/Policies/JurusanPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Jurusan;
use Illuminate\Auth\Access\HandlesAuthorization;

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

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Kategori;
use Illuminate\Auth\Access\HandlesAuthorization;

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

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Kelas;
use Illuminate\Auth\Access\HandlesAuthorization;

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

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KelasTahunPelajaran;
use Illuminate\Auth\Access\HandlesAuthorization;

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

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Kunjungan;
use Illuminate\Auth\Access\HandlesAuthorization;

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

## app/Policies/PeminjamanPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Peminjaman;
use Illuminate\Auth\Access\HandlesAuthorization;

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

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Pengembalian;
use Illuminate\Auth\Access\HandlesAuthorization;

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

## app/Policies/RakPolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Rak;
use Illuminate\Auth\Access\HandlesAuthorization;

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

## app/Policies/RolePolicy.php
```php
<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

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

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TahunPelajaran;
use Illuminate\Auth\Access\HandlesAuthorization;

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

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Transaksi;
use Illuminate\Auth\Access\HandlesAuthorization;

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

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

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
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->globalSearch(false)
            ->default()
            ->databaseNotifications()
            ->id('dashboard')
            ->path('dashboard')
            ->login(Login::class)
            ->spa()

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
            ->widgets([
                // \App\Filament\Widgets\PeminjamanStatsWidget::class,
                // \App\Filament\Widgets\TrenKunjunganChartWidget::class,
                // \App\Filament\Widgets\PeminjamanJatuhTempoWidget::class,
                // \App\Filament\Widgets\DendaTerbaruWidget::class,
            ])
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
     * Proses kenaikan kelas massal dari satu KTP asal.
     *
     * @param  array<string, string>  $keputusan  [user_id => 'naik'|'tinggal'|'lulus'|'keluar']
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

    protected function prosesNaik(User $user, \App\Models\Kelas $kelasAsal, TahunPelajaran $tahunTujuan): void
    {
        $kelasTujuan = \App\Models\Kelas::query()
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
            throw new RuntimeException("KTP tujuan ({$kelasTujuan->nama} - {$tahunTujuan->nama}) belum dibuat.");
        }

        $this->pindahKe($user, $ktpTujuan, StatusRiwayatKelas::Naik);
    }

    protected function prosesTinggal(User $user, \App\Models\Kelas $kelasAsal, TahunPelajaran $tahunTujuan): void
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
        $this->tutupRiwayatAktif($user, StatusRiwayatKelas::Keluar);

        $user->update([
            'kelas_tahun_pelajaran_id' => null,
            'status_akademik' => StatusAkademik::Keluar,
        ]);
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

## app/Services/LaporanBulananService.php
```php
<?php

namespace App\Services;

use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Point;
use Illuminate\Support\Carbon;

/**
 * Satu sumber kebenaran agregasi data untuk Laporan Bulanan (Aturan poin 3)
 * - dipanggil dari LaporanBulanan Page, jangan duplikasi query di tempat lain.
 *
 * TODO: GAP-SPEC - filter tanggal per domain memakai kolom "kejadian"
 * masing-masing (tanggal_pinjam, tanggal_kembali, created_at untuk
 * Denda/Point, tanggal untuk Kunjungan) - bukan tanggal_lunas untuk Denda.
 * Perlu dikonfirmasi jika laporan dimaksudkan sebagai laporan kas/arus
 * pemasukan (yang mestinya pakai tanggal_lunas), bukan laporan aktivitas.
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
        ];
    }

    protected function dataPeminjaman(Carbon $awal, Carbon $akhir): array
    {
        $records = Peminjaman::query()
            ->with(['user', 'buku'])
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
            ->with(['peminjaman.user', 'peminjaman.buku'])
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
            ->with(['user', 'peminjaman.buku'])
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
use App\Enums\StatusPeminjaman;
use App\Enums\TipeDenda;
use App\Models\Buku;
use App\Models\Denda;
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
     * @param  array<int, string>  $bukuIds
     */
    public function pinjamBuku(User $user, array $bukuIds, ?User $diprosesOleh = null): Transaksi
    {
        if (! $this->bisaMeminjam($user)) {
            throw new RuntimeException('User tidak dapat meminjam: suspend aktif atau limit peminjaman aktif tercapai.');
        }

        $lamaPeminjamanHari = (int) Setting::get('lama_peminjaman_hari', 7);

        $transaksi = DB::transaction(function () use ($user, $bukuIds, $diprosesOleh, $lamaPeminjamanHari) {
            $transaksi = Transaksi::create([
                'user_id' => $user->id,
                'jenis' => JenisTransaksi::Peminjaman,
                'diproses_oleh' => $diprosesOleh?->id,
                'tanggal' => now(),
            ]);

            foreach ($bukuIds as $bukuId) {
                $buku = Buku::query()->lockForUpdate()->findOrFail($bukuId);

                if ($buku->stok < 1) {
                    throw new RuntimeException("Stok buku '{$buku->judul}' habis.");
                }

                $buku->decrement('stok');

                $peminjaman = Peminjaman::create([
                    'transaksi_id' => $transaksi->id,
                    'user_id' => $user->id,
                    'buku_id' => $buku->id,
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

            return $transaksi->fresh('peminjamans.buku');
        });

        $daftarBuku = $transaksi->peminjamans->pluck('buku.judul')->implode(', ');
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
                $this->tandaiDenda($peminjaman, TipeDenda::Kehilangan, $this->hitungDendaKehilangan($peminjaman->buku));
                $peminjaman->update(['status' => StatusPeminjaman::Hilang]);

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
                $this->tandaiDenda($peminjaman, TipeDenda::Kerusakan, $this->hitungDendaKerusakan($peminjaman->buku));

                $this->pointService->catatEvent(
                    $peminjaman->user,
                    EventTypePoint::Kerusakan,
                    'peminjaman',
                    $peminjaman->id,
                );
            }

            $peminjaman->buku()->increment('stok');
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

    /**
     * Koreksi kondisi Pengembalian yang SUDAH final (dipanggil dari Action
     * "Koreksi Kondisi" di PengembalianResource - Pustakawan/Admin, lihat
     * PengembalianPolicy). Method TERPISAH dari prosesPengembalian() supaya
     * alur normal tidak berubah (Aturan poin 9/10).
     *
     * Efek: sesuaikan stok, batalkan Denda dari kondisi lama yang sudah
     * tidak relevan, catat Denda baru sesuai kondisi baru, update status
     * Peminjaman, update record Pengembalian.
     */
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
            // Sesuaikan stok: buku fisik dianggap kembali/hilang sesuai kondisi baru.
            if ($kondisiLama === KondisiBuku::Hilang && $kondisiBaru !== KondisiBuku::Hilang) {
                $peminjaman->buku()->increment('stok');
            } elseif ($kondisiLama !== KondisiBuku::Hilang && $kondisiBaru === KondisiBuku::Hilang) {
                $peminjaman->buku()->decrement('stok');
            }

            // Batalkan Denda dari kondisi lama yang tidak lagi berlaku.
            if ($kondisiLama === KondisiBuku::Rusak && $kondisiBaru !== KondisiBuku::Rusak) {
                $this->batalkanDenda($peminjaman, TipeDenda::Kerusakan);
            }
            if ($kondisiLama === KondisiBuku::Hilang && $kondisiBaru !== KondisiBuku::Hilang) {
                $this->batalkanDenda($peminjaman, TipeDenda::Kehilangan);
            }

            // Catat Denda baru sesuai kondisi baru.
            if ($kondisiBaru === KondisiBuku::Rusak && $kondisiLama !== KondisiBuku::Rusak) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kerusakan, $this->hitungDendaKerusakan($peminjaman->buku));
                $this->pointService->catatEvent($peminjaman->user, EventTypePoint::Kerusakan, 'peminjaman', $peminjaman->id, 'Koreksi kondisi ke rusak');
            }
            if ($kondisiBaru === KondisiBuku::Hilang && $kondisiLama !== KondisiBuku::Hilang) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kehilangan, $this->hitungDendaKehilangan($peminjaman->buku));
                $this->pointService->catatEvent($peminjaman->user, EventTypePoint::Kehilangan, 'peminjaman', $peminjaman->id, 'Koreksi kondisi ke hilang');
            }

            // TODO: GAP-SPEC - Point yang sudah tercatat dari kondisi lama
            // (mis. event Pengembalian/Kerusakan/Kehilangan sebelumnya) TIDAK
            // di-reverse saat koreksi; hanya menambah event baru sesuai
            // kondisi baru. Akumulasi point/badge/reward/punishment tidak
            // mundur otomatis - butuh keputusan terpisah jika diperlukan.

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

        $denda = DB::transaction(function () use ($peminjaman) {
            $denda = $this->tandaiDenda(
                $peminjaman,
                TipeDenda::Kehilangan,
                $this->hitungDendaKehilangan($peminjaman->buku),
            );

            $peminjaman->update(['status' => StatusPeminjaman::Hilang]);

            $this->pointService->catatEvent(
                $peminjaman->user,
                EventTypePoint::Kehilangan,
                'peminjaman',
                $peminjaman->id,
            );

            return $denda;
        });

        $this->whatsappService->kirimEvent(
            eventCode: 'denda_dibuat',
            nomorTujuan: $peminjaman->user->no_telepon,
            variables: ['nama' => $peminjaman->user->nama, 'tipe' => 'kehilangan', 'nominal' => (string) $denda->nominal],
            referenceId: "denda-{$denda->id}",
        );

        return $denda;
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
            ->with('user', 'buku')
            ->chunkById(200, function ($peminjamans) use ($today, &$stat) {
                foreach ($peminjamans as $peminjaman) {
                    $jatuhTempo = Carbon::parse($peminjaman->tanggal_jatuh_tempo);

                    if ($jatuhTempo->isSameDay($today->copy()->addDays(3))) {
                        $this->whatsappService->kirimEvent(
                            eventCode: 'reminder_h3',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->buku->judul, 'jatuh_tempo' => (string) $peminjaman->tanggal_jatuh_tempo],
                            referenceId: "reminder-h3-{$peminjaman->id}-{$today->toDateString()}",
                        );
                        $stat['reminder_h3']++;
                    } elseif ($jatuhTempo->isSameDay($today->copy()->addDay())) {
                        $this->whatsappService->kirimEvent(
                            eventCode: 'reminder_h1',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->buku->judul, 'jatuh_tempo' => (string) $peminjaman->tanggal_jatuh_tempo],
                            referenceId: "reminder-h1-{$peminjaman->id}-{$today->toDateString()}",
                        );
                        $stat['reminder_h1']++;
                    } elseif ($jatuhTempo->lt($today)) {
                        $peminjaman->update(['status' => StatusPeminjaman::Terlambat]);

                        $this->whatsappService->kirimEvent(
                            eventCode: 'jadi_terlambat',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->buku->judul],
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
     * Batalkan Denda aktif (tipe tertentu) milik satu Peminjaman - dipanggil
     * saat koreksi kondisi Pengembalian menghilangkan alasan Denda tersebut.
     * TIDAK soft-delete (riwayat tetap auditable) - nominal dipaksa 0 dan
     * status_lunas dipaksa true (memicu DendaObserver::updated() -> cek
     * auto-unsuspend user, sudah dikonfirmasi sebagai perilaku yang diinginkan).
     */
    protected function batalkanDenda(Peminjaman $peminjaman, TipeDenda $tipe): void
    {
        $denda = Denda::query()
            ->where('peminjaman_id', $peminjaman->id)
            ->where('tipe', $tipe)
            ->latest()
            ->first();

        if (! $denda || (float) $denda->nominal === 0.0) {
            return; // tidak ada Denda aktif untuk tipe ini, atau sudah pernah dibatalkan
        }

        $sudahTerbayar = $denda->status_lunas;

        $denda->update([
            'nominal' => 0,
            'status_lunas' => true,
            'tanggal_lunas' => now(),
            'keterangan' => trim(($denda->keterangan ? $denda->keterangan.' | ' : '')
                .($sudahTerbayar
                    ? 'Dibatalkan otomatis (SUDAH TERBAYAR SEBELUM KOREKSI - perlu refund manual di luar sistem): koreksi kondisi Pengembalian.'
                    : 'Dibatalkan otomatis: koreksi kondisi Pengembalian.')),
        ]);

        // TODO: GAP-SPEC - jika $sudahTerbayar true, sistem ini tidak
        // menangani proses refund (uang fisik/transfer) - hanya menandai
        // audit trail. Refund di luar sistem, keputusan Admin/Pustakawan.
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
     * Update level_badge_id user jika akumulasi_point masuk rentang badge lain.
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

            // eventCode 'badge_naik' - TODO: ASUMSI, samakan dengan Setting
            // wa_template_badge_naik yang harus diisi Admin di panel WA Gateway.
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

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('dashboard');
});

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

    'timezone' => 'UTC',

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

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

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

use App\Models\Rak;
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
            'barcode' => fake()->unique()->ean13(),
            'rak_id' => Rak::factory(),
            'harga_ganti' => fake()->randomFloat(2, 0, 500000),
            'stok' => fake()->numberBetween(0, 20),
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
use App\Models\Buku;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeminjamanFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'transaksi_id' => Transaksi::factory(),
            'user_id' => User::factory(),
            'buku_id' => Buku::factory(),
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

return new class extends Migration
{
    public function up(): void
    {
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

                'ViewAny:LaporanBulanan',

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
    41 => 'Laravel\\Pail\\PailServiceProvider',
    42 => 'Laravel\\Pao\\Laravel\\ServiceProvider',
    43 => 'Laravel\\Tinker\\TinkerServiceProvider',
    44 => 'Livewire\\LivewireServiceProvider',
    45 => 'Carbon\\Laravel\\ServiceProvider',
    46 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    47 => 'Termwind\\Laravel\\TermwindServiceProvider',
    48 => 'RyanChandler\\BladeCaptureDirective\\BladeCaptureDirectiveServiceProvider',
    49 => 'Spatie\\Permission\\PermissionServiceProvider',
    50 => 'App\\Providers\\AppServiceProvider',
    51 => 'App\\Providers\\Filament\\DashboardPanelProvider',
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
    26 => 'Laravel\\Pail\\PailServiceProvider',
    27 => 'Laravel\\Pao\\Laravel\\ServiceProvider',
    28 => 'Livewire\\LivewireServiceProvider',
    29 => 'Carbon\\Laravel\\ServiceProvider',
    30 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    31 => 'Termwind\\Laravel\\TermwindServiceProvider',
    32 => 'RyanChandler\\BladeCaptureDirective\\BladeCaptureDirectiveServiceProvider',
    33 => 'Spatie\\Permission\\PermissionServiceProvider',
    34 => 'App\\Providers\\AppServiceProvider',
    35 => 'App\\Providers\\Filament\\DashboardPanelProvider',
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
    <form wire:submit="generate">
        {{ $this->form }}
        <div style="margin-top: 1.5rem;">
            <x-filament::button type="submit" icon="heroicon-o-document-arrow-down">
                Generate & Download PDF
            </x-filament::button>
        </div>
    </form>
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
    @if (! $user)
        <div class="rounded-xl border p-6 space-y-3" x-data x-init="$refs.kartu.focus()">
            <label class="font-medium">Scan Kartu RFID</label>
            <input
                x-ref="kartu"
                type="text"
                wire:model="kartuInput"
                wire:keydown.enter="scanKartu"
                autofocus
                class="fi-input w-full rounded-lg"
                placeholder="Tempelkan/scan kartu..."
            />
        </div>
    @else
        <div class="rounded-xl border p-6 space-y-2">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-lg font-semibold">{{ $user->nama }}</p>
                    <p class="text-sm text-gray-500">
                        Status: {{ $user->status_suspend ? 'SUSPEND' : 'Aktif' }}
                        &middot;
                        {{ $bisaMeminjam ? 'Bisa meminjam' : 'Tidak bisa meminjam baru' }}
                    </p>
                </div>
                <x-filament::button color="gray" wire:click="selesai">Selesai / Ganti User</x-filament::button>
            </div>
        </div>

        <div class="rounded-xl border p-6 space-y-3 mt-4" x-data x-init="$refs.barcode.focus()">
            <label class="font-medium">Scan Barcode Buku</label>
            <input
                x-ref="barcode"
                type="text"
                wire:model="barcodeInput"
                wire:keydown.enter="scanBarcode"
                autofocus
                class="fi-input w-full rounded-lg"
                placeholder="Scan barcode buku..."
            />
        </div>

        <div class="mt-4 space-y-2">
            @foreach ($riwayatScan as $item)
                <div @class([
                    'rounded-lg border p-3 flex items-center justify-between',
                    'border-success-300 bg-success-50' => $item['sukses'],
                    'border-danger-300 bg-danger-50' => ! $item['sukses'],
                ])>
                    <div>
                        <p class="font-medium">{{ $item['judul'] }}</p>
                        <p class="text-sm text-gray-600">{{ $item['pesan'] }}</p>
                    </div>
                    <span class="text-xs uppercase font-semibold">{{ $item['aksi'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>

```
---

## resources/views/pdf/laporan-bulanan.blade.php
```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 0; }
        h2 { font-size: 13px; margin-top: 24px; margin-bottom: 6px; border-bottom: 1px solid #999; padding-bottom: 4px; }
        .subheading { color: #555; margin-top: 2px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; }
        .ringkasan-box { margin-bottom: 8px; }
        .ringkasan-box span { display: inline-block; margin-right: 16px; }
        .section { page-break-after: always; }
        .section:last-child { page-break-after: auto; }
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
                        <td>{{ $p->buku->judul }}</td>
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
                        <td>{{ $p->peminjaman->buku->judul }}</td>
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
</body>
</html>

```
---

## resources/views/welcome.blade.php
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @fonts

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */ @layer properties{@supports (((-webkit-hyphens:none)) and (not (margin-trim:inline))) or ((-moz-orient:inline) and (not (color:rgb(from red r g b)))){*,:before,:after,::backdrop{--tw-translate-x:0;--tw-translate-y:0;--tw-translate-z:0;--tw-rotate-x:initial;--tw-rotate-y:initial;--tw-rotate-z:initial;--tw-skew-x:initial;--tw-skew-y:initial;--tw-space-x-reverse:0;--tw-border-style:solid;--tw-leading:initial;--tw-font-weight:initial;--tw-tracking:initial;--tw-shadow:0 0 #0000;--tw-shadow-color:initial;--tw-shadow-alpha:100%;--tw-inset-shadow:0 0 #0000;--tw-inset-shadow-color:initial;--tw-inset-shadow-alpha:100%;--tw-ring-color:initial;--tw-ring-shadow:0 0 #0000;--tw-inset-ring-color:initial;--tw-inset-ring-shadow:0 0 #0000;--tw-ring-inset:initial;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-offset-shadow:0 0 #0000;--tw-blur:initial;--tw-brightness:initial;--tw-contrast:initial;--tw-grayscale:initial;--tw-hue-rotate:initial;--tw-invert:initial;--tw-opacity:initial;--tw-saturate:initial;--tw-sepia:initial;--tw-drop-shadow:initial;--tw-drop-shadow-color:initial;--tw-drop-shadow-alpha:100%;--tw-drop-shadow-size:initial;--tw-duration:initial;--tw-ease:initial;--tw-content:""}}}@layer theme{:root,:host{--font-sans:"Instrument Sans", ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";--font-serif:ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;--font-mono:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;--color-red-50:oklch(97.1% .013 17.38);--color-red-100:oklch(93.6% .032 17.717);--color-red-200:oklch(88.5% .062 18.334);--color-red-300:oklch(80.8% .114 19.571);--color-red-400:oklch(70.4% .191 22.216);--color-red-500:oklch(63.7% .237 25.331);--color-red-600:oklch(57.7% .245 27.325);--color-red-700:oklch(50.5% .213 27.518);--color-red-800:oklch(44.4% .177 26.899);--color-red-900:oklch(39.6% .141 25.723);--color-red-950:oklch(25.8% .092 26.042);--color-orange-50:oklch(98% .016 73.684);--color-orange-100:oklch(95.4% .038 75.164);--color-orange-200:oklch(90.1% .076 70.697);--color-orange-300:oklch(83.7% .128 66.29);--color-orange-400:oklch(75% .183 55.934);--color-orange-500:oklch(70.5% .213 47.604);--color-orange-600:oklch(64.6% .222 41.116);--color-orange-700:oklch(55.3% .195 38.402);--color-orange-800:oklch(47% .157 37.304);--color-orange-900:oklch(40.8% .123 38.172);--color-orange-950:oklch(26.6% .079 36.259);--color-amber-50:oklch(98.7% .022 95.277);--color-amber-100:oklch(96.2% .059 95.617);--color-amber-200:oklch(92.4% .12 95.746);--color-amber-300:oklch(87.9% .169 91.605);--color-amber-400:oklch(82.8% .189 84.429);--color-amber-500:oklch(76.9% .188 70.08);--color-amber-600:oklch(66.6% .179 58.318);--color-amber-700:oklch(55.5% .163 48.998);--color-amber-800:oklch(47.3% .137 46.201);--color-amber-900:oklch(41.4% .112 45.904);--color-amber-950:oklch(27.9% .077 45.635);--color-yellow-50:oklch(98.7% .026 102.212);--color-yellow-100:oklch(97.3% .071 103.193);--color-yellow-200:oklch(94.5% .129 101.54);--color-yellow-300:oklch(90.5% .182 98.111);--color-yellow-400:oklch(85.2% .199 91.936);--color-yellow-500:oklch(79.5% .184 86.047);--color-yellow-600:oklch(68.1% .162 75.834);--color-yellow-700:oklch(55.4% .135 66.442);--color-yellow-800:oklch(47.6% .114 61.907);--color-yellow-900:oklch(42.1% .095 57.708);--color-yellow-950:oklch(28.6% .066 53.813);--color-lime-50:oklch(98.6% .031 120.757);--color-lime-100:oklch(96.7% .067 122.328);--color-lime-200:oklch(93.8% .127 124.321);--color-lime-300:oklch(89.7% .196 126.665);--color-lime-400:oklch(84.1% .238 128.85);--color-lime-500:oklch(76.8% .233 130.85);--color-lime-600:oklch(64.8% .2 131.684);--color-lime-700:oklch(53.2% .157 131.589);--color-lime-800:oklch(45.3% .124 130.933);--color-lime-900:oklch(40.5% .101 131.063);--color-lime-950:oklch(27.4% .072 132.109);--color-green-50:oklch(98.2% .018 155.826);--color-green-100:oklch(96.2% .044 156.743);--color-green-200:oklch(92.5% .084 155.995);--color-green-300:oklch(87.1% .15 154.449);--color-green-400:oklch(79.2% .209 151.711);--color-green-500:oklch(72.3% .219 149.579);--color-green-600:oklch(62.7% .194 149.214);--color-green-700:oklch(52.7% .154 150.069);--color-green-800:oklch(44.8% .119 151.328);--color-green-900:oklch(39.3% .095 152.535);--color-green-950:oklch(26.6% .065 152.934);--color-emerald-50:oklch(97.9% .021 166.113);--color-emerald-100:oklch(95% .052 163.051);--color-emerald-200:oklch(90.5% .093 164.15);--color-emerald-300:oklch(84.5% .143 164.978);--color-emerald-400:oklch(76.5% .177 163.223);--color-emerald-500:oklch(69.6% .17 162.48);--color-emerald-600:oklch(59.6% .145 163.225);--color-emerald-700:oklch(50.8% .118 165.612);--color-emerald-800:oklch(43.2% .095 166.913);--color-emerald-900:oklch(37.8% .077 168.94);--color-emerald-950:oklch(26.2% .051 172.552);--color-teal-50:oklch(98.4% .014 180.72);--color-teal-100:oklch(95.3% .051 180.801);--color-teal-200:oklch(91% .096 180.426);--color-teal-300:oklch(85.5% .138 181.071);--color-teal-400:oklch(77.7% .152 181.912);--color-teal-500:oklch(70.4% .14 182.503);--color-teal-600:oklch(60% .118 184.704);--color-teal-700:oklch(51.1% .096 186.391);--color-teal-800:oklch(43.7% .078 188.216);--color-teal-900:oklch(38.6% .063 188.416);--color-teal-950:oklch(27.7% .046 192.524);--color-cyan-50:oklch(98.4% .019 200.873);--color-cyan-100:oklch(95.6% .045 203.388);--color-cyan-200:oklch(91.7% .08 205.041);--color-cyan-300:oklch(86.5% .127 207.078);--color-cyan-400:oklch(78.9% .154 211.53);--color-cyan-500:oklch(71.5% .143 215.221);--color-cyan-600:oklch(60.9% .126 221.723);--color-cyan-700:oklch(52% .105 223.128);--color-cyan-800:oklch(45% .085 224.283);--color-cyan-900:oklch(39.8% .07 227.392);--color-cyan-950:oklch(30.2% .056 229.695);--color-sky-50:oklch(97.7% .013 236.62);--color-sky-100:oklch(95.1% .026 236.824);--color-sky-200:oklch(90.1% .058 230.902);--color-sky-300:oklch(82.8% .111 230.318);--color-sky-400:oklch(74.6% .16 232.661);--color-sky-500:oklch(68.5% .169 237.323);--color-sky-600:oklch(58.8% .158 241.966);--color-sky-700:oklch(50% .134 242.749);--color-sky-800:oklch(44.3% .11 240.79);--color-sky-900:oklch(39.1% .09 240.876);--color-sky-950:oklch(29.3% .066 243.157);--color-blue-50:oklch(97% .014 254.604);--color-blue-100:oklch(93.2% .032 255.585);--color-blue-200:oklch(88.2% .059 254.128);--color-blue-300:oklch(80.9% .105 251.813);--color-blue-400:oklch(70.7% .165 254.624);--color-blue-500:oklch(62.3% .214 259.815);--color-blue-600:oklch(54.6% .245 262.881);--color-blue-700:oklch(48.8% .243 264.376);--color-blue-800:oklch(42.4% .199 265.638);--color-blue-900:oklch(37.9% .146 265.522);--color-blue-950:oklch(28.2% .091 267.935);--color-indigo-50:oklch(96.2% .018 272.314);--color-indigo-100:oklch(93% .034 272.788);--color-indigo-200:oklch(87% .065 274.039);--color-indigo-300:oklch(78.5% .115 274.713);--color-indigo-400:oklch(67.3% .182 276.935);--color-indigo-500:oklch(58.5% .233 277.117);--color-indigo-600:oklch(51.1% .262 276.966);--color-indigo-700:oklch(45.7% .24 277.023);--color-indigo-800:oklch(39.8% .195 277.366);--color-indigo-900:oklch(35.9% .144 278.697);--color-indigo-950:oklch(25.7% .09 281.288);--color-violet-50:oklch(96.9% .016 293.756);--color-violet-100:oklch(94.3% .029 294.588);--color-violet-200:oklch(89.4% .057 293.283);--color-violet-300:oklch(81.1% .111 293.571);--color-violet-400:oklch(70.2% .183 293.541);--color-violet-500:oklch(60.6% .25 292.717);--color-violet-600:oklch(54.1% .281 293.009);--color-violet-700:oklch(49.1% .27 292.581);--color-violet-800:oklch(43.2% .232 292.759);--color-violet-900:oklch(38% .189 293.745);--color-violet-950:oklch(28.3% .141 291.089);--color-purple-50:oklch(97.7% .014 308.299);--color-purple-100:oklch(94.6% .033 307.174);--color-purple-200:oklch(90.2% .063 306.703);--color-purple-300:oklch(82.7% .119 306.383);--color-purple-400:oklch(71.4% .203 305.504);--color-purple-500:oklch(62.7% .265 303.9);--color-purple-600:oklch(55.8% .288 302.321);--color-purple-700:oklch(49.6% .265 301.924);--color-purple-800:oklch(43.8% .218 303.724);--color-purple-900:oklch(38.1% .176 304.987);--color-purple-950:oklch(29.1% .149 302.717);--color-fuchsia-50:oklch(97.7% .017 320.058);--color-fuchsia-100:oklch(95.2% .037 318.852);--color-fuchsia-200:oklch(90.3% .076 319.62);--color-fuchsia-300:oklch(83.3% .145 321.434);--color-fuchsia-400:oklch(74% .238 322.16);--color-fuchsia-500:oklch(66.7% .295 322.15);--color-fuchsia-600:oklch(59.1% .293 322.896);--color-fuchsia-700:oklch(51.8% .253 323.949);--color-fuchsia-800:oklch(45.2% .211 324.591);--color-fuchsia-900:oklch(40.1% .17 325.612);--color-fuchsia-950:oklch(29.3% .136 325.661);--color-pink-50:oklch(97.1% .014 343.198);--color-pink-100:oklch(94.8% .028 342.258);--color-pink-200:oklch(89.9% .061 343.231);--color-pink-300:oklch(82.3% .12 346.018);--color-pink-400:oklch(71.8% .202 349.761);--color-pink-500:oklch(65.6% .241 354.308);--color-pink-600:oklch(59.2% .249 .584);--color-pink-700:oklch(52.5% .223 3.958);--color-pink-800:oklch(45.9% .187 3.815);--color-pink-900:oklch(40.8% .153 2.432);--color-pink-950:oklch(28.4% .109 3.907);--color-rose-50:oklch(96.9% .015 12.422);--color-rose-100:oklch(94.1% .03 12.58);--color-rose-200:oklch(89.2% .058 10.001);--color-rose-300:oklch(81% .117 11.638);--color-rose-400:oklch(71.2% .194 13.428);--color-rose-500:oklch(64.5% .246 16.439);--color-rose-600:oklch(58.6% .253 17.585);--color-rose-700:oklch(51.4% .222 16.935);--color-rose-800:oklch(45.5% .188 13.697);--color-rose-900:oklch(41% .159 10.272);--color-rose-950:oklch(27.1% .105 12.094);--color-slate-50:oklch(98.4% .003 247.858);--color-slate-100:oklch(96.8% .007 247.896);--color-slate-200:oklch(92.9% .013 255.508);--color-slate-300:oklch(86.9% .022 252.894);--color-slate-400:oklch(70.4% .04 256.788);--color-slate-500:oklch(55.4% .046 257.417);--color-slate-600:oklch(44.6% .043 257.281);--color-slate-700:oklch(37.2% .044 257.287);--color-slate-800:oklch(27.9% .041 260.031);--color-slate-900:oklch(20.8% .042 265.755);--color-slate-950:oklch(12.9% .042 264.695);--color-gray-50:oklch(98.5% .002 247.839);--color-gray-100:oklch(96.7% .003 264.542);--color-gray-200:oklch(92.8% .006 264.531);--color-gray-300:oklch(87.2% .01 258.338);--color-gray-400:oklch(70.7% .022 261.325);--color-gray-500:oklch(55.1% .027 264.364);--color-gray-600:oklch(44.6% .03 256.802);--color-gray-700:oklch(37.3% .034 259.733);--color-gray-800:oklch(27.8% .033 256.848);--color-gray-900:oklch(21% .034 264.665);--color-gray-950:oklch(13% .028 261.692);--color-zinc-50:oklch(98.5% 0 0);--color-zinc-100:oklch(96.7% .001 286.375);--color-zinc-200:oklch(92% .004 286.32);--color-zinc-300:oklch(87.1% .006 286.286);--color-zinc-400:oklch(70.5% .015 286.067);--color-zinc-500:oklch(55.2% .016 285.938);--color-zinc-600:oklch(44.2% .017 285.786);--color-zinc-700:oklch(37% .013 285.805);--color-zinc-800:oklch(27.4% .006 286.033);--color-zinc-900:oklch(21% .006 285.885);--color-zinc-950:oklch(14.1% .005 285.823);--color-neutral-50:oklch(98.5% 0 0);--color-neutral-100:oklch(97% 0 0);--color-neutral-200:oklch(92.2% 0 0);--color-neutral-300:oklch(87% 0 0);--color-neutral-400:oklch(70.8% 0 0);--color-neutral-500:oklch(55.6% 0 0);--color-neutral-600:oklch(43.9% 0 0);--color-neutral-700:oklch(37.1% 0 0);--color-neutral-800:oklch(26.9% 0 0);--color-neutral-900:oklch(20.5% 0 0);--color-neutral-950:oklch(14.5% 0 0);--color-stone-50:oklch(98.5% .001 106.423);--color-stone-100:oklch(97% .001 106.424);--color-stone-200:oklch(92.3% .003 48.717);--color-stone-300:oklch(86.9% .005 56.366);--color-stone-400:oklch(70.9% .01 56.259);--color-stone-500:oklch(55.3% .013 58.071);--color-stone-600:oklch(44.4% .011 73.639);--color-stone-700:oklch(37.4% .01 67.558);--color-stone-800:oklch(26.8% .007 34.298);--color-stone-900:oklch(21.6% .006 56.043);--color-stone-950:oklch(14.7% .004 49.25);--color-black:#000;--color-white:#fff;--spacing:.25rem;--breakpoint-sm:40rem;--breakpoint-md:48rem;--breakpoint-lg:64rem;--breakpoint-xl:80rem;--breakpoint-2xl:96rem;--container-3xs:16rem;--container-2xs:18rem;--container-xs:20rem;--container-sm:24rem;--container-md:28rem;--container-lg:32rem;--container-xl:36rem;--container-2xl:42rem;--container-3xl:48rem;--container-4xl:56rem;--container-5xl:64rem;--container-6xl:72rem;--container-7xl:80rem;--text-xs:.75rem;--text-xs--line-height:calc(1 / .75);--text-sm:.875rem;--text-sm--line-height:calc(1.25 / .875);--text-base:1rem;--text-base--line-height: 1.5 ;--text-lg:1.125rem;--text-lg--line-height:calc(1.75 / 1.125);--text-xl:1.25rem;--text-xl--line-height:calc(1.75 / 1.25);--text-2xl:1.5rem;--text-2xl--line-height:calc(2 / 1.5);--text-3xl:1.875rem;--text-3xl--line-height: 1.2 ;--text-4xl:2.25rem;--text-4xl--line-height:calc(2.5 / 2.25);--text-5xl:3rem;--text-5xl--line-height:1;--text-6xl:3.75rem;--text-6xl--line-height:1;--text-7xl:4.5rem;--text-7xl--line-height:1;--text-8xl:6rem;--text-8xl--line-height:1;--text-9xl:8rem;--text-9xl--line-height:1;--font-weight-thin:100;--font-weight-extralight:200;--font-weight-light:300;--font-weight-normal:400;--font-weight-medium:500;--font-weight-semibold:600;--font-weight-bold:700;--font-weight-extrabold:800;--font-weight-black:900;--tracking-tighter:-.05em;--tracking-tight:-.025em;--tracking-normal:0em;--tracking-wide:.025em;--tracking-wider:.05em;--tracking-widest:.1em;--leading-tight:1.25;--leading-snug:1.375;--leading-normal:1.5;--leading-relaxed:1.625;--leading-loose:2;--radius-xs:.125rem;--radius-sm:.25rem;--radius-md:.375rem;--radius-lg:.5rem;--radius-xl:.75rem;--radius-2xl:1rem;--radius-3xl:1.5rem;--radius-4xl:2rem;--shadow-2xs:0 1px #0000000d;--shadow-xs:0 1px 2px 0 #0000000d;--shadow-sm:0 1px 3px 0 #0000001a, 0 1px 2px -1px #0000001a;--shadow-md:0 4px 6px -1px #0000001a, 0 2px 4px -2px #0000001a;--shadow-lg:0 10px 15px -3px #0000001a, 0 4px 6px -4px #0000001a;--shadow-xl:0 20px 25px -5px #0000001a, 0 8px 10px -6px #0000001a;--shadow-2xl:0 25px 50px -12px #00000040;--inset-shadow-2xs:inset 0 1px #0000000d;--inset-shadow-xs:inset 0 1px 1px #0000000d;--inset-shadow-sm:inset 0 2px 4px #0000000d;--drop-shadow-xs:0 1px 1px #0000000d;--drop-shadow-sm:0 1px 2px #00000026;--drop-shadow-md:0 3px 3px #0000001f;--drop-shadow-lg:0 4px 4px #00000026;--drop-shadow-xl:0 9px 7px #0000001a;--drop-shadow-2xl:0 25px 25px #00000026;--ease-in:cubic-bezier(.4, 0, 1, 1);--ease-out:cubic-bezier(0, 0, .2, 1);--ease-in-out:cubic-bezier(.4, 0, .2, 1);--animate-spin:spin 1s linear infinite;--animate-ping:ping 1s cubic-bezier(0, 0, .2, 1) infinite;--animate-pulse:pulse 2s cubic-bezier(.4, 0, .6, 1) infinite;--animate-bounce:bounce 1s infinite;--blur-xs:4px;--blur-sm:8px;--blur-md:12px;--blur-lg:16px;--blur-xl:24px;--blur-2xl:40px;--blur-3xl:64px;--perspective-dramatic:100px;--perspective-near:300px;--perspective-normal:500px;--perspective-midrange:800px;--perspective-distant:1200px;--aspect-video:16 / 9;--default-transition-duration:.15s;--default-transition-timing-function:cubic-bezier(.4, 0, .2, 1);--default-font-family:var(--font-sans);--default-mono-font-family:var(--font-mono)}}@layer base{*,:after,:before,::backdrop{box-sizing:border-box;border:0 solid;margin:0;padding:0}::file-selector-button{box-sizing:border-box;border:0 solid;margin:0;padding:0}html,:host{-webkit-text-size-adjust:100%;tab-size:4;line-height:1.5;font-family:var(--default-font-family,ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji");font-feature-settings:var(--default-font-feature-settings,normal);font-variation-settings:var(--default-font-variation-settings,normal);-webkit-tap-highlight-color:transparent}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;-webkit-text-decoration:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,samp,pre{font-family:var(--default-mono-font-family,ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);font-feature-settings:var(--default-mono-font-feature-settings,normal);font-variation-settings:var(--default-mono-font-variation-settings,normal);font-size:1em}small{font-size:80%}sub,sup{vertical-align:baseline;font-size:75%;line-height:0;position:relative}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}:-moz-focusring{outline:auto}progress{vertical-align:baseline}summary{display:list-item}ol,ul,menu{list-style:none}img,svg,video,canvas,audio,iframe,embed,object{vertical-align:middle;display:block}img,video{max-width:100%;height:auto}button,input,select,optgroup,textarea{font:inherit;font-feature-settings:inherit;font-variation-settings:inherit;letter-spacing:inherit;color:inherit;opacity:1;background-color:#0000;border-radius:0}::file-selector-button{font:inherit;font-feature-settings:inherit;font-variation-settings:inherit;letter-spacing:inherit;color:inherit;opacity:1;background-color:#0000;border-radius:0}:where(select:is([multiple],[size])) optgroup{font-weight:bolder}:where(select:is([multiple],[size])) optgroup option{padding-inline-start:20px}::file-selector-button{margin-inline-end:4px}::placeholder{opacity:1}@supports (not ((-webkit-appearance:-apple-pay-button))) or (contain-intrinsic-size:1px){::placeholder{color:currentColor}@supports (color:color-mix(in lab,red,red)){::placeholder{color:color-mix(in oklab,currentcolor 50%,transparent)}}}textarea{resize:vertical}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-date-and-time-value{min-height:1lh;text-align:inherit}::-webkit-datetime-edit{display:inline-flex}::-webkit-datetime-edit-fields-wrapper{padding:0}::-webkit-datetime-edit{padding-block:0}::-webkit-datetime-edit-year-field{padding-block:0}::-webkit-datetime-edit-month-field{padding-block:0}::-webkit-datetime-edit-day-field{padding-block:0}::-webkit-datetime-edit-hour-field{padding-block:0}::-webkit-datetime-edit-minute-field{padding-block:0}::-webkit-datetime-edit-second-field{padding-block:0}::-webkit-datetime-edit-millisecond-field{padding-block:0}::-webkit-datetime-edit-meridiem-field{padding-block:0}::-webkit-calendar-picker-indicator{line-height:1}:-moz-ui-invalid{box-shadow:none}button,input:where([type=button],[type=reset],[type=submit]){appearance:button}::file-selector-button{appearance:button}::-webkit-inner-spin-button{height:auto}::-webkit-outer-spin-button{height:auto}[hidden]:where(:not([hidden=until-found])){display:none!important}}@layer components;@layer utilities{.absolute{position:absolute}.fixed{position:fixed}.relative{position:relative}.static{position:static}.inset-0{inset:calc(var(--spacing) * 0)}.start{inset-inline-start:var(--spacing)}.top-0{top:calc(var(--spacing) * 0)}.right-0{right:calc(var(--spacing) * 0)}.container{width:100%}@media(min-width:40rem){.container{max-width:40rem}}@media(min-width:48rem){.container{max-width:48rem}}@media(min-width:64rem){.container{max-width:64rem}}@media(min-width:80rem){.container{max-width:80rem}}@media(min-width:96rem){.container{max-width:96rem}}.mx-auto{margin-inline:auto}.-mt-\[6\.6rem\]{margin-top:-6.6rem}.-mt-px{margin-top:-1px}.mt-2{margin-top:calc(var(--spacing) * 2)}.mt-4{margin-top:calc(var(--spacing) * 4)}.mt-6{margin-top:calc(var(--spacing) * 6)}.mt-8{margin-top:calc(var(--spacing) * 8)}.mr-2{margin-right:calc(var(--spacing) * 2)}.-mb-px{margin-bottom:-1px}.mb-1{margin-bottom:calc(var(--spacing) * 1)}.mb-2{margin-bottom:calc(var(--spacing) * 2)}.mb-4{margin-bottom:calc(var(--spacing) * 4)}.mb-6{margin-bottom:calc(var(--spacing) * 6)}.-ml-8{margin-left:calc(var(--spacing) * -8)}.-ml-px{margin-left:-1px}.ml-1{margin-left:calc(var(--spacing) * 1)}.ml-2{margin-left:calc(var(--spacing) * 2)}.ml-4{margin-left:calc(var(--spacing) * 4)}.ml-12{margin-left:calc(var(--spacing) * 12)}.contents{display:contents}.flex{display:flex}.grid{display:grid}.hidden{display:none}.inline-block{display:inline-block}.inline-flex{display:inline-flex}.table{display:table}.aspect-\[335\/364\]{aspect-ratio:335/364}.h-1{height:calc(var(--spacing) * 1)}.h-1\.5{height:calc(var(--spacing) * 1.5)}.h-2{height:calc(var(--spacing) * 2)}.h-2\.5{height:calc(var(--spacing) * 2.5)}.h-3{height:calc(var(--spacing) * 3)}.h-3\.5{height:calc(var(--spacing) * 3.5)}.h-5{height:calc(var(--spacing) * 5)}.h-8{height:calc(var(--spacing) * 8)}.h-14{height:calc(var(--spacing) * 14)}.h-14\.5{height:calc(var(--spacing) * 14.5)}.h-16{height:calc(var(--spacing) * 16)}.min-h-screen{min-height:100vh}.w-1{width:calc(var(--spacing) * 1)}.w-1\.5{width:calc(var(--spacing) * 1.5)}.w-2{width:calc(var(--spacing) * 2)}.w-2\.5{width:calc(var(--spacing) * 2.5)}.w-3{width:calc(var(--spacing) * 3)}.w-3\.5{width:calc(var(--spacing) * 3.5)}.w-5{width:calc(var(--spacing) * 5)}.w-8{width:calc(var(--spacing) * 8)}.w-\[438px\]{width:438px}.w-auto{width:auto}.w-full{width:100%}.max-w-6xl{max-width:var(--container-6xl)}.max-w-\[335px\]{max-width:335px}.max-w-none{max-width:none}.max-w-xl{max-width:var(--container-xl)}.flex-1{flex:1}.shrink-0{flex-shrink:0}.translate-y-0{--tw-translate-y:calc(var(--spacing) * 0);translate:var(--tw-translate-x) var(--tw-translate-y)}.transform{transform:var(--tw-rotate-x,) var(--tw-rotate-y,) var(--tw-rotate-z,) var(--tw-skew-x,) var(--tw-skew-y,)}.cursor-default{cursor:default}.cursor-not-allowed{cursor:not-allowed}.grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}.flex-col{flex-direction:column}.flex-col-reverse{flex-direction:column-reverse}.items-center{align-items:center}.justify-between{justify-content:space-between}.justify-center{justify-content:center}.justify-end{justify-content:flex-end}.justify-items-center{justify-items:center}.gap-2{gap:calc(var(--spacing) * 2)}.gap-3{gap:calc(var(--spacing) * 3)}.gap-4{gap:calc(var(--spacing) * 4)}:where(.space-x-1>:not(:last-child)){--tw-space-x-reverse:0;margin-inline-start:calc(calc(var(--spacing) * 1) * var(--tw-space-x-reverse));margin-inline-end:calc(calc(var(--spacing) * 1) * calc(1 - var(--tw-space-x-reverse)))}.overflow-hidden{overflow:hidden}.rounded-full{border-radius:3.40282e38px}.rounded-md{border-radius:var(--radius-md)}.rounded-sm{border-radius:var(--radius-sm)}.rounded-t-lg{border-top-left-radius:var(--radius-lg);border-top-right-radius:var(--radius-lg)}.rounded-l-md{border-top-left-radius:var(--radius-md);border-bottom-left-radius:var(--radius-md)}.rounded-r-md{border-top-right-radius:var(--radius-md);border-bottom-right-radius:var(--radius-md)}.rounded-br-lg{border-bottom-right-radius:var(--radius-lg)}.rounded-bl-lg{border-bottom-left-radius:var(--radius-lg)}.border{border-style:var(--tw-border-style);border-width:1px}.border-t{border-top-style:var(--tw-border-style);border-top-width:1px}.border-r{border-right-style:var(--tw-border-style);border-right-width:1px}.border-\[\#19140035\]{border-color:#19140035}.border-\[\#e3e3e0\]{border-color:#e3e3e0}.border-black{border-color:var(--color-black)}.border-gray-200{border-color:var(--color-gray-200)}.border-gray-300{border-color:var(--color-gray-300)}.border-gray-400{border-color:var(--color-gray-400)}.border-transparent{border-color:#0000}.bg-\[\#1b1b18\]{background-color:#1b1b18}.bg-\[\#FDFDFC\]{background-color:#fdfdfc}.bg-\[\#dbdbd7\]{background-color:#dbdbd7}.bg-\[\#fff2f2\]{background-color:#fff2f2}.bg-gray-100{background-color:var(--color-gray-100)}.bg-gray-200{background-color:var(--color-gray-200)}.bg-white{background-color:var(--color-white)}.p-6{padding:calc(var(--spacing) * 6)}.px-2{padding-inline:calc(var(--spacing) * 2)}.px-4{padding-inline:calc(var(--spacing) * 4)}.px-5{padding-inline:calc(var(--spacing) * 5)}.px-6{padding-inline:calc(var(--spacing) * 6)}.py-1{padding-block:calc(var(--spacing) * 1)}.py-1\.5{padding-block:calc(var(--spacing) * 1.5)}.py-2{padding-block:calc(var(--spacing) * 2)}.py-4{padding-block:calc(var(--spacing) * 4)}.pt-8{padding-top:calc(var(--spacing) * 8)}.pb-6{padding-bottom:calc(var(--spacing) * 6)}.pb-12{padding-bottom:calc(var(--spacing) * 12)}.text-center{text-align:center}.text-lg{font-size:var(--text-lg);line-height:var(--tw-leading,var(--text-lg--line-height))}.text-sm{font-size:var(--text-sm);line-height:var(--tw-leading,var(--text-sm--line-height))}.text-\[13px\]{font-size:13px}.leading-5{--tw-leading:calc(var(--spacing) * 5);line-height:calc(var(--spacing) * 5)}.leading-7{--tw-leading:calc(var(--spacing) * 7);line-height:calc(var(--spacing) * 7)}.leading-\[20px\]{--tw-leading:20px;line-height:20px}.leading-normal{--tw-leading:var(--leading-normal);line-height:var(--leading-normal)}.font-medium{--tw-font-weight:var(--font-weight-medium);font-weight:var(--font-weight-medium)}.font-semibold{--tw-font-weight:var(--font-weight-semibold);font-weight:var(--font-weight-semibold)}.tracking-wider{--tw-tracking:var(--tracking-wider);letter-spacing:var(--tracking-wider)}.text-\[\#1B1B18\],.text-\[\#1b1b18\]{color:#1b1b18}.text-\[\#706f6c\]{color:#706f6c}.text-\[\#F3BEC7\]{color:#f3bec7}.text-\[\#F8B803\]{color:#f8b803}.text-\[\#F53003\],.text-\[\#f53003\]{color:#f53003}.text-gray-200{color:var(--color-gray-200)}.text-gray-300{color:var(--color-gray-300)}.text-gray-400{color:var(--color-gray-400)}.text-gray-500{color:var(--color-gray-500)}.text-gray-600{color:var(--color-gray-600)}.text-gray-700{color:var(--color-gray-700)}.text-gray-800{color:var(--color-gray-800)}.text-gray-900{color:var(--color-gray-900)}.text-white{color:var(--color-white)}.uppercase{text-transform:uppercase}.underline{text-decoration-line:underline}.underline-offset-4{text-underline-offset:4px}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.opacity-100{opacity:1}.mix-blend-color{mix-blend-mode:color}.mix-blend-darken{mix-blend-mode:darken}.mix-blend-hard-light{mix-blend-mode:hard-light}.mix-blend-multiply{mix-blend-mode:multiply}.shadow{--tw-shadow:0 1px 3px 0 var(--tw-shadow-color,#0000001a), 0 1px 2px -1px var(--tw-shadow-color,#0000001a);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.shadow-\[0px_0px_1px_0px_rgba\(0\,0\,0\,0\.03\)\,0px_1px_2px_0px_rgba\(0\,0\,0\,0\.06\)\]{--tw-shadow:0px 0px 1px 0px var(--tw-shadow-color,#00000008), 0px 1px 2px 0px var(--tw-shadow-color,#0000000f);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.shadow-\[inset_0px_0px_0px_1px_rgba\(26\,26\,0\,0\.16\)\]{--tw-shadow:inset 0px 0px 0px 1px var(--tw-shadow-color,#1a1a0029);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.shadow-sm{--tw-shadow:0 1px 3px 0 var(--tw-shadow-color,#0000001a), 0 1px 2px -1px var(--tw-shadow-color,#0000001a);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.ring-gray-300{--tw-ring-color:var(--color-gray-300)}.filter{filter:var(--tw-blur,) var(--tw-brightness,) var(--tw-contrast,) var(--tw-grayscale,) var(--tw-hue-rotate,) var(--tw-invert,) var(--tw-saturate,) var(--tw-sepia,) var(--tw-drop-shadow,)}.transition{transition-property:color,background-color,border-color,outline-color,text-decoration-color,fill,stroke,--tw-gradient-from,--tw-gradient-via,--tw-gradient-to,opacity,box-shadow,transform,translate,scale,rotate,filter,-webkit-backdrop-filter,backdrop-filter,display,content-visibility,overlay,pointer-events;transition-timing-function:var(--tw-ease,var(--default-transition-timing-function));transition-duration:var(--tw-duration,var(--default-transition-duration))}.transition-all{transition-property:all;transition-timing-function:var(--tw-ease,var(--default-transition-timing-function));transition-duration:var(--tw-duration,var(--default-transition-duration))}.transition-opacity{transition-property:opacity;transition-timing-function:var(--tw-ease,var(--default-transition-timing-function));transition-duration:var(--tw-duration,var(--default-transition-duration))}.delay-200{transition-delay:.2s}.delay-300{transition-delay:.3s}.delay-400{transition-delay:.4s}.duration-150{--tw-duration:.15s;transition-duration:.15s}.duration-750{--tw-duration:.75s;transition-duration:.75s}.ease-in-out{--tw-ease:var(--ease-in-out);transition-timing-function:var(--ease-in-out)}.\[--stroke-color\:\#1B1B18\]{--stroke-color:#1b1b18}.not-has-\[nav\]\:hidden:not(:has(:is(nav))){display:none}.before\:absolute:before{content:var(--tw-content);position:absolute}.before\:top-0:before{content:var(--tw-content);top:calc(var(--spacing) * 0)}.before\:top-1\/2:before{content:var(--tw-content);top:50%}.before\:bottom-0:before{content:var(--tw-content);bottom:calc(var(--spacing) * 0)}.before\:bottom-1\/2:before{content:var(--tw-content);bottom:50%}.before\:left-\[0\.4rem\]:before{content:var(--tw-content);left:.4rem}.before\:border-l:before{content:var(--tw-content);border-left-style:var(--tw-border-style);border-left-width:1px}.before\:border-\[\#e3e3e0\]:before{content:var(--tw-content);border-color:#e3e3e0}@media(hover:hover){.hover\:border-\[\#1915014a\]:hover{border-color:#1915014a}.hover\:border-\[\#19140035\]:hover{border-color:#19140035}.hover\:border-black:hover{border-color:var(--color-black)}.hover\:bg-black:hover{background-color:var(--color-black)}.hover\:bg-gray-100:hover{background-color:var(--color-gray-100)}.hover\:text-gray-400:hover{color:var(--color-gray-400)}.hover\:text-gray-700:hover{color:var(--color-gray-700)}}.focus\:border-blue-300:focus{border-color:var(--color-blue-300)}.focus\:ring:focus{--tw-ring-shadow:var(--tw-ring-inset,) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color,currentcolor);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.focus\:outline-none:focus{--tw-outline-style:none;outline-style:none}.active\:bg-gray-100:active{background-color:var(--color-gray-100)}.active\:text-gray-500:active{color:var(--color-gray-500)}.active\:text-gray-700:active{color:var(--color-gray-700)}.active\:text-gray-800:active{color:var(--color-gray-800)}@media(min-width:40rem){.sm\:flex{display:flex}.sm\:hidden{display:none}.sm\:flex-1{flex:1}.sm\:items-center{align-items:center}.sm\:justify-between{justify-content:space-between}.sm\:justify-start{justify-content:flex-start}.sm\:gap-2{gap:calc(var(--spacing) * 2)}.sm\:px-6{padding-inline:calc(var(--spacing) * 6)}.sm\:pt-0{padding-top:calc(var(--spacing) * 0)}}@media(min-width:64rem){.lg\:mt-10{margin-top:calc(var(--spacing) * 10)}.lg\:mb-0{margin-bottom:calc(var(--spacing) * 0)}.lg\:mb-6{margin-bottom:calc(var(--spacing) * 6)}.lg\:-ml-px{margin-left:-1px}.lg\:ml-0{margin-left:calc(var(--spacing) * 0)}.lg\:block{display:block}.lg\:aspect-auto{aspect-ratio:auto}.lg\:w-\[438px\]{width:438px}.lg\:max-w-4xl{max-width:var(--container-4xl)}.lg\:grow{flex-grow:1}.lg\:flex-row{flex-direction:row}.lg\:justify-center{justify-content:center}.lg\:rounded-t-none{border-top-left-radius:0;border-top-right-radius:0}.lg\:rounded-tl-lg{border-top-left-radius:var(--radius-lg)}.lg\:rounded-r-lg{border-top-right-radius:var(--radius-lg);border-bottom-right-radius:var(--radius-lg)}.lg\:rounded-br-none{border-bottom-right-radius:0}.lg\:p-8{padding:calc(var(--spacing) * 8)}.lg\:p-20{padding:calc(var(--spacing) * 20)}.lg\:px-8{padding-inline:calc(var(--spacing) * 8)}.lg\:pb-10{padding-bottom:calc(var(--spacing) * 10)}}.rtl\:flex-row-reverse:where(:dir(rtl),[dir=rtl],[dir=rtl] *){flex-direction:row-reverse}@media(prefers-color-scheme:dark){.dark\:border-\[\#3E3E3A\]{border-color:#3e3e3a}.dark\:border-\[\#eeeeec\]{border-color:#eeeeec}.dark\:border-gray-600{border-color:var(--color-gray-600)}.dark\:bg-\[\#0a0a0a\]{background-color:#0a0a0a}.dark\:bg-\[\#1D0002\]{background-color:#1d0002}.dark\:bg-\[\#3E3E3A\]{background-color:#3e3e3a}.dark\:bg-\[\#161615\]{background-color:#161615}.dark\:bg-\[\#eeeeec\]{background-color:#eeeeec}.dark\:bg-gray-700{background-color:var(--color-gray-700)}.dark\:bg-gray-800{background-color:var(--color-gray-800)}.dark\:bg-gray-900{background-color:var(--color-gray-900)}.dark\:text-\[\#1C1C1A\]{color:#1c1c1a}.dark\:text-\[\#4B0600\]{color:#4b0600}.dark\:text-\[\#391800\]{color:#391800}.dark\:text-\[\#733000\]{color:#733000}.dark\:text-\[\#A1A09A\]{color:#a1a09a}.dark\:text-\[\#EDEDEC\]{color:#ededec}.dark\:text-\[\#F61500\]{color:#f61500}.dark\:text-\[\#FF4433\]{color:#f43}.dark\:text-black{color:var(--color-black)}.dark\:text-gray-200{color:var(--color-gray-200)}.dark\:text-gray-300{color:var(--color-gray-300)}.dark\:text-gray-400{color:var(--color-gray-400)}.dark\:text-gray-600{color:var(--color-gray-600)}.dark\:mix-blend-hard-light{mix-blend-mode:hard-light}.dark\:mix-blend-normal{mix-blend-mode:normal}.dark\:shadow-\[inset_0px_0px_0px_1px_\#fffaed2d\]{--tw-shadow:inset 0px 0px 0px 1px var(--tw-shadow-color,#fffaed2d);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.dark\:\[--stroke-color\:\#FF750F\]{--stroke-color:#ff750f}.dark\:before\:border-\[\#3E3E3A\]:before{content:var(--tw-content);border-color:#3e3e3a}@media(hover:hover){.dark\:hover\:border-\[\#3E3E3A\]:hover{border-color:#3e3e3a}.dark\:hover\:border-\[\#62605b\]:hover{border-color:#62605b}.dark\:hover\:border-white:hover{border-color:var(--color-white)}.dark\:hover\:bg-gray-900:hover{background-color:var(--color-gray-900)}.dark\:hover\:bg-white:hover{background-color:var(--color-white)}.dark\:hover\:text-gray-200:hover{color:var(--color-gray-200)}.dark\:hover\:text-gray-300:hover{color:var(--color-gray-300)}}.dark\:focus\:border-blue-700:focus{border-color:var(--color-blue-700)}.dark\:focus\:border-blue-800:focus{border-color:var(--color-blue-800)}.dark\:active\:bg-gray-700:active{background-color:var(--color-gray-700)}.dark\:active\:text-gray-300:active{color:var(--color-gray-300)}}@starting-style{.starting\:opacity-0{opacity:0}}@media(prefers-reduced-motion:no-preference){@starting-style{.motion-safe\:starting\:-translate-x-\[26px\]{--tw-translate-x: -26px ;translate:var(--tw-translate-x) var(--tw-translate-y)}}@starting-style{.motion-safe\:starting\:-translate-x-\[51px\]{--tw-translate-x: -51px ;translate:var(--tw-translate-x) var(--tw-translate-y)}}@starting-style{.motion-safe\:starting\:-translate-x-\[78px\]{--tw-translate-x: -78px ;translate:var(--tw-translate-x) var(--tw-translate-y)}}@starting-style{.motion-safe\:starting\:-translate-x-\[102px\]{--tw-translate-x: -102px ;translate:var(--tw-translate-x) var(--tw-translate-y)}}@starting-style{.motion-safe\:starting\:translate-y-6{--tw-translate-y:calc(var(--spacing) * 6);translate:var(--tw-translate-x) var(--tw-translate-y)}}}}@property --tw-translate-x{syntax:"*";inherits:false;initial-value:0}@property --tw-translate-y{syntax:"*";inherits:false;initial-value:0}@property --tw-translate-z{syntax:"*";inherits:false;initial-value:0}@property --tw-rotate-x{syntax:"*";inherits:false}@property --tw-rotate-y{syntax:"*";inherits:false}@property --tw-rotate-z{syntax:"*";inherits:false}@property --tw-skew-x{syntax:"*";inherits:false}@property --tw-skew-y{syntax:"*";inherits:false}@property --tw-space-x-reverse{syntax:"*";inherits:false;initial-value:0}@property --tw-border-style{syntax:"*";inherits:false;initial-value:solid}@property --tw-leading{syntax:"*";inherits:false}@property --tw-font-weight{syntax:"*";inherits:false}@property --tw-tracking{syntax:"*";inherits:false}@property --tw-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-shadow-color{syntax:"*";inherits:false}@property --tw-shadow-alpha{syntax:"<percentage>";inherits:false;initial-value:100%}@property --tw-inset-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-inset-shadow-color{syntax:"*";inherits:false}@property --tw-inset-shadow-alpha{syntax:"<percentage>";inherits:false;initial-value:100%}@property --tw-ring-color{syntax:"*";inherits:false}@property --tw-ring-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-inset-ring-color{syntax:"*";inherits:false}@property --tw-inset-ring-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-ring-inset{syntax:"*";inherits:false}@property --tw-ring-offset-width{syntax:"<length>";inherits:false;initial-value:0}@property --tw-ring-offset-color{syntax:"*";inherits:false;initial-value:#fff}@property --tw-ring-offset-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-blur{syntax:"*";inherits:false}@property --tw-brightness{syntax:"*";inherits:false}@property --tw-contrast{syntax:"*";inherits:false}@property --tw-grayscale{syntax:"*";inherits:false}@property --tw-hue-rotate{syntax:"*";inherits:false}@property --tw-invert{syntax:"*";inherits:false}@property --tw-opacity{syntax:"*";inherits:false}@property --tw-saturate{syntax:"*";inherits:false}@property --tw-sepia{syntax:"*";inherits:false}@property --tw-drop-shadow{syntax:"*";inherits:false}@property --tw-drop-shadow-color{syntax:"*";inherits:false}@property --tw-drop-shadow-alpha{syntax:"<percentage>";inherits:false;initial-value:100%}@property --tw-drop-shadow-size{syntax:"*";inherits:false}@property --tw-duration{syntax:"*";inherits:false}@property --tw-ease{syntax:"*";inherits:false}@property --tw-content{syntax:"*";inherits:false;initial-value:""}@keyframes spin{to{transform:rotate(360deg)}}@keyframes ping{75%,to{opacity:0;transform:scale(2)}}@keyframes pulse{50%{opacity:.5}}@keyframes bounce{0%,to{animation-timing-function:cubic-bezier(.8,0,1,1);transform:translateY(-25%)}50%{animation-timing-function:cubic-bezier(0,0,.2,1);transform:none}}
            </style>
        @endif
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                        >
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>
        <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
            <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row">
                <div class="text-[13px] leading-[20px] flex-1 p-6 pb-6 lg:p-20 lg:pb-10 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-bl-lg rounded-br-lg lg:rounded-tl-lg lg:rounded-br-none">
                    <h1 class="mb-1 font-medium">Let's get started</h1>
                    <p class="mb-2 text-[#706f6c] dark:text-[#A1A09A]">With so many options available to you,<br /> we suggest you start with the following:</p>
                    <ul class="flex flex-col mb-4 lg:mb-6">
                        <li class="flex items-center gap-4 py-2 relative before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A] before:top-1/2 before:bottom-0 before:left-[0.4rem] before:absolute">
                            <span class="relative py-1 bg-white dark:bg-[#161615]">
                                <span class="flex items-center justify-center rounded-full bg-[#FDFDFC] dark:bg-[#161615] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] w-3.5 h-3.5 border dark:border-[#3E3E3A] border-[#e3e3e0]">
                                    <span class="rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A] w-1.5 h-1.5"></span>
                                </span>
                            </span>
                            <span>
                                Read the
                                <a href="https://laravel.com/docs" target="_blank" class="inline-flex items-center space-x-1 font-medium underline underline-offset-4 text-[#f53003] dark:text-[#FF4433] ml-1">
                                    <span>Documentation</span>
                                    <svg
                                        width="10"
                                        height="11"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-2.5 h-2.5"
                                    >
                                        <path
                                            d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                            stroke="currentColor"
                                            stroke-linecap="square"
                                        />
                                    </svg>
                                </a>
                            </span>
                        </li>
                        <li class="flex items-center gap-4 py-2 relative before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A] before:bottom-1/2 before:top-0 before:left-[0.4rem] before:absolute">
                            <span class="relative py-1 bg-white dark:bg-[#161615]">
                                <span class="flex items-center justify-center rounded-full bg-[#FDFDFC] dark:bg-[#161615] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] w-3.5 h-3.5 border dark:border-[#3E3E3A] border-[#e3e3e0]">
                                    <span class="rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A] w-1.5 h-1.5"></span>
                                </span>
                            </span>
                            <span>
                                Watch video tutorials at
                                <a href="https://laracasts.com" target="_blank" class="inline-flex items-center space-x-1 font-medium underline underline-offset-4 text-[#f53003] dark:text-[#FF4433] ml-1">
                                    <span>Laracasts</span>
                                    <svg
                                        width="10"
                                        height="11"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-2.5 h-2.5"
                                    >
                                        <path
                                            d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                            stroke="currentColor"
                                            stroke-linecap="square"
                                        />
                                    </svg>
                                </a>
                            </span>
                        </li>
                    </ul>
                    <ul class="flex gap-3 text-sm leading-normal">
                        <li>
                            <a href="https://cloud.laravel.com" target="_blank" class="inline-block dark:bg-[#eeeeec] dark:border-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white dark:hover:border-white hover:bg-black hover:border-black px-5 py-1.5 bg-[#1b1b18] rounded-sm border border-black text-white text-sm leading-normal">
                                Deploy now
                            </a>
                        </li>
                    </ul>

                    <p class="mt-6 lg:mt-10 text-[#706f6c] dark:text-[#A1A09A]">
                        v{{ app()->version() }}
                        <a href="https://github.com/laravel/framework/blob/13.x/CHANGELOG.md" target="_blank" class="inline-flex items-center space-x-1 font-medium underline underline-offset-4 text-[#f53003] dark:text-[#FF4433] ml-1">
                            <span>View changelog</span>
                            <svg
                                width="10"
                                height="11"
                                viewBox="0 0 10 11"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-2.5 h-2.5"
                            >
                                <path
                                    d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                    stroke="currentColor"
                                    stroke-linecap="square"
                                />
                            </svg>
                        </a>
                    </p>
                </div>
                <div class="bg-[#fff2f2] dark:bg-[#1D0002] relative lg:-ml-px -mb-px lg:mb-0 rounded-t-lg lg:rounded-t-none lg:rounded-r-lg aspect-[335/364] lg:aspect-auto w-full lg:w-[438px] shrink-0 overflow-hidden">
                    {{-- Laravel Logo --}}
                    <svg class="w-full text-[#F53003] dark:text-[#F61500] transition-all translate-y-0 opacity-100 max-w-none duration-750 starting:opacity-0 motion-safe:starting:translate-y-6" viewBox="0 0 438 104" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.2036 -3H0V102.197H49.5189V86.7187H17.2036V-3Z" fill="currentColor" />
                        <path d="M110.256 41.6337C108.061 38.1275 104.945 35.3731 100.905 33.3681C96.8667 31.3647 92.8016 30.3618 88.7131 30.3618C83.4247 30.3618 78.5885 31.3389 74.201 33.2923C69.8111 35.2456 66.0474 37.928 62.9059 41.3333C59.7643 44.7401 57.3198 48.6726 55.5754 53.1293C53.8287 57.589 52.9572 62.274 52.9572 67.1813C52.9572 72.1925 53.8287 76.8995 55.5754 81.3069C57.3191 85.7173 59.7636 89.6241 62.9059 93.0293C66.0474 96.4361 69.8119 99.1155 74.201 101.069C78.5885 103.022 83.4247 103.999 88.7131 103.999C92.8016 103.999 96.8667 102.997 100.905 100.994C104.945 98.9911 108.061 96.2359 110.256 92.7282V102.195H126.563V32.1642H110.256V41.6337ZM108.76 75.7472C107.762 78.4531 106.366 80.8078 104.572 82.8112C102.776 84.8161 100.606 86.4183 98.0637 87.6206C95.5202 88.823 92.7004 89.4238 89.6103 89.4238C86.5178 89.4238 83.7252 88.823 81.2324 87.6206C78.7388 86.4183 76.5949 84.8161 74.7998 82.8112C73.004 80.8078 71.6319 78.4531 70.6856 75.7472C69.7356 73.0421 69.2644 70.1868 69.2644 67.1821C69.2644 64.1758 69.7356 61.3205 70.6856 58.6154C71.6319 55.9102 73.004 53.5571 74.7998 51.5522C76.5949 49.5495 78.738 47.9451 81.2324 46.7427C83.7252 45.5404 86.5178 44.9396 89.6103 44.9396C92.7012 44.9396 95.5202 45.5404 98.0637 46.7427C100.606 47.9451 102.776 49.5487 104.572 51.5522C106.367 53.5571 107.762 55.9102 108.76 58.6154C109.756 61.3205 110.256 64.1758 110.256 67.1821C110.256 70.1868 109.756 73.0421 108.76 75.7472Z" fill="currentColor" />
                        <path d="M242.805 41.6337C240.611 38.1275 237.494 35.3731 233.455 33.3681C229.416 31.3647 225.351 30.3618 221.262 30.3618C215.974 30.3618 211.138 31.3389 206.75 33.2923C202.36 35.2456 198.597 37.928 195.455 41.3333C192.314 44.7401 189.869 48.6726 188.125 53.1293C186.378 57.589 185.507 62.274 185.507 67.1813C185.507 72.1925 186.378 76.8995 188.125 81.3069C189.868 85.7173 192.313 89.6241 195.455 93.0293C198.597 96.4361 202.361 99.1155 206.75 101.069C211.138 103.022 215.974 103.999 221.262 103.999C225.351 103.999 229.416 102.997 233.455 100.994C237.494 98.9911 240.611 96.2359 242.805 92.7282V102.195H259.112V32.1642H242.805V41.6337ZM241.31 75.7472C240.312 78.4531 238.916 80.8078 237.122 82.8112C235.326 84.8161 233.156 86.4183 230.614 87.6206C228.07 88.823 225.251 89.4238 222.16 89.4238C219.068 89.4238 216.275 88.823 213.782 87.6206C211.289 86.4183 209.145 84.8161 207.35 82.8112C205.554 80.8078 204.182 78.4531 203.236 75.7472C202.286 73.0421 201.814 70.1868 201.814 67.1821C201.814 64.1758 202.286 61.3205 203.236 58.6154C204.182 55.9102 205.554 53.5571 207.35 51.5522C209.145 49.5495 211.288 47.9451 213.782 46.7427C216.275 45.5404 219.068 44.9396 222.16 44.9396C225.251 44.9396 228.07 45.5404 230.614 46.7427C233.156 47.9451 235.326 49.5487 237.122 51.5522C238.917 53.5571 240.312 55.9102 241.31 58.6154C242.306 61.3205 242.806 64.1758 242.806 67.1821C242.805 70.1868 242.305 73.0421 241.31 75.7472Z" fill="currentColor" />
                        <path d="M438 -3H421.694V102.197H438V-3Z" fill="currentColor" />
                        <path d="M139.43 102.197H155.735V48.2834H183.712V32.1665H139.43V102.197Z" fill="currentColor" />
                        <path d="M324.49 32.1665L303.995 85.794L283.498 32.1665H266.983L293.748 102.197H314.242L341.006 32.1665H324.49Z" fill="currentColor" />
                        <path d="M376.571 30.3656C356.603 30.3656 340.797 46.8497 340.797 67.1828C340.797 89.6597 356.094 104 378.661 104C391.29 104 399.354 99.1488 409.206 88.5848L398.189 80.0226C398.183 80.031 389.874 90.9895 377.468 90.9895C363.048 90.9895 356.977 79.3111 356.977 73.269H411.075C413.917 50.1328 398.775 30.3656 376.571 30.3656ZM357.02 61.0967C357.145 59.7487 359.023 43.3761 376.442 43.3761C393.861 43.3761 395.978 59.7464 396.099 61.0967H357.02Z" fill="currentColor" />
                    </svg>

                    {{-- 13 --}}
                    <svg class="w-[438px] max-w-none relative -mt-[6.6rem] -ml-8 lg:ml-0 [--stroke-color:#1B1B18] dark:[--stroke-color:#FF750F]" viewBox="0 0 440 392" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g class="mix-blend-darken dark:mix-blend-normal transition-all delay-300 opacity-100 duration-750 starting:opacity-0 text-[#1B1B18] dark:text-black">
                            <mask id="path-1-mask" maskUnits="userSpaceOnUse" x="-0.328613" y="103" width="338" height="299" fill="black">
                                <rect fill="white" x="-0.328613" y="103" width="338" height="299"/>
                                <path d="M234.936 400.8C204.136 400.8 178.936 392.4 159.336 375.6C140.136 358.8 130.536 337 130.536 310.2H200.736C200.736 318.2 203.736 324.8 209.736 330C215.736 335.2 223.736 337.8 233.736 337.8C243.336 337.8 251.136 335 257.136 329.4C263.536 323.8 266.736 316.6 266.736 307.8C266.736 299.8 263.936 293.2 258.336 288C252.736 282.8 245.536 280.2 236.736 280.2H199.536V218.4H236.736C243.536 218.4 249.336 216 254.136 211.2C258.936 206.4 261.336 200.4 261.336 193.2C261.336 184.8 258.736 178.2 253.536 173.4C248.336 168.6 241.736 166.2 233.736 166.2C226.536 166.2 220.336 168.4 215.136 172.8C210.336 177.2 207.936 182.8 207.936 189.6H141.336C141.336 164.8 150.136 144.6 167.736 129C185.336 113 207.936 105 235.536 105C263.136 105 285.536 112.2 302.736 126.6C320.336 141 329.136 160 329.136 183.6C329.136 200.8 324.536 214.8 315.336 225.6C306.136 236 294.336 243.2 279.936 247.2C297.136 252 310.736 260.2 320.736 271.8C331.136 283.4 336.336 298 336.336 315.6C336.336 340.4 326.936 360.8 308.136 376.8C289.336 392.8 264.936 400.8 234.936 400.8Z"/>
                                <path d="M26.8714 167.6H1.67139V105.2H94.6714V400.2H26.8714V167.6Z"/>
                            </mask>
                            <path d="M234.936 400.8C204.136 400.8 178.936 392.4 159.336 375.6C140.136 358.8 130.536 337 130.536 310.2H200.736C200.736 318.2 203.736 324.8 209.736 330C215.736 335.2 223.736 337.8 233.736 337.8C243.336 337.8 251.136 335 257.136 329.4C263.536 323.8 266.736 316.6 266.736 307.8C266.736 299.8 263.936 293.2 258.336 288C252.736 282.8 245.536 280.2 236.736 280.2H199.536V218.4H236.736C243.536 218.4 249.336 216 254.136 211.2C258.936 206.4 261.336 200.4 261.336 193.2C261.336 184.8 258.736 178.2 253.536 173.4C248.336 168.6 241.736 166.2 233.736 166.2C226.536 166.2 220.336 168.4 215.136 172.8C210.336 177.2 207.936 182.8 207.936 189.6H141.336C141.336 164.8 150.136 144.6 167.736 129C185.336 113 207.936 105 235.536 105C263.136 105 285.536 112.2 302.736 126.6C320.336 141 329.136 160 329.136 183.6C329.136 200.8 324.536 214.8 315.336 225.6C306.136 236 294.336 243.2 279.936 247.2C297.136 252 310.736 260.2 320.736 271.8C331.136 283.4 336.336 298 336.336 315.6C336.336 340.4 326.936 360.8 308.136 376.8C289.336 392.8 264.936 400.8 234.936 400.8Z" fill="currentColor"/>
                            <path d="M26.8714 167.6H1.67139V105.2H94.6714V400.2H26.8714V167.6Z" fill="currentColor"/>
                            <path d="M234.936 400.8C204.136 400.8 178.936 392.4 159.336 375.6C140.136 358.8 130.536 337 130.536 310.2H200.736C200.736 318.2 203.736 324.8 209.736 330C215.736 335.2 223.736 337.8 233.736 337.8C243.336 337.8 251.136 335 257.136 329.4C263.536 323.8 266.736 316.6 266.736 307.8C266.736 299.8 263.936 293.2 258.336 288C252.736 282.8 245.536 280.2 236.736 280.2H199.536V218.4H236.736C243.536 218.4 249.336 216 254.136 211.2C258.936 206.4 261.336 200.4 261.336 193.2C261.336 184.8 258.736 178.2 253.536 173.4C248.336 168.6 241.736 166.2 233.736 166.2C226.536 166.2 220.336 168.4 215.136 172.8C210.336 177.2 207.936 182.8 207.936 189.6H141.336C141.336 164.8 150.136 144.6 167.736 129C185.336 113 207.936 105 235.536 105C263.136 105 285.536 112.2 302.736 126.6C320.336 141 329.136 160 329.136 183.6C329.136 200.8 324.536 214.8 315.336 225.6C306.136 236 294.336 243.2 279.936 247.2C297.136 252 310.736 260.2 320.736 271.8C331.136 283.4 336.336 298 336.336 315.6C336.336 340.4 326.936 360.8 308.136 376.8C289.336 392.8 264.936 400.8 234.936 400.8Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-1-mask)"/>
                            <path d="M26.8714 167.6H1.67139V105.2H94.6714V400.2H26.8714V167.6Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-1-mask)"/>
                        </g>

                        <g class="transition-all delay-400 opacity-100 duration-750 starting:opacity-0 motion-safe:starting:-translate-x-[26px] text-[#F3BEC7] dark:text-[#4B0600]">
                            <mask id="path-2-mask" maskUnits="userSpaceOnUse" x="25.3357" y="103" width="338" height="299" fill="black">
                                <rect fill="white" x="25.3357" y="103" width="338" height="299"/>
                                <path d="M260.6 400.8C229.8 400.8 204.6 392.4 185 375.6C165.8 358.8 156.2 337 156.2 310.2H226.4C226.4 318.2 229.4 324.8 235.4 330C241.4 335.2 249.4 337.8 259.4 337.8C269 337.8 276.8 335 282.8 329.4C289.2 323.8 292.4 316.6 292.4 307.8C292.4 299.8 289.6 293.2 284 288C278.4 282.8 271.2 280.2 262.4 280.2H225.2V218.4H262.4C269.2 218.4 275 216 279.8 211.2C284.6 206.4 287 200.4 287 193.2C287 184.8 284.4 178.2 279.2 173.4C274 168.6 267.4 166.2 259.4 166.2C252.2 166.2 246 168.4 240.8 172.8C236 177.2 233.6 182.8 233.6 189.6H167C167 164.8 175.8 144.6 193.4 129C211 113 233.6 105 261.2 105C288.8 105 311.2 112.2 328.4 126.6C346 141 354.8 160 354.8 183.6C354.8 200.8 350.2 214.8 341 225.6C331.8 236 320 243.2 305.6 247.2C322.8 252 336.4 260.2 346.4 271.8C356.8 283.4 362 298 362 315.6C362 340.4 352.6 360.8 333.8 376.8C315 392.8 290.6 400.8 260.6 400.8Z"/>
                                <path d="M52.5357 167.6H27.3357V105.2H120.336V400.2H52.5357V167.6Z"/>
                            </mask>
                            <path d="M260.6 400.8C229.8 400.8 204.6 392.4 185 375.6C165.8 358.8 156.2 337 156.2 310.2H226.4C226.4 318.2 229.4 324.8 235.4 330C241.4 335.2 249.4 337.8 259.4 337.8C269 337.8 276.8 335 282.8 329.4C289.2 323.8 292.4 316.6 292.4 307.8C292.4 299.8 289.6 293.2 284 288C278.4 282.8 271.2 280.2 262.4 280.2H225.2V218.4H262.4C269.2 218.4 275 216 279.8 211.2C284.6 206.4 287 200.4 287 193.2C287 184.8 284.4 178.2 279.2 173.4C274 168.6 267.4 166.2 259.4 166.2C252.2 166.2 246 168.4 240.8 172.8C236 177.2 233.6 182.8 233.6 189.6H167C167 164.8 175.8 144.6 193.4 129C211 113 233.6 105 261.2 105C288.8 105 311.2 112.2 328.4 126.6C346 141 354.8 160 354.8 183.6C354.8 200.8 350.2 214.8 341 225.6C331.8 236 320 243.2 305.6 247.2C322.8 252 336.4 260.2 346.4 271.8C356.8 283.4 362 298 362 315.6C362 340.4 352.6 360.8 333.8 376.8C315 392.8 290.6 400.8 260.6 400.8Z" fill="currentColor"/>
                            <path d="M52.5357 167.6H27.3357V105.2H120.336V400.2H52.5357V167.6Z" fill="currentColor"/>
                            <path d="M260.6 400.8C229.8 400.8 204.6 392.4 185 375.6C165.8 358.8 156.2 337 156.2 310.2H226.4C226.4 318.2 229.4 324.8 235.4 330C241.4 335.2 249.4 337.8 259.4 337.8C269 337.8 276.8 335 282.8 329.4C289.2 323.8 292.4 316.6 292.4 307.8C292.4 299.8 289.6 293.2 284 288C278.4 282.8 271.2 280.2 262.4 280.2H225.2V218.4H262.4C269.2 218.4 275 216 279.8 211.2C284.6 206.4 287 200.4 287 193.2C287 184.8 284.4 178.2 279.2 173.4C274 168.6 267.4 166.2 259.4 166.2C252.2 166.2 246 168.4 240.8 172.8C236 177.2 233.6 182.8 233.6 189.6H167C167 164.8 175.8 144.6 193.4 129C211 113 233.6 105 261.2 105C288.8 105 311.2 112.2 328.4 126.6C346 141 354.8 160 354.8 183.6C354.8 200.8 350.2 214.8 341 225.6C331.8 236 320 243.2 305.6 247.2C322.8 252 336.4 260.2 346.4 271.8C356.8 283.4 362 298 362 315.6C362 340.4 352.6 360.8 333.8 376.8C315 392.8 290.6 400.8 260.6 400.8Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-2-mask)"/>
                            <path d="M52.5357 167.6H27.3357V105.2H120.336V400.2H52.5357V167.6Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-2-mask)"/>
                        </g>
                        
                        <g class="mix-blend-color dark:mix-blend-hard-light transition-all delay-400 opacity-100 duration-750 starting:opacity-0 motion-safe:starting:-translate-x-[51px] text-[#F8B803] dark:text-[#391800]">
                            <mask id="path-3-mask" maskUnits="userSpaceOnUse" x="51" y="103" width="338" height="299" fill="black">
                                <rect fill="white" x="51" y="103" width="338" height="299"/>
                                <path d="M286.264 400.8C255.464 400.8 230.264 392.4 210.664 375.6C191.464 358.8 181.864 337 181.864 310.2H252.064C252.064 318.2 255.064 324.8 261.064 330C267.064 335.2 275.064 337.8 285.064 337.8C294.664 337.8 302.464 335 308.464 329.4C314.864 323.8 318.064 316.6 318.064 307.8C318.064 299.8 315.264 293.2 309.664 288C304.064 282.8 296.864 280.2 288.064 280.2H250.864V218.4H288.064C294.864 218.4 300.664 216 305.464 211.2C310.264 206.4 312.664 200.4 312.664 193.2C312.664 184.8 310.064 178.2 304.864 173.4C299.664 168.6 293.064 166.2 285.064 166.2C277.864 166.2 271.664 168.4 266.464 172.8C261.664 177.2 259.264 182.8 259.264 189.6H192.664C192.664 164.8 201.464 144.6 219.064 129C236.664 113 259.264 105 286.864 105C314.464 105 336.864 112.2 354.064 126.6C371.664 141 380.464 160 380.464 183.6C380.464 200.8 375.864 214.8 366.664 225.6C357.464 236 345.664 243.2 331.264 247.2C348.464 252 362.064 260.2 372.064 271.8C382.464 283.4 387.664 298 387.664 315.6C387.664 340.4 378.264 360.8 359.464 376.8C340.664 392.8 316.264 400.8 286.264 400.8Z"/>
                                <path d="M78.2 167.6H53V105.2H146V400.2H78.2V167.6Z"/>
                            </mask>
                            <path d="M286.264 400.8C255.464 400.8 230.264 392.4 210.664 375.6C191.464 358.8 181.864 337 181.864 310.2H252.064C252.064 318.2 255.064 324.8 261.064 330C267.064 335.2 275.064 337.8 285.064 337.8C294.664 337.8 302.464 335 308.464 329.4C314.864 323.8 318.064 316.6 318.064 307.8C318.064 299.8 315.264 293.2 309.664 288C304.064 282.8 296.864 280.2 288.064 280.2H250.864V218.4H288.064C294.864 218.4 300.664 216 305.464 211.2C310.264 206.4 312.664 200.4 312.664 193.2C312.664 184.8 310.064 178.2 304.864 173.4C299.664 168.6 293.064 166.2 285.064 166.2C277.864 166.2 271.664 168.4 266.464 172.8C261.664 177.2 259.264 182.8 259.264 189.6H192.664C192.664 164.8 201.464 144.6 219.064 129C236.664 113 259.264 105 286.864 105C314.464 105 336.864 112.2 354.064 126.6C371.664 141 380.464 160 380.464 183.6C380.464 200.8 375.864 214.8 366.664 225.6C357.464 236 345.664 243.2 331.264 247.2C348.464 252 362.064 260.2 372.064 271.8C382.464 283.4 387.664 298 387.664 315.6C387.664 340.4 378.264 360.8 359.464 376.8C340.664 392.8 316.264 400.8 286.264 400.8Z" fill="currentColor"/>
                            <path d="M78.2 167.6H53V105.2H146V400.2H78.2V167.6Z" fill="currentColor"/>
                            <path d="M286.264 400.8C255.464 400.8 230.264 392.4 210.664 375.6C191.464 358.8 181.864 337 181.864 310.2H252.064C252.064 318.2 255.064 324.8 261.064 330C267.064 335.2 275.064 337.8 285.064 337.8C294.664 337.8 302.464 335 308.464 329.4C314.864 323.8 318.064 316.6 318.064 307.8C318.064 299.8 315.264 293.2 309.664 288C304.064 282.8 296.864 280.2 288.064 280.2H250.864V218.4H288.064C294.864 218.4 300.664 216 305.464 211.2C310.264 206.4 312.664 200.4 312.664 193.2C312.664 184.8 310.064 178.2 304.864 173.4C299.664 168.6 293.064 166.2 285.064 166.2C277.864 166.2 271.664 168.4 266.464 172.8C261.664 177.2 259.264 182.8 259.264 189.6H192.664C192.664 164.8 201.464 144.6 219.064 129C236.664 113 259.264 105 286.864 105C314.464 105 336.864 112.2 354.064 126.6C371.664 141 380.464 160 380.464 183.6C380.464 200.8 375.864 214.8 366.664 225.6C357.464 236 345.664 243.2 331.264 247.2C348.464 252 362.064 260.2 372.064 271.8C382.464 283.4 387.664 298 387.664 315.6C387.664 340.4 378.264 360.8 359.464 376.8C340.664 392.8 316.264 400.8 286.264 400.8Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-3-mask)"/>
                            <path d="M78.2 167.6H53V105.2H146V400.2H78.2V167.6Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-3-mask)"/>
                        </g>
                        
                        <g class="mix-blend-multiply dark:mix-blend-normal transition-all delay-400 opacity-100 duration-750 starting:opacity-0 motion-safe:starting:-translate-x-[78px] text-[#F3BEC7] dark:text-[#733000]">
                            <mask id="path-4-mask" maskUnits="userSpaceOnUse" x="76.6643" y="103" width="338" height="299" fill="black">
                                <rect fill="white" x="76.6643" y="103" width="338" height="299"/>
                                <path d="M311.929 400.8C281.129 400.8 255.929 392.4 236.329 375.6C217.129 358.8 207.529 337 207.529 310.2H277.729C277.729 318.2 280.729 324.8 286.729 330C292.729 335.2 300.729 337.8 310.729 337.8C320.329 337.8 328.129 335 334.129 329.4C340.529 323.8 343.729 316.6 343.729 307.8C343.729 299.8 340.929 293.2 335.329 288C329.729 282.8 322.529 280.2 313.729 280.2H276.529V218.4H313.729C320.529 218.4 326.329 216 331.129 211.2C335.929 206.4 338.329 200.4 338.329 193.2C338.329 184.8 335.729 178.2 330.529 173.4C325.329 168.6 318.729 166.2 310.729 166.2C303.529 166.2 297.329 168.4 292.129 172.8C287.329 177.2 284.929 182.8 284.929 189.6H218.329C218.329 164.8 227.129 144.6 244.729 129C262.329 113 284.929 105 312.529 105C340.129 105 362.529 112.2 379.729 126.6C397.329 141 406.129 160 406.129 183.6C406.129 200.8 401.529 214.8 392.329 225.6C383.129 236 371.329 243.2 356.929 247.2C374.129 252 387.729 260.2 397.729 271.8C408.129 283.4 413.329 298 413.329 315.6C413.329 340.4 403.929 360.8 385.129 376.8C366.329 392.8 341.929 400.8 311.929 400.8Z"/>
                                <path d="M103.864 167.6H78.6643V105.2H171.664V400.2H103.864V167.6Z"/>
                            </mask>
                            <path d="M311.929 400.8C281.129 400.8 255.929 392.4 236.329 375.6C217.129 358.8 207.529 337 207.529 310.2H277.729C277.729 318.2 280.729 324.8 286.729 330C292.729 335.2 300.729 337.8 310.729 337.8C320.329 337.8 328.129 335 334.129 329.4C340.529 323.8 343.729 316.6 343.729 307.8C343.729 299.8 340.929 293.2 335.329 288C329.729 282.8 322.529 280.2 313.729 280.2H276.529V218.4H313.729C320.529 218.4 326.329 216 331.129 211.2C335.929 206.4 338.329 200.4 338.329 193.2C338.329 184.8 335.729 178.2 330.529 173.4C325.329 168.6 318.729 166.2 310.729 166.2C303.529 166.2 297.329 168.4 292.129 172.8C287.329 177.2 284.929 182.8 284.929 189.6H218.329C218.329 164.8 227.129 144.6 244.729 129C262.329 113 284.929 105 312.529 105C340.129 105 362.529 112.2 379.729 126.6C397.329 141 406.129 160 406.129 183.6C406.129 200.8 401.529 214.8 392.329 225.6C383.129 236 371.329 243.2 356.929 247.2C374.129 252 387.729 260.2 397.729 271.8C408.129 283.4 413.329 298 413.329 315.6C413.329 340.4 403.929 360.8 385.129 376.8C366.329 392.8 341.929 400.8 311.929 400.8Z" fill="currentColor"/>
                            <path d="M103.864 167.6H78.6643V105.2H171.664V400.2H103.864V167.6Z" fill="currentColor"/>
                            <path d="M311.929 400.8C281.129 400.8 255.929 392.4 236.329 375.6C217.129 358.8 207.529 337 207.529 310.2H277.729C277.729 318.2 280.729 324.8 286.729 330C292.729 335.2 300.729 337.8 310.729 337.8C320.329 337.8 328.129 335 334.129 329.4C340.529 323.8 343.729 316.6 343.729 307.8C343.729 299.8 340.929 293.2 335.329 288C329.729 282.8 322.529 280.2 313.729 280.2H276.529V218.4H313.729C320.529 218.4 326.329 216 331.129 211.2C335.929 206.4 338.329 200.4 338.329 193.2C338.329 184.8 335.729 178.2 330.529 173.4C325.329 168.6 318.729 166.2 310.729 166.2C303.529 166.2 297.329 168.4 292.129 172.8C287.329 177.2 284.929 182.8 284.929 189.6H218.329C218.329 164.8 227.129 144.6 244.729 129C262.329 113 284.929 105 312.529 105C340.129 105 362.529 112.2 379.729 126.6C397.329 141 406.129 160 406.129 183.6C406.129 200.8 401.529 214.8 392.329 225.6C383.129 236 371.329 243.2 356.929 247.2C374.129 252 387.729 260.2 397.729 271.8C408.129 283.4 413.329 298 413.329 315.6C413.329 340.4 403.929 360.8 385.129 376.8C366.329 392.8 341.929 400.8 311.929 400.8Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-4-mask)"/>
                            <path d="M103.864 167.6H78.6643V105.2H171.664V400.2H103.864V167.6Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-4-mask)"/>
                        </g>
                        
                        <g class="mix-blend-hard-light transition-all delay-400 opacity-100 duration-750 starting:opacity-0 motion-safe:starting:-translate-x-[102px] text-[#F3BEC7] dark:text-[#4B0600]">
                            <mask id="path-5-mask" maskUnits="userSpaceOnUse" x="102.329" y="103" width="338" height="299" fill="black">
                                <rect fill="white" x="102.329" y="103" width="338" height="299"/>
                                <path d="M337.593 400.8C306.793 400.8 281.593 392.4 261.993 375.6C242.793 358.8 233.193 337 233.193 310.2H303.393C303.393 318.2 306.393 324.8 312.393 330C318.393 335.2 326.393 337.8 336.393 337.8C345.993 337.8 353.793 335 359.793 329.4C366.193 323.8 369.393 316.6 369.393 307.8C369.393 299.8 366.593 293.2 360.993 288C355.393 282.8 348.193 280.2 339.393 280.2H302.193V218.4H339.393C346.193 218.4 351.993 216 356.793 211.2C361.593 206.4 363.993 200.4 363.993 193.2C363.993 184.8 361.393 178.2 356.193 173.4C350.993 168.6 344.393 166.2 336.393 166.2C329.193 166.2 322.993 168.4 317.793 172.8C312.993 177.2 310.593 182.8 310.593 189.6H243.993C243.993 164.8 252.793 144.6 270.393 129C287.993 113 310.593 105 338.193 105C365.793 105 388.193 112.2 405.393 126.6C422.993 141 431.793 160 431.793 183.6C431.793 200.8 427.193 214.8 417.993 225.6C408.793 236 396.993 243.2 382.593 247.2C399.793 252 413.393 260.2 423.393 271.8C433.793 283.4 438.993 298 438.993 315.6C438.993 340.4 429.593 360.8 410.793 376.8C391.993 392.8 367.593 400.8 337.593 400.8Z"/>
                                <path d="M129.529 167.6H104.329V105.2H197.329V400.2H129.529V167.6Z"/>
                            </mask>
                            <path d="M337.593 400.8C306.793 400.8 281.593 392.4 261.993 375.6C242.793 358.8 233.193 337 233.193 310.2H303.393C303.393 318.2 306.393 324.8 312.393 330C318.393 335.2 326.393 337.8 336.393 337.8C345.993 337.8 353.793 335 359.793 329.4C366.193 323.8 369.393 316.6 369.393 307.8C369.393 299.8 366.593 293.2 360.993 288C355.393 282.8 348.193 280.2 339.393 280.2H302.193V218.4H339.393C346.193 218.4 351.993 216 356.793 211.2C361.593 206.4 363.993 200.4 363.993 193.2C363.993 184.8 361.393 178.2 356.193 173.4C350.993 168.6 344.393 166.2 336.393 166.2C329.193 166.2 322.993 168.4 317.793 172.8C312.993 177.2 310.593 182.8 310.593 189.6H243.993C243.993 164.8 252.793 144.6 270.393 129C287.993 113 310.593 105 338.193 105C365.793 105 388.193 112.2 405.393 126.6C422.993 141 431.793 160 431.793 183.6C431.793 200.8 427.193 214.8 417.993 225.6C408.793 236 396.993 243.2 382.593 247.2C399.793 252 413.393 260.2 423.393 271.8C433.793 283.4 438.993 298 438.993 315.6C438.993 340.4 429.593 360.8 410.793 376.8C391.993 392.8 367.593 400.8 337.593 400.8Z" fill="currentColor"/>
                            <path d="M129.529 167.6H104.329V105.2H197.329V400.2H129.529V167.6Z" fill="currentColor"/>
                            <path d="M337.593 400.8C306.793 400.8 281.593 392.4 261.993 375.6C242.793 358.8 233.193 337 233.193 310.2H303.393C303.393 318.2 306.393 324.8 312.393 330C318.393 335.2 326.393 337.8 336.393 337.8C345.993 337.8 353.793 335 359.793 329.4C366.193 323.8 369.393 316.6 369.393 307.8C369.393 299.8 366.593 293.2 360.993 288C355.393 282.8 348.193 280.2 339.393 280.2H302.193V218.4H339.393C346.193 218.4 351.993 216 356.793 211.2C361.593 206.4 363.993 200.4 363.993 193.2C363.993 184.8 361.393 178.2 356.193 173.4C350.993 168.6 344.393 166.2 336.393 166.2C329.193 166.2 322.993 168.4 317.793 172.8C312.993 177.2 310.593 182.8 310.593 189.6H243.993C243.993 164.8 252.793 144.6 270.393 129C287.993 113 310.593 105 338.193 105C365.793 105 388.193 112.2 405.393 126.6C422.993 141 431.793 160 431.793 183.6C431.793 200.8 427.193 214.8 417.993 225.6C408.793 236 396.993 243.2 382.593 247.2C399.793 252 413.393 260.2 423.393 271.8C433.793 283.4 438.993 298 438.993 315.6C438.993 340.4 429.593 360.8 410.793 376.8C391.993 392.8 367.593 400.8 337.593 400.8Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-5-mask)"/>
                            <path d="M129.529 167.6H104.329V105.2H197.329V400.2H129.529V167.6Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-5-mask)"/>
                        </g>
                    </svg>
                    <div class="absolute inset-0 rounded-t-lg lg:rounded-t-none lg:rounded-r-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"></div>
                </div>
            </main>
        </div>

        @if (Route::has('login'))
            <div class="h-14.5 hidden lg:block"></div>
        @endif
    </body>
</html>

```
---
