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
