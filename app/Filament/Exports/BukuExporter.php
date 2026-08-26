<?php

namespace App\Filament\Exports;

use App\Models\Buku;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class BukuExporter extends Exporter
{
    protected static ?string $model = Buku::class;

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['kategoris', 'eksemplars.rak']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('judul'),
            ExportColumn::make('penulis'),
            ExportColumn::make('penerbit'),
            ExportColumn::make('isbn')->label('ISBN'),
            ExportColumn::make('tahun_terbit')->label('Tahun Terbit'),
            ExportColumn::make('eksemplars')
                ->label('Stok (Jumlah Eksemplar)')
                ->formatStateUsing(fn (Buku $record) => (string) $record->eksemplars->count()),
            ExportColumn::make('rak')
                ->label('Rak (distinct, lihat catatan)')
                ->formatStateUsing(fn (Buku $record) => $record->eksemplars
                    ->pluck('rak.nama')
                    ->filter()
                    ->unique()
                    ->implode('; ')),
            ExportColumn::make('kategoris')
                ->label('Kategori')
                ->formatStateUsing(fn (Buku $record) => $record->kategoris->pluck('nama')->implode('; ')),
            ExportColumn::make('harga_ganti')->label('Harga Ganti'),
            ExportColumn::make('deskripsi'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Buku selesai, '.number_format($export->successful_rows).' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }
}
