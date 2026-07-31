<?php

namespace App\Filament\Exports;

use App\Models\Buku;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * BUG FIX (iterasi ini, pola sama dengan bug 'kategoris' sebelumnya):
 * kolom 'rak.nama' dan 'stok' sudah tidak ada lagi di tabel/model Buku
 * sejak migration 2026_08_02_000002-000004 (rak & stok pindah jadi
 * per-Eksemplar, bukan per-judul-buku) - keduanya dihapus dari sini.
 *
 * TODO: GAP-SPEC - kolom 'rak' hasil export sekarang menampilkan daftar
 * nama Rak DISTINCT dari semua eksemplar buku ini (bisa lebih dari satu
 * kalau eksemplar tersebar di rak berbeda), dipisah '; ' sama seperti
 * 'kategori'. TAPI ini informasional saja - BukuImporter hanya menerima
 * SATU nama rak per baris (dipakaikan ke SEMUA eksemplar baru dari
 * selisih stok import itu), jadi hasil export TIDAK bisa diimpor ulang
 * mentah-mentah kalau satu judul buku punya eksemplar di rak berbeda-beda.
 * Admin perlu edit manual jadi satu nama rak sebelum import ulang.
 */
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
            ExportColumn::make('tahun_terbit')->label('Tahun Terbit'),
            ExportColumn::make('eksemplars')
                ->label('Jumlah Eksemplar')
                ->formatStateUsing(fn (Buku $record) => (string) $record->eksemplars()->count()),
            ExportColumn::make('rak')
                ->label('Rak (distinct, lihat catatan)')
                ->formatStateUsing(fn (Buku $record) => $record->eksemplars()
                    ->with('rak')
                    ->get()
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
