<?php

namespace App\Services;

use App\Enums\StatusEksemplar;
use App\Models\Buku;
use App\Models\Eksemplar;
use App\Models\Kategori;
use App\Models\Rak;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;

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
     *                                 (tidak ada perubahan relasi).
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
            throw new RowImportFailedException('Kategori tidak ditemukan: "'.implode('", "', $namaTidakDitemukan).'". Cek ejaan atau tambahkan Kategori-nya dulu di Master Data > Kategori.');
        }

        return $kategoris->pluck('id')->all();
    }

    public function syncKategori(Buku $buku, ?array $kategoriIds): void
    {
        if ($kategoriIds !== null) {
            $buku->kategoris()->sync($kategoriIds);
        }
    }

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
