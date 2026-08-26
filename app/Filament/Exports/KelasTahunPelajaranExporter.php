<?php

namespace App\Filament\Exports;

use App\Models\KelasTahunPelajaran;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class KelasTahunPelajaranExporter extends Exporter
{
    protected static ?string $model = KelasTahunPelajaran::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('kelas.nama')->label('Kelas'),
            ExportColumn::make('kelas.jurusan.kode')->label('Kode Jurusan'),
            ExportColumn::make('tahunPelajaran.nama')->label('Tahun Pelajaran'),
            ExportColumn::make('waliKelas.nama')->label('Wali Kelas'),
            ExportColumn::make('waliKelas.nip')->label('NIP Wali Kelas'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Kelas per Tahun Pelajaran selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
