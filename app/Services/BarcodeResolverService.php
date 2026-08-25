<?php

namespace App\Services;

use App\Models\Buku;
use App\Models\Eksemplar;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Satu sumber kebenaran untuk resolusi barcode Eksemplar/ISBN Buku yang
 * hasil scan-nya TERPOTONG sebagian (device tertentu di lapangan salah
 * mendekode sebagian digit EAN-13, terverifikasi berulang dan konsisten
 * pada device yang sama - lihat diskusi terkait). Jangan menulis ulang
 * logic subsequence-match ini di tempat lain (Aturan poin 3, DRY).
 *
 * TODO: GAP-SPEC - threshold MIN_PANJANG_FUZZY dan MARGIN_PANJANG di
 * bawah adalah ASUMSI berdasarkan pola yang diamati (kehilangan 2-4
 * digit dari ISBN 13 digit) - belum ada spesifikasi resmi. Sesuaikan
 * jika pola kehilangan digit di lapangan ternyata lebih ekstrem.
 */
class BarcodeResolverService
{
    /**
     * Panjang minimum input SEBELUM fuzzy match dicoba - mencegah query
     * fuzzy yang terlalu longgar untuk input pendek (risiko exploded
     * kandidat/positif-palsu tinggi kalau input cuma beberapa digit).
     */
    protected const MIN_PANJANG_FUZZY = 6;

    /**
     * Toleransi selisih panjang kandidat vs input yang di-scan - kandidat
     * dengan panjang di luar rentang ini TIDAK ikut diperiksa (menjaga
     * performa query, karena subsequence check dilakukan di PHP setelah
     * prefilter SQL).
     */
    protected const MARGIN_PANJANG = 6;

    /**
     * Exact match barcode Eksemplar (persis sama seperti sebelumnya,
     * tidak berubah) - disediakan di sini juga supaya caller bisa satu
     * pintu pemanggilan resolusi barcode Eksemplar bila diperlukan.
     */
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
            ->where('barcode', 'like', $karakterAwal . '%')
            ->whereRaw('LENGTH(barcode) BETWEEN ? AND ?', [$panjangMin, $panjangMax])
            ->with('buku')
            ->get();

        return $kandidat->filter(fn(Eksemplar $eksemplar) => $this->isSubsequence($kode, strtoupper($eksemplar->barcode)))->values();
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
            ->where('isbn', 'like', $karakterAwal . '%')
            ->whereRaw('LENGTH(isbn) BETWEEN ? AND ?', [$panjangMin, $panjangMax])
            ->get();

        return $kandidat->filter(fn(Buku $buku) => $this->isSubsequence($kode, $buku->isbn))->values();
    }

    /**
     * True jika seluruh karakter $needle muncul di $haystack dengan
     * URUTAN yang sama (tidak harus berdampingan). Algoritma two-pointer
     * O(panjang haystack) - cukup ringan untuk dijalankan per kandidat
     * hasil prefilter SQL di atas.
     */
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
