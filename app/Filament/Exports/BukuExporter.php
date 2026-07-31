<?php

namespace App\Filament\Exports;

use App\Models\Buku;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class BukuExporter extends Exporter
{
    protected static ?string $model = Buku::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('judul'),
            ExportColumn::make('penulis'),
            ExportColumn::make('penerbit'),
            ExportColumn::make('isbn')->label('ISBN'),
            ExportColumn::make('barcode'),
            ExportColumn::make('rak.nama')->label('Rak'),
            /**
             * TODO: verifikasi signature formatStateUsing() terhadap versi
             * filament/filament yang terpasang (composer.json: ^5.7).
             *
             * BUG FIX (ditemukan iterasi ini): sebelumnya kolom ini memakai
             * dot-notation 'kategoris.nama' polos, yang oleh Filament
             * digabung dengan pemisah default (', ') untuk relasi many.
             * BukuImporter mem-parse kolom 'kategori' dengan pemisah ';'
             * (Aturan poin 3, satu sumber kebenaran kontrak data) - hasil
             * export TIDAK bisa langsung diimpor ulang tanpa admin sadar
             * harus mengganti koma jadi titik-koma manual, dan jika lolos
             * tanpa diedit, seluruh kategori buku akan ter-sync KOSONG
             * (hilang diam-diam) karena string gabungan tidak cocok nama
             * kategori manapun. Dipaksa pakai ';' di sini supaya alur
             * export -> edit -> import ulang aman untuk admin pemula.
             */
            ExportColumn::make('kategoris')
                ->label('Kategori')
                ->formatStateUsing(fn(Buku $record) => $record->kategoris->pluck('nama')->implode('; ')),
            ExportColumn::make('harga_ganti')->label('Harga Ganti'),
            ExportColumn::make('stok'),
            ExportColumn::make('deskripsi'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Buku selesai, ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}
