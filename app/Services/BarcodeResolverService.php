<?php

namespace App\Services;

use App\Models\Buku;
use App\Models\Eksemplar;
use Illuminate\Database\Eloquent\Collection;

class BarcodeResolverService
{
    protected const MIN_PANJANG_FUZZY = 6;

    protected const MARGIN_PANJANG = 6;

    public function cariEksemplarPersis(string $kode): ?Eksemplar
    {
        return Eksemplar::query()->where('barcode', $kode)->with('buku')->first();
    }

    public function cariBukuPersisByIsbn(string $kode): ?Buku
    {
        return Buku::query()->where('isbn', $kode)->first();
    }

    /**
     * Fuzzy match barcode Eksemplar - kandidat yang barcode aslinya
     * MEMUAT seluruh karakter $kode secara berurutan (subsequence),
     * meski tidak berdampingan (properti yang cocok dengan pola digit
     * hilang yang diamati pada device tertentu).
     *
     * @return Collection<int, Eksemplar>
     */
    public function cariEksemplarFuzzy(string $kode): Collection
    {
        $kode = strtoupper(trim($kode));

        if (mb_strlen($kode) < self::MIN_PANJANG_FUZZY) {
            return new Collection;
        }

        $panjangMin = mb_strlen($kode);
        $panjangMax = $panjangMin + self::MARGIN_PANJANG;
        $karakterAwal = mb_substr($kode, 0, 1);

        $kandidat = Eksemplar::query()
            ->where('barcode', 'like', $karakterAwal.'%')
            ->whereRaw('LENGTH(barcode) BETWEEN ? AND ?', [$panjangMin, $panjangMax])
            ->with('buku')
            ->get();

        return $kandidat->filter(fn (Eksemplar $eksemplar) => $this->isSubsequence($kode, strtoupper($eksemplar->barcode)))->values();
    }

    /**
     * Fuzzy match ISBN Buku - properti sama seperti cariEksemplarFuzzy()
     * di atas, diterapkan ke kolom Buku.isbn.
     *
     * @return Collection<int, Buku>
     */
    public function cariBukuFuzzyByIsbn(string $kode): Collection
    {
        $kode = trim($kode);

        if (mb_strlen($kode) < self::MIN_PANJANG_FUZZY) {
            return new Collection;
        }

        $panjangMin = mb_strlen($kode);
        $panjangMax = $panjangMin + self::MARGIN_PANJANG;
        $karakterAwal = mb_substr($kode, 0, 1);

        $kandidat = Buku::query()
            ->whereNotNull('isbn')
            ->where('isbn', 'like', $karakterAwal.'%')
            ->whereRaw('LENGTH(isbn) BETWEEN ? AND ?', [$panjangMin, $panjangMax])
            ->get();

        return $kandidat->filter(fn (Buku $buku) => $this->isSubsequence($kode, $buku->isbn))->values();
    }

    protected function isSubsequence(string $needle, string $haystack): bool
    {
        $posNeedle = 0;
        $panjangNeedle = mb_strlen($needle);
        $panjangHaystack = mb_strlen($haystack);

        for ($posHaystack = 0; $posHaystack < $panjangHaystack && $posNeedle < $panjangNeedle; $posHaystack++) {
            if (mb_substr($haystack, $posHaystack, 1) === mb_substr($needle, $posNeedle, 1)) {
                $posNeedle++;
            }
        }

        return $posNeedle === $panjangNeedle;
    }
}
