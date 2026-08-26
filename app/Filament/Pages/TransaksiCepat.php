<?php

namespace App\Filament\Pages;

use App\Enums\KondisiBuku;
use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Models\Buku;
use App\Models\Eksemplar;
use App\Models\Peminjaman;
use App\Models\User;
use App\Services\BarcodeResolverService;
use App\Services\PeminjamanService;
use App\Services\RfidResolverService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use RuntimeException;

class TransaksiCepat extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Transaksi Cepat';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected string $view = 'filament.pages.transaksi-cepat';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        if (static::class !== self::class) {
            return;
        }

        $this->redirect(Sirkulasi::getUrl());
    }

    protected const RATE_LIMIT_DETIK = 300;

    protected const MIN_KARAKTER_CARI = 2;

    protected const MAX_HASIL_CARI = 8;

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

    /**
     * Live-search fallback User by nama, berbasis isi $kartuInput saat ini.
     * Livewire memanggil ulang otomatis tiap kali $kartuInput berubah
     * karena dipakai langsung di Blade sbg $this->hasilCariUser.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function hasilCariUser(): Collection
    {
        $kata = trim((string) $this->kartuInput);

        if (mb_strlen($kata) < self::MIN_KARAKTER_CARI) {
            return new Collection;
        }

        return User::query()
            ->where('nama', 'like', "%{$kata}%")
            ->orderBy('nama')
            ->limit(self::MAX_HASIL_CARI)
            ->get();
    }

    /**
     * Live-search fallback Buku by judul, berbasis isi $kodeInput saat ini.
     * Hanya buku yang punya minimal satu Eksemplar yang ditampilkan - buku
     * tanpa eksemplar sama sekali tidak mungkin diproses pinjam/kembali.
     *
     * @return Collection<int, Buku>
     */
    #[Computed]
    public function hasilCariBuku(): Collection
    {
        $kata = trim((string) $this->kodeInput);

        if (mb_strlen($kata) < self::MIN_KARAKTER_CARI) {
            return new Collection;
        }

        return Buku::query()
            ->where('judul', 'like', "%{$kata}%")
            ->whereHas('eksemplars')
            ->orderBy('judul')
            ->limit(self::MAX_HASIL_CARI)
            ->get();
    }

    /**
     * Enter ditekan pada input identifikasi user. $inputEksplisit dikirim
     * langsung dari $event.target.value di Blade - hindari race condition
     * debounce (lihat catatan sebelumnya).
     *
     * Input SELALU dikosongkan segera setelah nilai ditangkap, TERLEPAS
     * dari hasil (sukses/gagal) - supaya operator bisa langsung scan/ketik
     * ulang tanpa perlu menghapus manual sisa teks lama. PENGECUALIAN:
     * saat hasil fallback nama >1 (ambigu), input SENGAJA tidak
     * dikosongkan supaya daftar pilihan yang muncul di bawahnya tetap
     * relevan dengan apa yang diketik (operator masih bisa
     * mempersempit kata kunci alih-alih pilih dari daftar).
     */
    public function scanKartu(?string $inputEksplisit = null): void
    {
        $input = trim($inputEksplisit ?? (string) $this->kartuInput);

        if ($input === '') {
            return;
        }

        try {
            $user = app(RfidResolverService::class)->resolveUser($input);
            $this->kartuInput = '';
            $this->muatUser($user);

            return;
        } catch (RuntimeException) {
            // Bukan kartu/NISN/NIP yang valid - lanjut ke fallback pencarian nama.
        }

        // Sinkronkan dulu supaya hasilCariUser() (baca dari $this->kartuInput)
        // memakai nilai yang baru saja ditangkap, bukan nilai lama.
        $this->kartuInput = $input;
        $hasil = $this->hasilCariUser;

        if ($hasil->count() === 1) {
            $this->kartuInput = '';
            $this->muatUser($hasil->first());

            return;
        }

        if ($hasil->count() > 1) {
            Notification::make()
                ->info()
                ->title('Ada beberapa user dengan nama serupa')
                ->body('Pilih salah satu dari daftar di bawah input.')
                ->send();

            return; // input & daftar hasil SENGAJA dibiarkan tampil untuk dipilih manual
        }

        $this->kartuInput = '';
        Notification::make()
            ->danger()
            ->title('User tidak ditemukan')
            ->body("Tidak ada kartu/NISN/NIP/nama yang cocok dengan '{$input}'.")
            ->send();
    }

    /**
     * Dipanggil saat operator klik salah satu hasil fallback pencarian
     * nama. Menutup jalur yang SAMA dengan scanKartu() setelah user
     * berhasil diresolve - lihat muatUser().
     */
    public function pilihUser(string $userId): void
    {
        $user = User::query()->find($userId);

        if (! $user) {
            Notification::make()->danger()->title('User tidak ditemukan')->body('Data mungkin sudah dihapus, coba cari ulang.')->send();

            return;
        }

        $this->kartuInput = '';
        $this->muatUser($user);
    }

    /**
     * Satu sumber kebenaran untuk "apa yang terjadi setelah user berhasil
     * diidentifikasi", dipakai baik oleh jalur exact-match maupun fallback
     * nama (Aturan poin 3 - DRY).
     */
    protected function muatUser(User $user): void
    {
        $this->user = $user;
        $this->riwayatScan = [];
        $this->bisaMeminjam = app(PeminjamanService::class)->bisaMeminjam($user);

        if ($user->status_suspend) {
            Notification::make()
                ->warning()
                ->title('User sedang suspend')
                ->body('User masih bisa mengembalikan buku, tapi tidak bisa meminjam baru sampai Denda lunas.')
                ->send();
        }
    }

    /**
     * Enter ditekan pada input identifikasi buku. $inputEksplisit dikirim
     * langsung dari $event.target.value di Blade - hindari race condition
     * debounce (lihat catatan sebelumnya).
     *
     * Input SELALU dikosongkan segera setelah nilai ditangkap, TERLEPAS
     * dari hasil, KECUALI saat hasil fallback judul >1 (ambigu) - sama
     * seperti scanKartu() di atas.
     *
     * URUTAN RESOLUSI (iterasi ini, BARU ada langkah 3):
     *  1. Exact match barcode Eksemplar.
     *  2. Exact match ISBN Buku -> resolve ke satu Eksemplar.
     *  3. BARU - Fuzzy subsequence match (barcode Eksemplar ATAU ISBN
     *     Buku) - HANYA diproses otomatis jika tepat 1 kandidat unik
     *     ditemukan (lihat cariEksemplarFuzzyAtauNull()). Kalau ambigu
     *     (>1 kandidat) atau tidak ada kandidat sama sekali, TIDAK
     *     ditebak - lanjut ke langkah 4 seperti biasa.
     *  4. Fallback live-search judul Buku (existing, tidak berubah).
     */
    public function scanKode(?string $inputEksplisit = null): void
    {
        $kode = trim($inputEksplisit ?? (string) $this->kodeInput);

        if ($kode === '' || ! $this->user) {
            return;
        }

        $eksemplar = Eksemplar::query()->where('barcode', $kode)->with('buku')->first();

        if (! $eksemplar) {
            $buku = Buku::query()->where('isbn', $kode)->first();
            $eksemplar = $buku ? $this->resolveEksemplarUntukBuku($buku) : null;
        }

        if ($eksemplar) {
            $this->kodeInput = '';
            $this->prosesEksemplar($eksemplar);

            return;
        }

        $eksemplarFuzzy = $this->cariEksemplarFuzzyAtauNull($kode);

        if ($eksemplarFuzzy) {
            $this->kodeInput = '';
            Notification::make()
                ->info()
                ->title('Barcode/ISBN cocok via pencocokan otomatis')
                ->body("Hasil scan '{$kode}' tampak sebagian terpotong, dicocokkan ke eksemplar '{$eksemplarFuzzy->barcode}' ({$eksemplarFuzzy->buku->judul}). Periksa kembali jika ini tidak sesuai.")
                ->send();
            $this->prosesEksemplar($eksemplarFuzzy);

            return;
        }

        // Sinkronkan dulu supaya hasilCariBuku() (baca dari $this->kodeInput)
        // memakai nilai yang baru saja ditangkap, bukan nilai lama.
        $this->kodeInput = $kode;
        $hasil = $this->hasilCariBuku;

        if ($hasil->count() === 1) {
            $this->pilihBuku($hasil->first()->id);

            return;
        }

        if ($hasil->count() > 1) {
            Notification::make()
                ->info()
                ->title('Ada beberapa buku dengan judul serupa')
                ->body('Pilih salah satu dari daftar di bawah input.')
                ->send();

            return; // input & daftar hasil SENGAJA dibiarkan tampil untuk dipilih manual
        }

        $this->kodeInput = '';
        $this->tambahRiwayat($kode, '-', 'error', 'Barcode/ISBN/judul tidak ditemukan.', false);
    }

    /**
     * BARU (iterasi ini) - fuzzy subsequence match sebagai jalan tengah
     * antara exact-match (langkah 1-2 di scanKode()) dan fallback judul
     * (langkah 4). Menangani kasus device scanner tertentu yang terbukti
     * salah mendekode sebagian digit EAN-13 secara konsisten/repeatable
     * (lihat BarcodeResolverService, Aturan poin 3 - DRY, logic
     * subsequence-nya TIDAK diduplikasi di sini).
     *
     * Mengumpulkan kandidat dari DUA sumber (barcode Eksemplar langsung,
     * dan ISBN Buku yang di-resolve ke satu Eksemplar via
     * resolveEksemplarUntukBuku() - method yang SAMA dipakai jalur ISBN
     * exact-match di atas, tidak ada logic pemilihan eksemplar yang
     * terduplikasi), lalu di-dedupe berdasarkan Eksemplar->id.
     *
     * HANYA mengembalikan hasil jika gabungan kandidat unik berjumlah
     * TEPAT 1 - kalau ambigu (>1), method ini SENGAJA mengembalikan null
     * (tidak menebak) supaya caller lanjut ke fallback judul biasa,
     * karena salah pilih buku pada transaksi Peminjaman/Pengembalian
     * jauh lebih berisiko dibanding sekadar gagal-cocok.
     *
     * TODO: GAP-SPEC - kasus ambigu (>1 kandidat fuzzy) saat ini TIDAK
     * ditampilkan sebagai daftar pilihan ke operator (berbeda dengan
     * fallback nama/judul yang punya UI pilihan) - hanya diam-diam
     * dilewati ke fallback judul. Jika operator butuh visibilitas kapan
     * fuzzy match gagal karena ambigu (vs benar-benar tidak ketemu),
     * perlu UI/notifikasi tambahan - belum diimplementasikan di iterasi
     * ini karena belum ada spesifikasi bagaimana daftar itu seharusnya
     * ditampilkan (barcode Eksemplar dan ISBN Buku punya bentuk data
     * berbeda, sehingga tidak bisa langsung reuse UI pilihUser()/pilihBuku()
     * yang ada).
     */
    protected function cariEksemplarFuzzyAtauNull(string $kode): ?Eksemplar
    {
        $service = app(BarcodeResolverService::class);

        $kandidatEksemplar = $service->cariEksemplarFuzzy($kode);
        $kandidatDariBuku = $service->cariBukuFuzzyByIsbn($kode)
            ->map(fn (Buku $buku) => $this->resolveEksemplarUntukBuku($buku))
            ->filter();

        $gabungan = $kandidatEksemplar->concat($kandidatDariBuku)
            ->unique(fn (Eksemplar $eksemplar) => $eksemplar->id);

        return $gabungan->count() === 1 ? $gabungan->first() : null;
    }

    /**
     * Dipanggil saat operator klik salah satu hasil fallback pencarian
     * judul (atau otomatis dari scanKode() saat hasil fallback persis 1).
     * Me-resolve ke SATU Eksemplar (aturan sama seperti jalur ISBN, lihat
     * resolveEksemplarUntukBuku()) lalu diproses lewat jalur bersama
     * prosesEksemplar() - tidak ada logic pinjam/kembali terduplikasi
     * (Aturan poin 3 - DRY).
     */
    public function pilihBuku(string $bukuId): void
    {
        if (! $this->user) {
            return;
        }

        $buku = Buku::query()->find($bukuId);

        if (! $buku) {
            Notification::make()->danger()->title('Buku tidak ditemukan')->body('Data mungkin sudah dihapus, coba cari ulang.')->send();

            return;
        }

        $eksemplar = $this->resolveEksemplarUntukBuku($buku);

        if (! $eksemplar) {
            // Tidak ada Peminjaman aktif utk buku ini, dan tidak
            // ada Eksemplar berstatus Tersedia - beri pesan yang jelas
            // (bukan silent no-op) supaya operator tahu kenapa tidak
            // terjadi apa-apa setelah klik/Enter.
            $this->kodeInput = '';
            $this->tambahRiwayat('-', $buku->judul, 'error', 'Tidak ada eksemplar tersedia untuk buku ini, dan user tidak sedang meminjam eksemplar manapun dari buku ini.', false);

            return;
        }

        $this->kodeInput = '';
        $this->prosesEksemplar($eksemplar);
    }

    /**
     * Logika inti pinjam/kembali per eksemplar (rate limit anti-scan-ganda
     * + deteksi otomatis pinjam vs kembali + panggil PeminjamanService).
     * Dipakai bersama oleh jalur exact-match (scanKode), fuzzy match
     * (cariEksemplarFuzzyAtauNull), dan fallback judul (pilihBuku) -
     * tanpa duplikasi (Aturan poin 3 - DRY).
     */
    protected function prosesEksemplar(Eksemplar $eksemplar): void
    {
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
                            'Jika Anda mengira eksemplar ini seharusnya dikembalikan oleh user ini, periksa apakah barcode/ISBN/judul yang dipilih sesuai dengan yang tadi dipinjam - satu judul buku bisa punya beberapa copy/eksemplar dengan barcode berbeda.'
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
     * Resolve SATU Buku (dari ISBN, dari fuzzy match, maupun dari fallback
     * judul) -> pilih SATU Eksemplar yang relevan. Dipakai jalur ISBN,
     * fuzzy match, dan fallback judul (Aturan poin 3 - DRY).
     *
     * TODO: GAP-SPEC - aturan pemilihan eksemplar saat scan ISBN atau
     * fallback judul (bukan barcode eksemplar spesifik):
     *  1. PENGEMBALIAN: jika user ini punya Peminjaman aktif/terlambat atas
     *     eksemplar manapun dari Buku ini, ambil yang PALING LAMA
     *     dipinjam (created_at terkecil). Asumsi: user jarang pinjam >1
     *     eksemplar dari judul yang sama secara bersamaan; kalau itu
     *     terjadi, operator TIDAK diminta memilih - sistem otomatis pilih
     *     yang tertua. Jika perilaku yang diinginkan adalah selalu minta
     *     scan barcode eksemplar spesifik ketika ambigu (bukan auto-pick),
     *     ubah logic ini untuk melempar RuntimeException alih-alih memilih.
     *  2. PEMINJAMAN BARU: ambil 1 Eksemplar berstatus Tersedia dari Buku
     *     ini secara FIFO (created_at terkecil) - operator TIDAK memilih
     *     eksemplar/copy fisik spesifik, sistem yang menentukan.
     */
    protected function resolveEksemplarUntukBuku(Buku $buku): ?Eksemplar
    {
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
