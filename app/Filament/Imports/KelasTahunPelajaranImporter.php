<?php

namespace App\Filament\Imports;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasTahunPelajaran;
use App\Models\TahunPelajaran;
use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

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
                ->example('2026/2027')
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
            throw new RowImportFailedException("NIP wali kelas \"{$this->data['wali_kelas_nip']}\" tidak ditemukan. Pastikan user dengan NIP tersebut sudah terdaftar.");
        }

        // FIX (iterasi ini): sebelumnya hanya menolak role Admin - tidak
        // selaras dengan KelasTahunPelajaranResource::form() yang membatasi
        // kandidat wali kelas HANYA role pustakawan/pegawai (whereIn di
        // relationship Select, lihat komentar "FIX" di file tersebut). Siswa
        // bisa lolos jadi wali kelas lewat import padahal opsi itu tidak
        // pernah muncul di form manual - disamakan di sini (Aturan poin 11 -
        // telusuri semua titik pemakaian saat mengubah aturan bisnis).
        if (! in_array($waliKelas->role->value, ['pustakawan', 'pegawai'], true)) {
            throw new RowImportFailedException('User dengan NIP tersebut bukan Pustakawan/Pegawai - hanya kedua role ini yang bisa dijadikan wali kelas (sama seperti pilihan di form manual).');
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
