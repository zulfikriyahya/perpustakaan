<?php

namespace App\Filament\Exports;

use App\Models\Denda;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class DendaExporter extends Exporter
{
    protected static ?string $model = Denda::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('User'),
            ExportColumn::make('peminjaman.buku.judul')->label('Buku'),
            ExportColumn::make('tipe'),
            ExportColumn::make('nominal'),
            ExportColumn::make('status_lunas'),
            ExportColumn::make('tanggal_lunas'),
            ExportColumn::make('status_refund'),
            ExportColumn::make('keterangan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Denda selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
