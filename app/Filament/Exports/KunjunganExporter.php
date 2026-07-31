<?php

namespace App\Filament\Exports;

use App\Models\Kunjungan;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class KunjunganExporter extends Exporter
{
    protected static ?string $model = Kunjungan::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('Pengunjung'),
            ExportColumn::make('tanggal'),
            ExportColumn::make('jam_tap'),
            ExportColumn::make('source'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Kunjungan selesai, ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
