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
