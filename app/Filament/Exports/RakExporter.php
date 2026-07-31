<?php

namespace App\Filament\Exports;

use App\Models\Rak;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class RakExporter extends Exporter
{
    protected static ?string $model = Rak::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('lokasi'),
            /**
             * TODO: verifikasi signature formatStateUsing() terhadap versi
             * filament/filament yang terpasang (composer.json: ^5.7).
             *
             * BUG FIX (ditemukan iterasi ini): sama kasus dengan
             * BukuExporter::kategoris - dipaksa pemisah ';' supaya cocok
             * dengan parser RakImporter (kolom 'kategori', Aturan poin 3),
             * mencegah kategori rak ter-sync kosong diam-diam saat file
             * hasil export diimpor ulang tanpa diedit.
             */
            ExportColumn::make('kategoris')
                ->label('Kategori Terkait')
                ->formatStateUsing(fn (Rak $record) => $record->kategoris->pluck('nama')->implode('; ')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Rak selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
