<?php

namespace App\Filament\Imports;

use App\Models\Kategori;
use App\Models\Rak;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert case-insensitive berdasarkan 'nama' (dikonfirmasi) - "Rak A"
 * dan "rak a" dianggap Rak yang sama. Jika sudah ada baris cocok,
 * ejaan/kapitalisasi LAMA di database yang dipertahankan - hanya kolom
 * lain (lokasi, kategori) yang ter-update.
 */
class RakImporter extends Importer
{
    protected static ?string $model = Rak::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Rak A'),
            ImportColumn::make('lokasi')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Lantai 1, dekat pintu masuk'),
            ImportColumn::make('kategori')
                ->label('Kategori (nama, pisah titik-koma jika lebih dari satu)')
                ->rules(['nullable', 'string'])
                ->example('Fiksi;Sains'),
        ];
    }

    public function resolveRecord(): ?Rak
    {
        $nama = trim($this->data['nama']);

        return Rak::query()->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first()
            ?? new Rak(['nama' => $nama]);
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
        $body = 'Import Rak selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}
