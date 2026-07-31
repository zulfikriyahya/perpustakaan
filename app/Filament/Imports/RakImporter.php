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
        $body = 'Import Rak selesai, ' . number_format($import->successful_rows) . ' / ' . number_format($import->total_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}
