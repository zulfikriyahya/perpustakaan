<?php

namespace App\Filament\Pages;

use App\Enums\KondisiBuku;
use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Models\Eksemplar;
use App\Models\Peminjaman;
use App\Models\User;
use App\Services\PeminjamanService;
use App\Services\RfidResolverService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Halaman transaksi cepat: scan kartu (identifikasi user) -> scan barcode
 * EKSEMPLAR satu per satu -> sistem OTOMATIS deteksi pinjam/kembali per
 * eksemplar, diproses langsung tiap scan (TIDAK dikumpulkan dulu, sesuai
 * keputusan QA). Seluruh logic bisnis (limit, stok, Denda, Point, WA) tetap
 * lewat PeminjamanService - halaman ini murni orkestrasi UI (Aturan poin 3).
 *
 * BUG FIX (iterasi ini): sebelumnya scan barcode query ke Buku.barcode dan
 * Peminjaman.buku_id - keduanya sudah tidak ada lagi sejak migration
 * 2026_08_02_000002-000004 (barcode & relasi pinjam kini per Eksemplar,
 * bukan per judul Buku). Diganti total ke Eksemplar.barcode dan
 * Peminjaman.eksemplar_id. Parameter ke PeminjamanService::pinjamBuku()
 * juga diperbaiki dari 'bukuIds' (tidak sesuai signature) jadi
 * 'eksemplarIds'.
 *
 * Reader RFID di komputer = USB keyboard-wedge (ketik ke input fokus,
 * seperti barcode scanner), BUKAN endpoint device Attendance Machine -
 * jangan disamakan dengan PerpustakaanDeviceController.
 *
 * Otorisasi: reuse Policy existing, tidak ada permission baru untuk
 * halaman ini sendiri - akses digerbang oleh Create:Peminjaman.
 *
 * Rate limit anti-scan-ganda: barcode yang sama untuk user aktif yang
 * sama tidak boleh diproses ulang dalam window RATE_LIMIT_DETIK detik
 * (mencegah pinjam->kembali->pinjam tidak sengaja akibat eksemplar
 * ter-scan 2x, mis. scanner bouncing atau operator tidak sadar sudah
 * masuk). Diguard via Cache (bukan DB), TTL pendek, tidak butuh migration.
 *
 * TODO: GAP-SPEC - window rate limit di-key per (user_id, eksemplar_id),
 * BUKAN global per eksemplar - asumsi: 2 user berbeda scan eksemplar yang
 * sama beruntun (mis. serah terima cepat) tetap valid, hanya user yang
 * SAMA scan eksemplar yang SAMA berulang yang di-block. Jika ternyata
 * yang diinginkan adalah block global per eksemplar (siapapun
 * operatornya), sesuaikan cache key di bawah (buang bagian user->id).
 *
 * TODO: verifikasi signature terhadap versi package yang terpasang
 * (filament/filament versi sesuai composer.json) - properti $view dan
 * $navigationIcon di bawah mengikuti API Filament v4/v5 (schema-based),
 * cek ulang jika versi terpasang berbeda.
 */
