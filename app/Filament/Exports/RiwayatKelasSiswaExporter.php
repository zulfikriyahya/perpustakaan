<?php

namespace App\Filament\Exports;

use App\Models\RiwayatKelasSiswa;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class RiwayatKelasSiswaExporter extends Exporter
{
    protected static ?string $model = RiwayatKelasSiswa::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user', 'kelasTahunPelajaran.kelas', 'kelasTahunPelajaran.tahunPelajaran']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.nama')->label('Siswa'),
            ExportColumn::make('user.nisn')->label('NISN'),
            ExportColumn::make('kelasTahunPelajaran.kelas.nama')->label('Kelas'),
            ExportColumn::make('kelasTahunPelajaran.tahunPelajaran.nama')->label('Tahun Pelajaran'),
            ExportColumn::make('status'),
            ExportColumn::make('tanggal_mulai'),
            ExportColumn::make('tanggal_selesai'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Riwayat Kelas Siswa selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
