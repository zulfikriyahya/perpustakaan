<?php

namespace App\Filament\Imports;

use App\Models\LevelBadge;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert case-insensitive berdasarkan 'nama_badge' (dikonfirmasi).
 */
class LevelBadgeImporter extends Importer
{
    protected static ?string $model = LevelBadge::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama_badge')
                ->label('Nama badge')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Kutu Buku'),
            ImportColumn::make('min_point')
                ->label('Point minimal')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer'])
                ->example('0'),
            ImportColumn::make('max_point')
                ->label('Point maksimal (opsional)')
                ->helperText('Kosongkan jika badge ini adalah level tertinggi (tidak ada batas atas).')
                ->numeric()
                ->rules(['nullable', 'integer'])
                ->example('100'),
            ImportColumn::make('urutan')
                ->helperText('Angka lebih kecil ditampilkan lebih dulu.')
                ->numeric()
                ->rules(['nullable', 'integer'])
                ->example('1'),
        ];
    }

    public function resolveRecord(): ?LevelBadge
    {
        $nama = trim($this->data['nama_badge']);

        return LevelBadge::query()->whereRaw('LOWER(nama_badge) = ?', [mb_strtolower($nama)])->first()
            ?? new LevelBadge(['nama_badge' => $nama]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Level Badge selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}
