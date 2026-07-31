<?php

namespace App\Filament\Exports;

use App\Models\Eksemplar;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EksemplarExporter extends Exporter
{
    protected static ?string $model = Eksemplar::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('barcode'),
            ExportColumn::make('buku.isbn')
                ->label('ISBN Buku'),
            ExportColumn::make('buku.judul')
                ->label('Judul Buku'),
            ExportColumn::make('rak.nama')
                ->label('Rak'),
            ExportColumn::make('status')
                ->formatStateUsing(fn (Eksemplar $record) => $record->status->value),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Eksemplar selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
