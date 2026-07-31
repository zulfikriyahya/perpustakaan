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
use Illuminate\Support\Str;

/**
 * TODO: GAP-SPEC - 'role' SENGAJA tidak termasuk kolom import (dikonfirmasi:
 * harus manual lewat form demi keamanan). User baru hasil import otomatis
 * role='siswa' (default migration/kolom).
 *
 * Upsert berdasarkan 'nisn' jika ada, fallback 'nip' - baris tanpa
 * keduanya akan gagal (lihat rules 'required_without').
 *
 * Password digenerate random - TIDAK ada mekanisme kirim WA/email
 * notifikasi password ke user baru dalam iterasi ini.
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
                ->example('VII A'),
            ImportColumn::make('jurusan_kode')
                ->label('Kode jurusan (wajib jika kelas_nama diisi)')
                ->helperText('Lihat daftar kode di menu Master Data > Jurusan.')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Non_Jurusan'),
            ImportColumn::make('tahun_pelajaran_nama')
                ->label('Tahun pelajaran (wajib jika kelas_nama diisi)')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('2025/2026'),
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
                ->helperText('PERHATIAN: kosongkan HANYA jika memang ingin menghapus kartu yang sudah terdaftar untuk user ini - user tidak akan bisa tap RFID lagi sampai didaftarkan ulang. Harus persis 10 digit angka.')
                ->rules(['nullable', new FormatKartuRfid])
                ->example('1234567890'),
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
            $record->password = Str::random(12); // di-hash otomatis via cast 'hashed'
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
