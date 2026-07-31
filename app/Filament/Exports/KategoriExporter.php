<?php

namespace App\Filament\Exports;

use App\Models\Kategori;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class KategoriExporter extends Exporter
{
    protected static ?string $model = Kategori::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('deskripsi'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Kategori selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
