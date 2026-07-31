<?php

namespace App\Filament\Exports;

use App\Models\RewardLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class RewardLogExporter extends Exporter
{
    protected static ?string $model = RewardLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('User'),
            ExportColumn::make('reward.nama')->label('Reward'),
            ExportColumn::make('tanggal_didapat'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Riwayat Reward selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
