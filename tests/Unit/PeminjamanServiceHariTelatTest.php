<?php

namespace Tests\Unit;

use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use App\Services\PointService;
use App\Services\WhatsappService;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Mengunci perilaku hitungHariTelat() secara eksplisit - lihat catatan di
 * PeminjamanService::hitungHariTelat(). Tidak melakukan query DB sungguhan
 * (Peminjaman diinstansiasi tanpa disimpan) - TAPI tetap extends
 * Tests\TestCase (bukan PHPUnit\Framework\TestCase murni) karena Eloquent
 * membutuhkan aplikasi Laravel ter-boot supaya connection resolver
 * ter-set, walau tidak ada query yang benar-benar dijalankan.
 */
class PeminjamanServiceHariTelatTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function hitung(PeminjamanService $service, Peminjaman $peminjaman): int
    {
        $method = new ReflectionMethod(PeminjamanService::class, 'hitungHariTelat');
        $method->setAccessible(true);

        return $method->invoke($service, $peminjaman);
    }

    protected function buatService(): PeminjamanService
    {
        return new PeminjamanService(
            $this->createMock(PointService::class),
            $this->createMock(WhatsappService::class),
        );
    }

    public function test_belum_jatuh_tempo_menghasilkan_nol(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01'));

        $peminjaman = new Peminjaman(['tanggal_jatuh_tempo' => '2026-07-10']);

        $this->assertSame(0, $this->hitung($this->buatService(), $peminjaman));
    }

    public function test_tepat_jatuh_tempo_menghasilkan_nol(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-10'));

        $peminjaman = new Peminjaman(['tanggal_jatuh_tempo' => '2026-07-10']);

        $this->assertSame(0, $this->hitung($this->buatService(), $peminjaman));
    }

    public function test_terlambat_lima_hari_menghasilkan_lima(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15'));

        $peminjaman = new Peminjaman(['tanggal_jatuh_tempo' => '2026-07-10']);

        $this->assertSame(5, $this->hitung($this->buatService(), $peminjaman));
    }
}
