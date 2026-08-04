<?php

namespace App\Services;

use App\Enums\StatusEksemplar;
use App\Models\Buku;
use App\Models\Eksemplar;
use App\Models\Kategori;
use App\Models\Rak;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;

/**
 * SATU SUMBER KEBENARAN (Aturan poin 3) untuk resolusi Buku saat import -
 * dipakai BukuImporter (per-Resource, halaman Buku) DAN MasterDataRegistry
 * (jalur "Import Semua"). Sebelumnya logic ini terduplikasi manual di dua
 * tempat (ditemukan saat review iterasi ini) - berisiko drift diam-diam
 * kalau salah satu diperbaiki tapi yang lain tidak, sehingga dua jalur
 * import Buku bisa berbeda perilaku tanpa terdeteksi.
 *
 * Method di sini SENGAJA menerima primitive (string/int/null), bukan
 * $this->data dari konteks Filament\Actions\Imports\Importer - supaya bisa
 * dipanggil juga dari closure MasterDataRegistry yang tidak sepenuhnya
 * punya konteks Importer.
 *
 * KEPUTUSAN dikonfirmasi (tetap berlaku, dipindah dari BukuImporter lama):
 * - harga_ganti WAJIB diisi manual di file - baris kosong GAGAL TOTAL
 *   (bukan default 0) - divalidasi di ImportColumn/closure pemanggil,
 *   BUKAN di service ini (service tidak menyentuh harga_ganti).
 * - Duplikasi ISBN antar baris/antar import: STOK diakumulasi (tambah
 *   eksemplar baru sejumlah selisih), eksemplar existing tidak pernah
 *   dikurangi meski stok di file diturunkan.
 * - Kategori tidak ditemukan by nama -> baris GAGAL TOTAL (tidak
 *   tersimpan sebagian dengan kategori salah/hilang diam-diam).
 */
class BukuImportResolverService
{
    public function resolveOrCreateBuku(?string $isbn): Buku
    {
        $isbn = trim((string) $isbn);

        return $isbn !== ''
            ? Buku::query()->firstOrNew(['isbn' => $isbn])
            : new Buku;
    }

    /**
     * @return array<int, string>|null null berarti kolom kategori kosong
     *                                  (tidak ada perubahan relasi).
     *
     * @throws RowImportFailedException jika ada nama kategori yang tidak ditemukan.
     */
    public function resolveKategoriIds(?string $namaKategoriGabungan): ?array
    {
        if (empty($namaKategoriGabungan)) {
            return null;
        }

        $namaKategoris = array_values(array_filter(array_map('trim', explode(';', $namaKategoriGabungan))));
        $kategoris = Kategori::query()->whereIn('nama', $namaKategoris)->get(['id', 'nama']);

        $namaTidakDitemukan = array_diff($namaKategoris, $kategoris->pluck('nama')->all());

        if (! empty($namaTidakDitemukan)) {
            throw new RowImportFailedException('Kategori tidak ditemukan: "' . implode('", "', $namaTidakDitemukan) . '". Cek ejaan atau tambahkan Kategori-nya dulu di Master Data > Kategori.');
        }

        return $kategoris->pluck('id')->all();
    }

    public function syncKategori(Buku $buku, ?array $kategoriIds): void
    {
        if ($kategoriIds !== null) {
            $buku->kategoris()->sync($kategoriIds);
        }
    }

    /**
     * Menambah eksemplar baru sejumlah selisih (stokDiminta - existing),
     * TIDAK PERNAH mengurangi eksemplar existing (keputusan dikonfirmasi).
     * barcode digenerate otomatis via Eksemplar::generateBarcodeUntuk().
     */
    public function sinkronEksemplarDariSelisihStok(Buku $buku, int $stokDiminta, ?string $namaRak): void
    {
        $rak = ! empty($namaRak)
            ? Rak::query()->where('nama', trim($namaRak))->first()
            : null;

        $eksemplarSaatIni = $buku->eksemplars()->count();
        $selisih = $stokDiminta - $eksemplarSaatIni;

        for ($i = 0; $i < $selisih; $i++) {
            $buku->eksemplars()->create([
                'barcode' => Eksemplar::generateBarcodeUntuk($buku, $eksemplarSaatIni + $i + 1),
                'rak_id' => $rak?->id,
                'status' => StatusEksemplar::Tersedia,
            ]);
        }
    }
}
