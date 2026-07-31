<?php

namespace App\Filament\Imports;

use App\Enums\StatusEksemplar;
use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Rak;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;

/**
 * resolveRecord() upsert berdasarkan 'isbn' (barcode kini per eksemplar,
 * bukan per judul buku - lihat migration 2026_08_02_000003/000004).
 * Baris tanpa ISBN selalu jadi Buku baru.
 *
 * BUG FIX (iterasi ini): kolom 'barcode' SEBELUMNYA requiredMapping tanpa
 * fillRecordUsing no-op, padahal kolom 'barcode' sudah di-drop dari tabel
 * bukus - Filament akan mencoba assign $record->barcode sebelum save()
 * dan menyebabkan SQL error "Unknown column". Kolom 'barcode' dihapus
 * total dari sini; barcode eksemplar HANYA digenerate otomatis di
 * afterSave() (lihat TODO: GAP-SPEC di bawah), tidak lagi diambil dari
 * file import.
 *
 * BUG FIX (pola sama, ditemukan sebelumnya): kolom 'rak' dan 'kategori'
 * adalah lookup-only (bukan kolom asli tabel 'bukus') - tetap pakai
 * ->fillRecordUsing() no-op supaya tidak di-assign ke $record sebelum
 * save().
 *
 * KEPUTUSAN dikonfirmasi:
 * - harga_ganti WAJIB diisi manual di file - baris kosong GAGAL TOTAL
 *   (bukan default 0).
 * - Duplikasi ISBN antar baris/antar import: STOK diakumulasi (tambah
 *   eksemplar baru sejumlah selisih), eksemplar existing tidak dikurangi
 *   meski stok di file diturunkan.
 */
class BukuImporter extends Importer
{
    protected static ?string $model = Buku::class;

    /**
     * @var array<int, string>|null ID Kategori hasil resolve nama di
     *                              beforeSave() - null berarti kolom 'kategori' kosong (tidak
     *                              ada perubahan relasi). Divalidasi SEBELUM save() supaya baris
     *                              dengan nama kategori typo/tidak ditemukan GAGAL TOTAL
     *                              (dikonfirmasi) - bukan tersimpan sebagian dengan kategori
     *                              yang salah/hilang diam-diam.
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
            ImportColumn::make('tahun_terbit')
                ->label('Tahun Terbit')
                ->numeric()
                ->rules(['nullable', 'integer', 'digits:4'])
                ->example('2008'),
            ImportColumn::make('rak')
                ->label('Rak (nama, opsional)')
                ->helperText('Isi persis sesuai nama Rak yang sudah ada di Master Data > Rak. Jika tidak ditemukan, buku diimpor tanpa lokasi rak (bukan dibuatkan Rak baru otomatis).')
                ->rules(['nullable', 'string'])
                ->example('Rak A')
                // BUG FIX - lookup-only, lihat docblock class.
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('kategori')
                ->label('Kategori (nama, pisah titik-koma jika lebih dari satu)')
                ->helperText('Isi persis sesuai nama Kategori yang sudah ada di Master Data > Kategori. Contoh 2 kategori: "Fiksi;Sains". Kategori yang tidak ditemukan namanya akan membuat baris GAGAL.')
                ->rules(['nullable', 'string'])
                ->example('Fiksi;Sastra Indonesia')
                // BUG FIX - lookup-only, lihat docblock class.
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
                ->example('3'),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string'])
                ->example('Novel tentang perjuangan anak-anak Belitung mengejar pendidikan.'),
        ];
    }

    public function resolveRecord(): ?Buku
    {
        if (empty($this->data['isbn'])) {
            return new Buku;
        }

        return Buku::query()->firstOrNew(['isbn' => $this->data['isbn']]);
    }

    /**
     * Dipanggil setelah field kolom dasar di-assign, sebelum save() -
     * dipakai untuk resolusi 'rak'/'kategori' by nama (bukan foreign key
     * mentah), karena kolom ini bukan field langsung di tabel bukus.
     */
    protected function beforeSave(): void
    {
        // FIX: baris "$this->record->rak_id = ..." DIHAPUS. Buku tidak lagi
        // punya kolom rak_id (migration 2026_08_02_000003) - rak hanya
        // relevan di level Eksemplar, ditangani di afterSave().
        if (! empty($this->data['kategori'])) {
            $namaKategoris = array_values(array_filter(array_map('trim', explode(';', $this->data['kategori']))));
            $kategoris = Kategori::query()->whereIn('nama', $namaKategoris)->get(['id', 'nama']);

            $namaTidakDitemukan = array_diff($namaKategoris, $kategoris->pluck('nama')->all());

            if (! empty($namaTidakDitemukan)) {
                throw new RowImportFailedException('Kategori tidak ditemukan: "'.implode('", "', $namaTidakDitemukan).'". Cek ejaan atau tambahkan Kategori-nya dulu di Master Data > Kategori.');
            }

            $this->kategoriIdsTerresolve = $kategoris->pluck('id')->all();
        }
    }

    protected function afterSave(): void
    {
        if ($this->kategoriIdsTerresolve !== null) {
            $this->record->kategoris()->sync($this->kategoriIdsTerresolve);
        }

        // TODO: GAP-SPEC - strategi generate barcode otomatis saat import
        // belum dikonfirmasi selain format dasar ini. Format:
        // "{ISBN-or-JUDULSLUG}-{urutan}". Konfirmasi sebelumnya: stok
        // diakumulasi (tambah eksemplar sejumlah selisih), tidak pernah
        // mengurangi eksemplar existing meski stok di file diturunkan.
        $rak = ! empty($this->data['rak'])
            ? Rak::query()->where('nama', trim($this->data['rak']))->first()
            : null;

        $stokDiminta = (int) ($this->data['stok'] ?? 0);
        $eksemplarSaatIni = $this->record->eksemplars()->count();
        $selisih = $stokDiminta - $eksemplarSaatIni;

        for ($i = 0; $i < $selisih; $i++) {
            $this->record->eksemplars()->create([
                'barcode' => strtoupper(($this->record->isbn ?: Str::slug($this->record->judul)).'-'.($eksemplarSaatIni + $i + 1)),
                'rak_id' => $rak?->id,
                'status' => StatusEksemplar::Tersedia,
            ]);
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
