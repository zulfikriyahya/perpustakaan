<?php

namespace App\Filament\Pages;

use App\Enums\SourceKunjungan;
use App\Models\Eksemplar;
use App\Models\Kunjungan;
use App\Models\User;
use App\Services\KunjunganService;
use App\Services\PointService;
use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;

/**
 * Halaman "Sirkulasi": DUPLIKAT fungsi & fitur Transaksi Cepat, tapi TANPA
 * sidebar dan HANYA diakses lewat tombol di topbar (kanan atas) - TIDAK
 * didaftarkan ke navigasi sidebar (lihat shouldRegisterNavigation()).
 *
 * Extends TransaksiCepat, BUKAN copy-paste class PHP-nya, supaya seluruh
 * logic bisnis (scanKartu, scanKode, muatUser, prosesEksemplar, rate
 * limit anti-scan-ganda, fallback pencarian nama/judul, dst.) TIDAK
 * terduplikasi (Aturan poin 3 - DRY).
 *
 * FITUR BARU (iterasi ini) - AUTO KUNJUNGAN SAAT TAP PERTAMA HARI INI:
 * di-hook lewat override muatUser() (SATU titik yang dipanggil baik dari
 * scanKartu() exact-match maupun pilihUser() fallback nama di parent
 * TransaksiCepat - Aturan poin 3, DRY, tidak menduplikasi logic resolve
 * user).
 *
 * Alur:
 *  - User BELUM punya Kunjungan hari ini -> catat Kunjungan (source
 *    Rfid - tetap tap kartu RFID fisik via reader keyboard-wedge yang
 *    sama dengan RfidResolverService, HANYA jalur transportnya beda dari
 *    Attendance Machine/ESP32; dikonfirmasi eksplisit BUKAN Manual),
 *    trigger PointService::catatEvent(Kunjungan) + notifikasi WhatsApp
 *    (pola SAMA seperti PerpustakaanDeviceController::prosesSatuTap() /
 *    kirimLangsung()) - lalu tampilkan modal "Selamat datang" dan
 *    KEMBALI ke state standby Sirkulasi (TIDAK lanjut memuat panel
 *    pinjam/kembali buku - parent::muatUser() TIDAK dipanggil).
 *  - User SUDAH punya Kunjungan hari ini -> panggil parent::muatUser()
 *    seperti biasa, lanjut alur sirkulasi pinjam/kembali (tidak berubah).
 *
 * TODO: GAP-SPEC - berbeda dari PerpustakaanDeviceController, jalur ini
 * TIDAK ikut membuat Transaksi log (catatTransaksiKunjungan() di device
 * controller) - hanya Kunjungan + Point + WA yang dikonfirmasi eksplisit
 * untuk fitur ini. Jika Transaksi log jenis 'kunjungan' juga diharapkan
 * konsisten dari jalur manual ini, perlu konfirmasi terpisah (menyentuh
 * konsistensi laporan/Export Transaksi).
 *
 * TODO: ASUMSI - label 'device' pada variabel WhatsApp (event
 * 'kunjungan_tercatat') diisi string statis 'Sirkulasi (Manual)' karena
 * tidak ada device_id sesungguhnya di jalur ini (bukan tap fisik
 * Attendance Machine) - sesuaikan jika template WA di gateway zedlabs
 * mengasumsikan format device_id tertentu (mis. MAC address).
 *
 * TODO: GAP-SPEC - durasi modal "Selamat datang" auto-dismiss diasumsikan
 * 4 detik (lihat DURASI_MODAL_SELAMAT_DATANG_MS) - sesuaikan jika operator
 * di lapangan merasa terlalu cepat/lambat.
 */
