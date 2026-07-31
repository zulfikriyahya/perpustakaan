<?php

namespace App\Filament\Imports;

use App\Models\Kategori;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert case-insensitive berdasarkan 'nama' (dikonfirmasi) - "Fiksi"
 * dan "fiksi" dianggap kategori yang sama, mencegah duplikat akibat
 * ketidakkonsistenan pengetikan staf. Jika sudah ada baris cocok,
 * ejaan/kapitalisasi LAMA di database yang dipertahankan (baris di
 * file import tidak menimpa nama yang sudah ada) - hanya kolom lain
 * (deskripsi) yang ter-update.
 */
class KategoriImporter extends Importer
{
    protected static ?string $model = Kategori::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Fiksi'),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string'])
                ->example('Novel dan cerita rekaan'),
        ];
    }

    public function resolveRecord(): ?Kategori
    {
        $nama = trim($this->data['nama']);

        return Kategori::query()->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first()
            ?? new Kategori(['nama' => $nama]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Kategori selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}
