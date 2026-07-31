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