class TransaksiCepat extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Transaksi Cepat';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected string $view = 'filament.pages.transaksi-cepat';

    /**
     * Window rate limit anti-scan-ganda (detik). Lihat catatan class di atas.
     */
    protected const RATE_LIMIT_DETIK = 15;

    public ?string $kartuInput = '';

    public ?string $barcodeInput = '';

    public ?User $user = null;

    public bool $bisaMeminjam = false;

    /**
     * @var array<int, array{barcode: string, judul: string, aksi: string, pesan: string, sukses: bool}>
     */
    public array $riwayatScan = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', Peminjaman::class) ?? false;
    }

    public function scanKartu(): void
    {
        $input = trim((string) $this->kartuInput);
        $this->kartuInput = '';

        if ($input === '') {
            return;
        }

        try {
            $this->user = app(RfidResolverService::class)->resolveUser($input);
        } catch (RuntimeException $e) {
            Notification::make()->danger()->title('User tidak ditemukan')->body($e->getMessage())->send();

            return;
        }

        $this->riwayatScan = [];
        $this->bisaMeminjam = app(PeminjamanService::class)->bisaMeminjam($this->user);

        if ($this->user->status_suspend) {
            Notification::make()
                ->warning()
                ->title('User sedang suspend')
                ->body('User masih bisa mengembalikan buku, tapi tidak bisa meminjam baru sampai Denda lunas.')
                ->send();
        }
    }

    public function scanBarcode(): void
    {
        $barcode = trim((string) $this->barcodeInput);
        $this->barcodeInput = '';

        if ($barcode === '' || ! $this->user) {
            return;
        }

        $eksemplar = Eksemplar::query()->where('barcode', $barcode)->with('buku')->first();

        if (! $eksemplar) {
            $this->tambahRiwayat($barcode, '-', 'error', 'Barcode tidak ditemukan.', false);

            return;
        }

        // Rate limit anti-scan-ganda - dicek SEBELUM logic pinjam/kembali,
        // supaya eksemplar yang sama ter-scan 2x dalam window tidak memicu
        // toggle pinjam->kembali->pinjam yang tidak diinginkan.
        $rateLimitKey = "transaksi-cepat-scan:{$this->user->id}:{$eksemplar->id}";

        if (Cache::has($rateLimitKey)) {
            $this->tambahRiwayat(
                $barcode,
                $eksemplar->buku->judul,
                'ditolak',
                'Eksemplar ini baru saja diproses untuk user ini, tunggu '.self::RATE_LIMIT_DETIK.' detik sebelum scan ulang.',
                false,
            );

            return;
        }

        Cache::put($rateLimitKey, true, self::RATE_LIMIT_DETIK);

        // Deteksi otomatis: ada Peminjaman aktif/terlambat milik user ini
        // untuk eksemplar ini -> kembalikan. Kalau tidak -> pinjam baru.
        $peminjamanAktif = Peminjaman::query()
            ->where('eksemplar_id', $eksemplar->id)
            ->where('user_id', $this->user->id)
            ->whereIn('status', [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat])
            ->first();

        $service = app(PeminjamanService::class);

        try {
            if ($peminjamanAktif) {
                $service->prosesPengembalian(
                    peminjaman: $peminjamanAktif,
                    kondisi: KondisiBuku::Baik, // default, koreksi manual lewat PengembalianResource jika perlu
                    diprosesOleh: auth()->user(),
                );
                $this->tambahRiwayat($barcode, $eksemplar->buku->judul, 'dikembalikan', 'Berhasil dikembalikan (kondisi: baik).', true);
            } else {
                if ($eksemplar->status !== StatusEksemplar::Tersedia) {
                    throw new RuntimeException("Eksemplar barcode '{$eksemplar->barcode}' tidak tersedia (status: {$eksemplar->status->value}).");
                }

                $service->pinjamBuku(
                    user: $this->user,
                    eksemplarIds: [$eksemplar->id],
                    diprosesOleh: auth()->user(),
                );
                $this->tambahRiwayat($barcode, $eksemplar->buku->judul, 'dipinjamkan', 'Berhasil dipinjamkan.', true);
            }
        } catch (RuntimeException $e) {
            // Gagal diproses - buka kembali rate limit supaya operator bisa
            // langsung retry tanpa perlu menunggu window habis.
            Cache::forget($rateLimitKey);
            $this->tambahRiwayat($barcode, $eksemplar->buku->judul, 'error', $e->getMessage(), false);
        }

        $this->bisaMeminjam = app(PeminjamanService::class)->bisaMeminjam($this->user->fresh());
    }

    public function selesai(): void
    {
        $this->user = null;
        $this->riwayatScan = [];
        $this->bisaMeminjam = false;
        $this->kartuInput = '';
        $this->barcodeInput = '';
    }

    protected function tambahRiwayat(string $barcode, string $judul, string $aksi, string $pesan, bool $sukses): void
    {
        array_unshift($this->riwayatScan, [
            'barcode' => $barcode,
            'judul' => $judul,
            'aksi' => $aksi,
            'pesan' => $pesan,
            'sukses' => $sukses,
        ]);
    }
}
