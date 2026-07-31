<?php

namespace App\Filament\Imports;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Rak;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * TODO: GAP-SPEC - resolveRecord() upsert berdasarkan 'barcode' (create
 * jika tidak ada, update jika sudah ada). Belum dikonfirmasi apakah
 * perilaku yang diinginkan justru reject baris dengan barcode duplikat.
 */
class BukuImporter extends Importer
{
    protected static ?string $model = Buku::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('judul')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('penulis')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('penerbit')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('isbn')
                ->label('ISBN')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('barcode')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('rak')
                ->label('Rak (nama)')
                ->rules(['nullable', 'string']),
            ImportColumn::make('kategori')
                ->label('Kategori (nama, pisah titik-koma jika lebih dari satu)')
                ->rules(['nullable', 'string']),
            ImportColumn::make('harga_ganti')
                ->label('Harga Ganti')
                ->numeric()
                ->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('stok')
                ->numeric()
                ->rules(['required', 'integer', 'min:0']),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?Buku
    {
        // upsert - lihat TODO: GAP-SPEC di atas class.
        return Buku::query()->firstOrNew(['barcode' => $this->data['barcode']]);
    }

    /**
     * Dipanggil setelah field kolom dasar di-assign, sebelum save() -
     * dipakai untuk resolusi 'rak'/'kategori' by nama (bukan foreign key
     * mentah), karena kolom ini bukan field langsung di tabel bukus.
     */
    protected function beforeSave(): void
    {
        if (! empty($this->data['rak'])) {
            $rak = Rak::query()->where('nama', trim($this->data['rak']))->first();
            $this->record->rak_id = $rak?->id;
        }
    }

    protected function afterSave(): void
    {
        if (! empty($this->data['kategori'])) {
            $namaKategoris = array_filter(array_map('trim', explode(';', $this->data['kategori'])));
            $kategoriIds = Kategori::query()->whereIn('nama', $namaKategoris)->pluck('id');
            $this->record->kategoris()->sync($kategoriIds);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Buku selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}