class Sirkulasi extends TransaksiCepat
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Sirkulasi';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected string $view = 'filament.pages.sirkulasi';

    /**
     * Durasi tampil modal "Selamat datang" sebelum otomatis tertutup
     * (dibaca dari sisi JS/Alpine di sirkulasi.blade.php).
     */
    public const DURASI_MODAL_SELAMAT_DATANG_MS = 4000;

    /**
     * State modal "Selamat datang, {nama}" - true saat Kunjungan baru
     * saja tercatat lewat tap pertama hari ini.
     */
    public bool $tampilkanModalSelamatDatang = false;

    public ?string $namaModalSelamatDatang = null;

    public ?int $pointModalSelamatDatang = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getHeading(): string
    {
        return '';
    }

    /**
     * Override dari TransaksiCepat::muatUser() - hook TUNGGAL untuk fitur
     * auto-Kunjungan (lihat docblock class). Dipanggil dari scanKartu()
     * (exact-match kartu/NISN) maupun pilihUser() (fallback nama) yang
     * diwarisi dari parent - TIDAK ada logic resolve user yang
     * diduplikasi di sini (Aturan poin 3, DRY).
     */
    protected function muatUser(User $user): void
    {
        $sudahKunjunganHariIni = Kunjungan::query()
            ->where('user_id', $user->id)
            ->where('tanggal', today())
            ->exists();

        if ($sudahKunjunganHariIni) {
            parent::muatUser($user);

            return;
        }

        $this->catatKunjunganPertama($user);
    }

    /**
     * Satu sumber kebenaran pencatatan Kunjungan tap-pertama dari halaman
     * Sirkulasi - SEKARANG delegasi penuh ke KunjunganService (Point +
     * Transaksi log + WhatsApp), SAMA PERSIS dengan jalur
     * PerpustakaanDeviceController.
     *
     * source = SourceKunjungan::Rfid - tetap tap kartu RFID fisik via
     * reader keyboard-wedge yang sama dengan RfidResolverService, hanya
     * jalur transportnya beda dari Attendance Machine/ESP32.
     *
     * CATATAN: $user->fresh() WAJIB dipanggil setelah catatKunjungan() -
     * PointService::catatEvent() mengubah akumulasi_point lewat
     * increment()+refresh() pada instance User yang DIPEGANG SERVICE
     * (parameter $user yang di-pass ke KunjunganService::catatKunjungan()),
     * bukan instance $user milik page ini - properti $user->akumulasi_point
     * di sini TETAP nilai lama tanpa fresh() ulang.
     */
    protected function catatKunjunganPertama(User $user): void
    {
        try {
            app(KunjunganService::class)->catatKunjungan(
                user: $user,
                source: SourceKunjungan::Rfid,
                sumberLabel: 'Sirkulasi (RFID Reader Web)',
            );
        } catch (QueryException $e) {
            // Race condition dengan unique index kunjungans_unik_aktif_unique
            // (mis. 2 klik/tap beruntun) - anggap sudah tercatat, tetap
            // tampilkan modal supaya operator/siswa tidak bingung tidak ada
            // respons sama sekali.
        }

        $this->tampilkanModalKunjungan($user->nama, $user->fresh()->akumulasi_point);
    }

    protected function tampilkanModalKunjungan(string $nama, int $totalPoint): void
    {
        $this->namaModalSelamatDatang = $nama;
        $this->pointModalSelamatDatang = $totalPoint;
        $this->tampilkanModalSelamatDatang = true;
    }

    /**
     * Dipanggil dari JS (Alpine setTimeout) setelah
     * DURASI_MODAL_SELAMAT_DATANG_MS - menutup modal dan mengembalikan
     * halaman ke state standby (tidak memuat user ke panel sirkulasi,
     * karena parent::muatUser() memang tidak pernah dipanggil untuk
     * tap-pertama ini).
     */
    public function tutupModalSelamatDatang(): void
    {
        $this->tampilkanModalSelamatDatang = false;
        $this->namaModalSelamatDatang = null;
        $this->pointModalSelamatDatang = null;
    }

    protected function prosesEksemplar(Eksemplar $eksemplar): void
    {
        parent::prosesEksemplar($eksemplar);

        $this->dispatch('transaksi-sirkulasi-berhasil');
    }

    /**
     * Override dari TransaksiCepat::tambahRiwayat() - TETAP panggil
     * parent (Aturan poin 3, DRY) supaya $riwayatScan tetap terisi untuk
     * statistik ringkasan (Dipinjamkan/Dikembalikan/Gagal, lihat
     * sirkulasi.blade.php) - HANYA menambahkan toast notifikasi sebagai
     * pengganti daftar list "Riwayat Scan (sesi ini)" yang dihapus dari
     * tampilan (dikonfirmasi eksplisit: statistik ringkasan tetap
     * dipertahankan, hanya list-nya yang jadi toast).
     */
    protected function tambahRiwayat(string $barcode, string $judul, string $aksi, string $pesan, bool $sukses): void
    {
        parent::tambahRiwayat($barcode, $judul, $aksi, $pesan, $sukses);

        if (! $sukses) {
            Notification::make()
                ->danger()
                ->title($judul !== '-' ? $judul : 'Gagal diproses')
                ->body($pesan)
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title($aksi === 'dipinjamkan' ? 'Berhasil dipinjamkan' : 'Berhasil dikembalikan')
            ->body($judul)
            ->send();
    }
}
