<?php

namespace App\Filament\Exports;

use App\Models\Peminjaman;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PeminjamanExporter extends Exporter
{
    protected static ?string $model = Peminjaman::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('Peminjam'),
            ExportColumn::make('eksemplar.buku.judul')
                ->label('Buku')
                ->formatStateUsing(fn ($state) => $state ?? '(eksemplar sudah dihapus permanen)'),
            ExportColumn::make('tanggal_pinjam'),
            ExportColumn::make('tanggal_jatuh_tempo'),
            ExportColumn::make('status'),
            ExportColumn::make('diprosesOleh.nama')->label('Diproses Oleh'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Peminjaman selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
