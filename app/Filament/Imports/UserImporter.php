<?php

namespace App\Filament\Imports;

use App\Enums\RoleUser;
use App\Models\KelasTahunPelajaran;
use App\Models\User;
use App\Services\KenaikanKelasService;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
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
 * notifikasi password ke user baru dalam iterasi ini.
 *
 * TODO: GAP-SPEC - kolom 'kelas_tahun_pelajaran' berformat teks bebas
 * "Nama Kelas - Nama Tahun Pelajaran" (mis. "X IPA 1 - 2025/2026"),
 * dipisah pada tanda "-" TERAKHIR karena nama Kelas sendiri bisa
 * mengandung "-". Format ini asumsi/keputusan sepihak karena belum ada
 * spek resmi format Excel dari sekolah - jika format sumber data
 * berbeda, sesuaikan resolveKtp() di bawah. Baris yang tidak match KTP
 * manapun akan GAGAL divalidasi (RowImportFailedException, masuk
 * failed-rows CSV) - TIDAK assignment diam-diam ke kelas yang salah.
 */
class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    /**
     * KTP hasil resolve di resolveRecord(), dipakai lagi di afterSave()
     * supaya logic parsing teks "kelas_tahun_pelajaran" tidak diduplikasi
     * (Aturan poin 3, DRY - satu resolve, dua pemakaian).
     */
    protected ?KelasTahunPelajaran $ktpTerresolve = null;

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
            ImportColumn::make('kelas_tahun_pelajaran')
                ->label('Kelas (format: "Nama Kelas - Nama Tahun Pelajaran")')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('X IPA 1 - 2025/2026'),
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
        $teks = trim((string) ($this->data['kelas_tahun_pelajaran'] ?? ''));

        if ($teks !== '') {
            $this->ktpTerresolve = $this->parseKtp($teks);

            if (! $this->ktpTerresolve) {
                throw new RowImportFailedException("KTP tidak ditemukan untuk teks kelas_tahun_pelajaran: \"{$teks}\".");
            }
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
     * Assignment lewat service (bukan mass-assign kolom di resolveRecord)
     * supaya RiwayatKelasSiswa tetap tercatat, dan hanya dijalankan
     * SETELAH record dasar tersimpan (butuh $this->record->id).
     */
    protected function afterSave(): void
    {
        if ($this->ktpTerresolve) {
            app(KenaikanKelasService::class)->assignKelas($this->record, $this->ktpTerresolve);
        }
    }

    protected function parseKtp(string $teks): ?KelasTahunPelajaran
    {
        $posisi = strrpos($teks, '-');

        if ($posisi === false) {
            return null;
        }

        $namaKelas = trim(substr($teks, 0, $posisi));
        $namaTahun = trim(substr($teks, $posisi + 1));

        if ($namaKelas === '' || $namaTahun === '') {
            return null;
        }

        return KelasTahunPelajaran::query()
            ->whereHas('kelas', fn ($q) => $q->where('nama', $namaKelas))
            ->whereHas('tahunPelajaran', fn ($q) => $q->where('nama', $namaTahun))
            ->first();
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
