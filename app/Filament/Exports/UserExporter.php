<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama'),
            ExportColumn::make('jenis_kelamin')->label('Jenis Kelamin'),
            ExportColumn::make('role'),
            ExportColumn::make('nisn')->label('NISN'),
            ExportColumn::make('nip')->label('NIP'),
            ExportColumn::make('kelasTahunPelajaran.kelas.nama')->label('Kelas'),
            // BARU iterasi ini - lihat BUG FIX di atas.
            ExportColumn::make('kelasTahunPelajaran.kelas.jurusan.kode')->label('Kode Jurusan'),
            ExportColumn::make('kelasTahunPelajaran.tahunPelajaran.nama')->label('Tahun Pelajaran'),
            ExportColumn::make('status_akademik')->label('Status Akademik'),
            ExportColumn::make('jabatan'),
            ExportColumn::make('no_telepon')->label('No. Telepon'),
            ExportColumn::make('no_kartu_rfid')->label('No. Kartu RFID'),
            ExportColumn::make('status_suspend')->label('Suspend'),
            ExportColumn::make('akumulasi_point')->label('Point'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export User selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
