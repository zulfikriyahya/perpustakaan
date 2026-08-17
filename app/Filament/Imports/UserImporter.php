<?php

namespace App\Filament\Imports;

use App\Enums\RoleUser;
use App\Models\KelasTahunPelajaran;
use App\Models\User;
use App\Rules\FormatKartuRfid;
use App\Rules\FormatNomorTelepon;
use App\Services\KenaikanKelasService;
use App\Services\UserImportResolverService;
use App\Support\NomorTeleponFormatter;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Cache;

/**
 * TODO: GAP-SPEC - 'role' SENGAJA tidak termasuk kolom import (dikonfirmasi:
 * harus manual lewat form demi keamanan). User baru hasil import otomatis
 * role='siswa' (default migration/kolom).
 *
 * Upsert berdasarkan 'nisn' jika ada, fallback 'nip' - baris tanpa
 * keduanya akan gagal (lihat rules 'required_without').
 *
 * REFACTOR (iterasi ini): resolusi password/avatar/kartu-RFID/KTP
 * dipindah ke UserImportResolverService (Aturan poin 3) - sebelumnya
 * logic ini terduplikasi manual di sheet 'user' MasterDataRegistry
 * dan menyebabkan bug double-hashing password di sana karena drift.
 * Kontrak kolom/rules/perilaku dari sisi pengguna TIDAK berubah.
 *
 * PERINGATAN KEAMANAN (dikonfirmasi, RISIKO DITERIMA SADAR - bukan
 * kealpaan, lihat detail lengkap di docblock
 * UserImportResolverService::resolveAvatar()): resolveAvatar bisa (a)
 * menyalin FILE APA PUN yang bisa dibaca proses PHP di server jika
 * diisi path absolut (path traversal), dan (b) melakukan HTTP request
 * ke alamat mana pun termasuk jaringan internal jika diisi URL (SSRF).
 * SENGAJA tidak dibatasi karena hanya super_admin yang punya akses
 * Import User (lihat authorize() di UserResource) - JANGAN perluas
 * permission import ini ke role lain tanpa meninjau ulang dua risiko ini.
 *
 * BUG FIX (pola sama dengan KelasImporter): kolom 'kelas_nama',
 * 'jurusan_kode', 'tahun_pelajaran_nama', 'avatar' adalah lookup/
 * transform-only (bukan kolom tabel 'users' persis nama itu) - diberi
 * ->fillRecordUsing() no-op supaya Filament tidak meng-assign atribut
 * dinamis ini ke $record, yang akan memicu SQL error "Unknown column"
 * saat save(). 'no_kartu_rfid' dan 'password' TIDAK diberi no-op -
 * keduanya kolom ASLI tabel 'users', assignment akhirnya tetap lewat
 * beforeSave() (aman dari bug ini).
 */
class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    protected ?KelasTahunPelajaran $ktpTerresolve = null;

    protected function resolver(): UserImportResolverService
    {
        return app(UserImportResolverService::class);
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Yahya Zulfikri'),
            ImportColumn::make('jenis_kelamin')
                ->label('Jenis Kelamin (L/P, opsional)')
                ->rules(['nullable', 'in:L,P'])
                ->example('L'),
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
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('jurusan_kode')
                ->label('Kode jurusan (wajib jika kelas_nama diisi)')
                ->helperText('Lihat daftar kode di menu Master Data > Jurusan.')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Non_Jurusan')
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('tahun_pelajaran_nama')
                ->label('Tahun pelajaran (wajib jika kelas_nama diisi)')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('2026/2027')
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('jabatan')
                ->rules(['nullable', 'string', 'max:255'])
                ->example(''),
            ImportColumn::make('no_telepon')
                ->label('No. Telepon')
                ->requiredMapping()
                ->helperText('Boleh format apa pun (+62, spasi, strip) - otomatis dinormalisasi jadi 628xxxxxxxxx saat disimpan.')
                ->rules(['required', 'string', 'max:255', new FormatNomorTelepon])
                ->example('081234567890'),
            ImportColumn::make('no_kartu_rfid')
                ->label('No. kartu RFID (opsional)')
                ->helperText('PERHATIAN: kosongkan HANYA jika memangingin menghapus kartu yang sudah terdaftar untuk user ini - user tidak akan bisa tap RFID lagi sampai didaftarkan ulang. Harus persis 10 digit angka.')
                ->rules(['nullable', new FormatKartuRfid])
                ->example('1234567890'),
            ImportColumn::make('password')
                ->label('Password (opsional)')
                ->helperText('Isi plaintext (otomatis di-hash saat disimpan). Kosongkan: user baru tetap dapat password random, user lamapassword TIDAK berubah.')
                ->rules(['nullable', 'string', 'min:8', 'max:255'])
                ->example(''),
            ImportColumn::make('avatar')
                ->label('Avatar - URL atau path (opsional)')
                ->helperText('Isi URL gambar (https://...) atau pathfile yang bisa diakses server. Kosongkan jika tidak ingin mengubah avatar.')
                ->rules(['nullable', 'string', 'max:2048'])
                ->example('https://contoh-sekolah.id/foto/siswa1.jpg')
                ->fillRecordUsing(fn (?string $state) => null),
        ];
    }

    public function resolveRecord(): ?User
    {
        $namaKelas = trim((string) ($this->data['kelas_nama'] ?? ''));

        if ($namaKelas !== '') {
            $this->ktpTerresolve = $this->resolver()->resolveKtp(
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

    protected function beforeSave(): void
    {
        $this->resolver()->resolvePassword($this->record, $this->data['password'] ?? null);

        $identitas = trim((string) ($this->data['nisn'] ?? '')) ?: trim((string) ($this->data['nip'] ?? ''));
        $this->resolver()->resolveAvatar($this->record, $this->data['avatar'] ?? null, $identitas);

        $kartuDihapus = $this->resolver()->resolveKartuRfid($this->record, $this->data['no_kartu_rfid'] ?? null);

        if ($kartuDihapus) {
            Cache::increment("import-{$this->import->id}-kartu-dihapus");
        }

        // dinormalisasi jadi 628xxxxxxxxx - rules() di kolom sudah memastikan
        // FormatNomorTelepon lolos, jadi normalisasi() di sini seharusnya
        // tidak pernah null, tapi tetap di-guard defensif (fallback ke nilai
        // asli) supaya import tidak fatal error jika suatu saat asumsi ini keliru.
        $nomorTernormalisasi = NomorTeleponFormatter::normalisasi($this->data['no_telepon'] ?? null);
        $this->record->no_telepon = $nomorTernormalisasi ?? $this->data['no_telepon'];
    }

    protected function afterSave(): void
    {
        if ($this->ktpTerresolve) {
            app(KenaikanKelasService::class)->assignKelas($this->record, $this->ktpTerresolve);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import User selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        $kartuDihapus = (int) Cache::get("import-{$import->id}-kartu-dihapus", 0);

        if ($kartuDihapus > 0) {
            $body .= " PERHATIAN: {$kartuDihapus} kartu RFID dihapus dari user (kolom dikosongkan di file) - user tersebut tidak bisa tap RFID sampai didaftarkan ulang.";
        }

        Cache::forget("import-{$import->id}-kartu-dihapus");

        return $body;
    }
}
