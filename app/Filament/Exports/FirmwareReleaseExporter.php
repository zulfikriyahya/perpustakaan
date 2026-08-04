<?php

namespace App\Filament\Exports;

use App\Models\FirmwareRelease;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * Export metadata rilis firmware (version, url, md5, aktif, catatan).
 * TIDAK ada Importer pasangan - kolom 'file' adalah hasil FileUpload
 * (.bin) yang disimpan sebagai path di storage, tidak bisa direpresentasikan
 * dalam sel .xlsx. Rilis firmware baru wajib tetap lewat form Create
 * (upload file manual), bukan import massal.
 */
class FirmwareReleaseExporter extends Exporter
{
    protected static ?string $model = FirmwareRelease::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('version')->label('Versi'),
            ExportColumn::make('url')->label('URL'),
            ExportColumn::make('md5')->label('MD5'),
            ExportColumn::make('aktif'),
            ExportColumn::make('catatan'),
            ExportColumn::make('created_at')->label('Tanggal Rilis'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Firmware OTA selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
