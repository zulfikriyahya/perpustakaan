<?php

namespace Tests\Unit;

use App\Models\Buku;
use App\Models\Eksemplar;
use App\Services\BarcodeResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mengunci perilaku BarcodeResolverService terhadap data nyata dari
 * lapangan (dua tipe alat scan berbeda) - lihat catatan GAP-SPEC di
 * BarcodeResolverService.php. Jika suatu saat MIN_PANJANG_FUZZY atau
 * MARGIN_PANJANG diubah, test ini akan gagal jika perubahan tsb sampai
 * merusak kompatibilitas kasus yang sudah terverifikasi ini.
 */
class BarcodeResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BarcodeResolverService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BarcodeResolverService::class);
    }

    /**
     * Alat Scan 1 - device yang terbukti menghilangkan sebagian digit
     * ISBN secara konsisten. Data persis dari laporan lapangan.
     */
    public function test_alat_scan_1_kasus_pertama_resolve_via_fuzzy_isbn(): void
    {
        $buku = Buku::factory()->create(['isbn' => '8991389241561']);

        $hasil = $this->service->cariBukuFuzzyByIsbn('89913824156');

        $this->assertCount(1, $hasil);
        $this->assertTrue($hasil->first()->is($buku));
    }

    public function test_alat_scan_1_kasus_kedua_resolve_via_fuzzy_isbn(): void
    {
        $buku = Buku::factory()->create(['isbn' => '8998989110167']);

        $hasil = $this->service->cariBukuFuzzyByIsbn('899811067');

        $this->assertCount(1, $hasil);
        $this->assertTrue($hasil->first()->is($buku));
    }

    /**
     * Fuzzy match juga harus jalan di level barcode Eksemplar (bukan
     * cuma ISBN Buku) - device yang sama dipakai untuk scan barcode
     * eksemplar pada alur pengembalian.
     */
    public function test_alat_scan_1_resolve_via_fuzzy_eksemplar_barcode(): void
    {
        $buku = Buku::factory()->create(['isbn' => '8991389241561']);
        $eksemplar = Eksemplar::factory()->create([
            'buku_id' => $buku->id,
            'barcode' => '8991389241561',
        ]);

        $hasil = $this->service->cariEksemplarFuzzy('89913824156');

        $this->assertCount(1, $hasil);
        $this->assertTrue($hasil->first()->is($eksemplar));
    }

    /**
     * Alat Scan 2 - device yang men-decode ISBN dengan benar dan persis.
     * Ini HARUS resolve lewat exact match (cariBukuPersisByIsbn), bukan
     * fuzzy - memastikan jalur exact-match tetap diprioritaskan dan
     * tidak terganggu oleh adanya fuzzy matching.
     */
    public function test_alat_scan_2_resolve_via_exact_match_kasus_pertama(): void
    {
        $buku = Buku::factory()->create(['isbn' => '8991389241561']);

        $hasil = $this->service->cariBukuPersisByIsbn('8991389241561');

        $this->assertNotNull($hasil);
        $this->assertTrue($hasil->is($buku));
    }

    public function test_alat_scan_2_resolve_via_exact_match_kasus_kedua(): void
    {
        $buku = Buku::factory()->create(['isbn' => '8998989110167']);

        $hasil = $this->service->cariBukuPersisByIsbn('8998989110167');

        $this->assertNotNull($hasil);
        $this->assertTrue($hasil->is($buku));
    }

    /**
     * Guard tambahan - input pendek (di bawah MIN_PANJANG_FUZZY) tidak
     * boleh memicu query fuzzy sama sekali (mencegah positif-palsu dari
     * ISBN pendek yang kebetulan jadi subsequence banyak buku).
     */
    public function test_input_di_bawah_minimum_panjang_tidak_fuzzy_match(): void
    {
        Buku::factory()->create(['isbn' => '8991389241561']);

        $hasil = $this->service->cariBukuFuzzyByIsbn('8991');

        $this->assertCount(0, $hasil);
    }
}
