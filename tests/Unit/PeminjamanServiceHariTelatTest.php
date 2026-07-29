<?php

namespace Tests\Unit;

use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use App\Services\PointService;
use App\Services\WhatsappService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Mengunci perilaku hitungHariTelat() secara eksplisit - lihat catatan di
 * PeminjamanService::hitungHariTelat(). Tidak butuh DB (Peminjaman
 * diinstansiasi tanpa disimpan, hanya attribute tanggal_jatuh_tempo yang dibaca).
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
