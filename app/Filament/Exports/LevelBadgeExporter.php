<?php

namespace App\Filament\Exports;

use App\Models\LevelBadge;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LevelBadgeExporter extends Exporter
{
    protected static ?string $model = LevelBadge::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama_badge'),
            ExportColumn::make('min_point'),
            ExportColumn::make('max_point'),
            ExportColumn::make('urutan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Level Badge selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
