<?php

namespace App\Filament\Exports;

use App\Models\PunishmentLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PunishmentLogExporter extends Exporter
{
    protected static ?string $model = PunishmentLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('User'),
            ExportColumn::make('punishment.nama')->label('Punishment'),
            ExportColumn::make('tanggal_diterapkan'),
            ExportColumn::make('tanggal_berakhir'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Riwayat Punishment selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
