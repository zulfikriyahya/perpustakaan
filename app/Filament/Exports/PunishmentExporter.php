<?php

namespace App\Filament\Exports;

use App\Models\Punishment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PunishmentExporter extends Exporter
{
    protected static ?string $model = Punishment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('deskripsi'),
            ExportColumn::make('threshold_point_minus'),
            ExportColumn::make('durasi_suspend_hari'),
            ExportColumn::make('aktif'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Punishment selesai, ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
