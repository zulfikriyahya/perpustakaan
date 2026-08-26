<?php

namespace App\Filament\Imports;

use App\Models\Jurusan;
use App\Models\Kelas;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class KelasImporter extends Importer
{
    protected static ?string $model = Kelas::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->label('Nama kelas (mis. X IPA 1)')
                ->helperText('Unik per Jurusan - boleh ada nama sama di Jurusan berbeda (mis. "X-1" di IPA dan "X-1" di IPS).')
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
                ->label('Kode Jurusan (wajib)')
                ->helperText('Lihat daftar kode di menu Master Data > Jurusan. Setiap Kelas wajib punya Jurusan.')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('IPA')
                ->fillRecordUsing(fn (?string $state) => null),
        ];
    }

    public function resolveRecord(): ?Kelas
    {
        $jurusan = Jurusan::query()->where('kode', $this->data['jurusan_kode'])->first();

        if (! $jurusan) {
            throw new RowImportFailedException("Jurusan dengan kode \"{$this->data['jurusan_kode']}\" tidak ditemukan.");
        }

        return Kelas::query()->firstOrNew([
            'jurusan_id' => $jurusan->id,
            'nama' => trim($this->data['nama']),
        ]);
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
