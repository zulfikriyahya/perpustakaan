<?php

namespace App\Filament\Imports;

use App\Models\Buku;
use App\Services\BukuImportResolverService;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * resolveRecord() upsert berdasarkan 'isbn' (barcode kini per eksemplar,
 * bukan per judul buku - lihat migration 2026_08_02_000003/000004).
 * Baris tanpa ISBN selalu jadi Buku baru.
 *
 * REFACTOR (iterasi ini): seluruh resolusi Buku/Kategori/Eksemplar
 * dipindah ke BukuImportResolverService (Aturan poin 3, DRY) -
 * sebelumnya logic ini terduplikasi manual di closure 'buku'
 * MasterDataRegistry (ditemukan saat review), berisiko drift kalau
 * salah satu diperbaiki tapi yang lain tidak. Kontrak
 * kolom/rules/pesan/perilaku dari sisi pengguna TIDAK berubah sama
 * sekali dibanding versi sebelumnya.
 *
 * KEPUTUSAN dikonfirmasi (tetap berlaku, lihat detail di
 * BukuImportResolverService):
 * - harga_ganti WAJIB diisi manual - baris kosong GAGAL TOTAL.
 * - Duplikasi ISBN: STOK diakumulasi, eksemplar existing tidak
 *   pernah dikurangi meski stok di file diturunkan.
 */
class BukuImporter extends Importer
{
    protected static ?string $model = Buku::class;

    /**
     * @var array<int, string>|null Hasil resolve nama kategori di
     *                              beforeSave() - null berarti kolom 'kategori' kosong.
     */
    protected ?array $kategoriIdsTerresolve = null;

    protected function resolver(): BukuImportResolverService
    {
        return app(BukuImportResolverService::class);
    }

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
            ImportColumn::make('tahun_terbit')
                ->label('Tahun Terbit')
                ->numeric()
                ->rules(['nullable', 'integer', 'digits:4'])
                ->example('2008'),
            ImportColumn::make('rak')
                ->label('Rak (nama, opsional)')
                ->helperText('Isi persis sesuai nama Rak yang sudah ada di Master Data > Rak. Jika tidak ditemukan, buku diimport tanpa lokasi rak (bukan dibuatkan Rak baru otomatis).')
                ->rules(['nullable', 'string'])
                ->example('Rak A')
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('kategori')
                ->label('Kategori (nama, pisah titik-koma jika lebih dari satu)')
                ->helperText('Isi persis sesuai nama Kategori yang sudah ada di Master Data > Kategori. Contoh 2 kategori: "Fiksi;Sains". Kategori yang tidak ditemukan namanya akan membuat baris GAGAL.')
                ->rules(['nullable', 'string'])
                ->example('Fiksi;Sastra Indonesia')
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('harga_ganti')
                ->label('Harga Ganti')
                ->helperText('WAJIB diisi manual - dipakai sebagai basis perhitungan Denda kerusakan/kehilangan. Baris tanpa nilai ini akan GAGAL, tidak ada default otomatis.')
                ->numeric()
                ->rules(['required', 'numeric', 'min:0'])
                ->example('75000'),
            ImportColumn::make('stok')
                ->helperText('Jumlah eksemplar fisik untuk ISBN ini. Import ulang ISBN yang sama akan MENAMBAH eksemplar sejumlah selisih (stok baru - jumlah eksemplar existing), tidak pernah mengurangi eksemplar yang sudah ada.')
                ->numeric()
                ->rules(['required', 'integer', 'min:0'])
                ->example('3')
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string'])
                ->example('Novel tentang perjuangan anak-anak Belitung mengejar pendidikan.'),
        ];
    }

    public function resolveRecord(): ?Buku
    {
        return $this->resolver()->resolveOrCreateBuku($this->data['isbn'] ?? null);
    }

    /**
     * Dipanggil setelah field kolom dasar di-assign, sebelum save() -
     * dipakai untuk resolusi 'kategori' by nama (bukan foreign key
     * mentah), karena kolom ini bukan field langsung di tabel bukus.
     */
    protected function beforeSave(): void
    {
        $this->kategoriIdsTerresolve = $this->resolver()->resolveKategoriIds($this->data['kategori'] ?? null);
    }

    protected function afterSave(): void
    {
        $this->resolver()->syncKategori($this->record, $this->kategoriIdsTerresolve);

        $this->resolver()->sinkronEksemplarDariSelisihStok(
            $this->record,
            (int) ($this->data['stok'] ?? 0),
            $this->data['rak'] ?? null,
        );
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
