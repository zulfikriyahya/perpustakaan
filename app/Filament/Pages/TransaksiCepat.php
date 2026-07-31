<?php

namespace App\Filament\Pages;

use App\Enums\KondisiBuku;
use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Models\Buku;
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
 * EKSEMPLAR atau ISBN buku satu per satu -> sistem OTOMATIS deteksi
 * pinjam/kembali per eksemplar, diproses langsung tiap scan (TIDAK
 * dikumpulkan dulu, sesuai keputusan QA). Seluruh logic bisnis (limit,
 * stok, Denda, Point, WA) tetap lewat PeminjamanService - halaman ini
 * murni orkestrasi UI (Aturan poin 3).
 *
 * FITUR BARU (iterasi ini): sebelumnya input hanya dicocokkan ke
 * Eksemplar.barcode. Sekarang jika tidak match barcode eksemplar manapun,
 * input dicoba resolve sebagai Buku.isbn (lihat resolveEksemplarDariIsbn())
 * - karena satu ISBN bisa punya banyak Eksemplar/copy fisik, sistem
 * otomatis memilih eksemplar yang relevan (lihat TODO: GAP-SPEC di
 * method tersebut untuk aturan pemilihannya). Property/method di-rename
 * dari $barcodeInput/scanBarcode() menjadi $kodeInput/scanKode() karena
 * sekarang menerima barcode ATAU ISBN, ditelusuri ke seluruh pemakaian
 * termasuk blade (Aturan poin 11).
 *
 * BUG FIX (iterasi sebelumnya): scan barcode sudah dipindah dari query
 * Buku.barcode/Peminjaman.buku_id (sudah tidak ada sejak migration
 * 2026_08_02_000002-000004) ke Eksemplar.barcode/Peminjaman.eksemplar_id.
 *
 * Reader RFID di komputer = USB keyboard-wedge (ketik ke input fokus,
 * seperti barcode scanner), BUKAN endpoint device Attendance Machine -
 * jangan disamakan dengan PerpustakaanDeviceController.
 *
 * Otorisasi: reuse Policy existing, tidak ada permission baru untuk
 * halaman ini sendiri - akses digerbang oleh Create:Peminjaman.
 *
 * Rate limit anti-scan-ganda: eksemplar yang sama (setelah diresolve dari
 * barcode ATAU ISBN) untuk user aktif yang sama tidak boleh diproses ulang
 * dalam window RATE_LIMIT_DETIK detik.
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
    protected const RATE_LIMIT_DETIK = 60;

    public ?string $kartuInput = '';

    public ?string $kodeInput = '';

    public ?User $user = null;

    public bool $bisaMeminjam = false;

    /**
     * @var array<int, array{barcode: string, judul: string, aksi: string,pesan: string, sukses: bool}>
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

    public function scanKode(): void
    {
        $kode = trim((string) $this->kodeInput);
        $this->kodeInput = '';

        if ($kode === '' || ! $this->user) {
            return;
        }

        $eksemplar = Eksemplar::query()->where('barcode', $kode)->with('buku')->first();

        if (! $eksemplar) {
            $eksemplar = $this->resolveEksemplarDariIsbn($kode);
        }

        if (! $eksemplar) {
            $this->tambahRiwayat($kode, '-', 'error', 'Barcode/ISBN tidak ditemukan.', false);

            return;
        }

        // Rate limit anti-scan-ganda - dicek SEBELUM logic pinjam/kembali,
        // supaya eksemplar yang sama (baik diresolve dari barcode maupun
        // ISBN) ter-scan 2x dalam window tidak memicu toggle
        // pinjam->kembali->pinjam yang tidak diinginkan.
        $rateLimitKey = "transaksi-cepat-scan:{$this->user->id}:{$eksemplar->id}";

        if (Cache::has($rateLimitKey)) {
            $this->tambahRiwayat(
                $eksemplar->barcode,
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
                $this->tambahRiwayat($eksemplar->barcode, $eksemplar->buku->judul, 'dikembalikan', 'Berhasil dikembalikan (kondisi: baik).', true);
            } else {
                if ($eksemplar->status !== StatusEksemplar::Tersedia) {
                    // UX: pesan dipertegas - ini SANGAT mungkin eksemplar/copy
                    // fisik lain dari judul yang sama, sedang dipinjam user lain,
                    // bukan berarti transaksi user saat ini bermasalah/bug.
                    throw new RuntimeException(
                        "Eksemplar barcode '{$eksemplar->barcode}' ({$eksemplar->buku->judul}) sedang tidak tersedia (status: {$eksemplar->status->value}). ".
                            'Jika Anda mengira eksemplar ini seharusnya dikembalikan oleh user ini, periksa apakah barcode/ISBN yang di-scan sesuai dengan yang tadi dipinjam - satu judul buku bisa punya beberapa copy/eksemplar dengan barcode berbeda.'
                    );
                }

                $service->pinjamBuku(
                    user: $this->user,
                    eksemplarIds: [$eksemplar->id],
                    diprosesOleh: auth()->user(),
                );
                $this->tambahRiwayat($eksemplar->barcode, $eksemplar->buku->judul, 'dipinjamkan', 'Berhasil dipinjamkan.', true);
            }
        } catch (RuntimeException $e) {
            // Gagal diproses - buka kembali rate limit supaya operator bisa
            // langsung retry tanpa perlu menunggu window habis.
            Cache::forget($rateLimitKey);
            $this->tambahRiwayat($eksemplar->barcode, $eksemplar->buku->judul, 'error', $e->getMessage(), false);
        }

        $this->bisaMeminjam = app(PeminjamanService::class)->bisaMeminjam($this->user->fresh());
    }

    /**
     * Resolve input sebagai ISBN Buku (dipanggil ketika input tidak match
     * barcode Eksemplar manapun) -> pilih SATU Eksemplar yang relevan.
     *
     * TODO: GAP-SPEC - aturan pemilihan eksemplar saat scan ISBN (bukan
     * barcode eksemplar spesifik):
     *  1. PENGEMBALIAN: jika user ini punya Peminjaman aktif/terlambat atas
     *     eksemplar manapun dari Buku ber-ISBN ini, ambil yang PALING LAMA
     *     dipinjam (created_at terkecil). Asumsi: user jarang pinjam >1
     *     eksemplar dari judul yang sama secara bersamaan; kalau itu
     *     terjadi, operator TIDAK diminta memilih - sistem otomatis pilih
     *     yang tertua. Jika perilaku yang diinginkan adalah selalu minta
     *     scan barcode eksemplar spesifik ketika ambigu (bukan auto-pick),
     *     ubah logic ini untuk melempar RuntimeException alih-alih memilih.
     *  2. PEMINJAMAN BARU: ambil 1 Eksemplar berstatus Tersedia dari Buku
     *     ini secara FIFO (created_at terkecil) - operator TIDAK memilih
     *     eksemplar/copy fisik spesifik, sistem yang menentukan. Ini
     *     konsisten dengan premis gap ("barcode identik dengan ISBN yang
     *     dipinjam" - dianggap tidak ada preferensi copy tertentu).
     */
    protected function resolveEksemplarDariIsbn(string $isbn): ?Eksemplar
    {
        $buku = Buku::query()->where('isbn', $isbn)->first();

        if (! $buku) {
            return null;
        }

        $eksemplarDipinjamUser = Eksemplar::query()
            ->where('buku_id', $buku->id)
            ->whereHas('peminjamans', fn ($q) => $q
                ->where('user_id', $this->user->id)
                ->whereIn('status', [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat]))
            ->with('buku')
            ->oldest('created_at')
            ->first();

        if ($eksemplarDipinjamUser) {
            return $eksemplarDipinjamUser;
        }

        return Eksemplar::query()
            ->where('buku_id', $buku->id)
            ->where('status', StatusEksemplar::Tersedia)
            ->with('buku')
            ->oldest('created_at')
            ->first();
    }

    public function selesai(): void
    {
        $this->user = null;
        $this->riwayatScan = [];
        $this->bisaMeminjam = false;
        $this->kartuInput = '';
        $this->kodeInput = '';
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
