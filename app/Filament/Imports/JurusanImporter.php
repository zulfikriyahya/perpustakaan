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
