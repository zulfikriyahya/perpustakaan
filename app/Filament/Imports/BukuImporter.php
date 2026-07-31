<?php

namespace App\Filament\Imports;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Rak;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * resolveRecord() upsert berdasarkan 'barcode' (dikonfirmasi): jika
 * barcode sudah ada di database, data buku tersebut DITIMPA (judul,
 * penulis, penerbit, dll ikut berubah sesuai baris import) - bukan
 * ditolak sebagai error.
 */
class BukuImporter extends Importer
{
    protected static ?string $model = Buku::class;

    /**
     * @var array<int, string>|null ID Kategori hasil resolve nama di
     *     beforeSave() - null berarti kolom 'kategori' kosong (tidak
     *     ada perubahan relasi). Divalidasi SEBELUM save() supaya baris
     *     dengan nama kategori typo/tidak ditemukan GAGAL TOTAL
     *     (dikonfirmasi) - bukan tersimpan sebagian dengan kategori
     *     yang salah/hilang diam-diam.
     */
    protected ?array $kategoriIdsTerresolve = null;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('judul')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Laskar Pelangi'),
            ImportColumn::make('penulis')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Andrea Hirata'),
            ImportColumn::make('penerbit')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Bentang Pustaka'),
            ImportColumn::make('isbn')
                ->label('ISBN')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('9789793062792'),
            ImportColumn::make('barcode')
                ->helperText('Kode unik fisik buku (label barcode yang ditempel). Barcode yang sudah ada di database akan DITIMPA datanya (judul/penulis/dll ikut berubah), bukan ditolak.')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('BC0001234'),
            ImportColumn::make('rak')
                ->label('Rak (nama, opsional)')
                ->helperText('Isi persis sesuai nama Rak yang sudah ada di Master Data > Rak. Jika tidak ditemukan, buku diimpor tanpa lokasi rak (bukan dibuatkan Rak baru otomatis).')
                ->rules(['nullable', 'string'])
                ->example('Rak A'),
            ImportColumn::make('kategori')
                ->label('Kategori (nama, pisah titik-koma jika lebih dari satu)')
                ->helperText('Isi persis sesuai nama Kategori yang sudah ada di Master Data > Kategori. Contoh 2 kategori: "Fiksi;Sains". Kategori yang tidak ditemukan namanya akan dilewati diam-diam (bukan dibuatkan baru).')
                ->rules(['nullable', 'string'])
                ->example('Fiksi;Sastra Indonesia'),
            ImportColumn::make('harga_ganti')
                ->label('Harga Ganti')
                ->helperText('Nominal rupiah, dipakai sebagai basis perhitungan Denda kerusakan/kehilangan.')
                ->numeric()
                ->rules(['required', 'numeric', 'min:0'])
                ->example('75000'),
            ImportColumn::make('stok')
                ->numeric()
                ->rules(['required', 'integer', 'min:0'])
                ->example('3'),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string'])
                ->example('Novel tentang perjuangan anak-anak Belitung mengejar pendidikan.'),
        ];
    }

    public function resolveRecord(): ?Buku
    {
        // upsert berdasarkan barcode - dikonfirmasi, lihat docblock class.
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

        if (! empty($this->data['kategori'])) {
            $namaKategoris = array_values(array_filter(array_map('trim', explode(';', $this->data['kategori']))));
            $kategoris = Kategori::query()->whereIn('nama', $namaKategoris)->get(['id', 'nama']);

            $namaTidakDitemukan = array_diff($namaKategoris, $kategoris->pluck('nama')->all());

            if (! empty($namaTidakDitemukan)) {
                throw new RowImportFailedException('Kategori tidak ditemukan: "' . implode('", "', $namaTidakDitemukan) . '". Cek ejaan atau tambahkan Kategori-nya dulu di Master Data > Kategori.');
            }

            $this->kategoriIdsTerresolve = $kategoris->pluck('id')->all();
        }
    }

    protected function afterSave(): void
    {
        if ($this->kategoriIdsTerresolve !== null) {
            $this->record->kategoris()->sync($this->kategoriIdsTerresolve);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Buku selesai, ' . number_format($import->successful_rows) . ' / ' . number_format($import->total_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}
