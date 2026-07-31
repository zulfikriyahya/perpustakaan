<?php

namespace App\Filament\Imports;

use App\Models\Punishment;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert case-insensitive berdasarkan 'nama' (dikonfirmasi).
 */
class PunishmentImporter extends Importer
{
    protected static ?string $model = Punishment::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Skorsing peminjaman 7 hari'),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string'])
                ->example('Diberikan jika point minus mencapai ambang batas'),
            ImportColumn::make('threshold_point_minus')
                ->label('Ambang batas point minus')
                ->helperText('Isi dengan angka negatif atau 0 (mis. -50).')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'max:0'])
                ->example('-50'),
            ImportColumn::make('durasi_suspend_hari')
                ->label('Durasi suspend (hari, opsional)')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:1'])
                ->example('7'),
            ImportColumn::make('aktif')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->example('1'),
        ];
    }

    public function resolveRecord(): ?Punishment
    {
        $nama = trim($this->data['nama']);

        return Punishment::query()->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first()
            ?? new Punishment(['nama' => $nama]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Punishment selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}
