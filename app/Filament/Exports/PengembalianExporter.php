<?php

namespace App\Filament\Exports;

use App\Models\Pengembalian;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PengembalianExporter extends Exporter
{
    protected static ?string $model = Pengembalian::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('peminjaman.user.nama')->label('Peminjam'),
            ExportColumn::make('peminjaman.buku.judul')->label('Buku'),
            ExportColumn::make('tanggal_kembali'),
            ExportColumn::make('kondisi'),
            ExportColumn::make('catatan'),
            ExportColumn::make('diprosesOleh.nama')->label('Diproses Oleh'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Pengembalian selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
