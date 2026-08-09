<?php

namespace App\Filament\Pages;

use App\Models\Eksemplar;

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
 * LAYOUT (gap iterasi ini) - 2 area:
 *  1. Section utama: form scan (kartu/kode) - identik dengan Transaksi
 *     Cepat, logic diwarisi. Jam analog ditampilkan di sampingnya (posisi
 *     visual jam vs form diatur murni lewat CSS `order` di view, TIDAK
 *     memengaruhi logic apa pun di sini).
 *  2. Riwayat harian (total pengguna hari ini, riwayat 5 transaksi
 *     terbaru, seluruh riwayat hari ini) - method computed-nya DIPINDAH
 *     ke child Livewire component terpisah (App\Livewire\RiwayatSirkulasiHarian,
 *     lihat class tsb) supaya render-nya TIDAK ikut ter-trigger ulang
 *     setiap kali form scan berubah (wire:model.live.debounce di
 *     kartuInput/kodeInput). Ini alasan MURNI PERFORMA (scan terasa
 *     "stuck" sebelumnya karena query riwayat berat ikut jalan tiap
 *     keystroke) - bukan perubahan kebijakan data.
 *
 * KEPUTUSAN YANG TETAP DIPEGANG (dari iterasi sebelumnya, tidak berubah):
 *  - Sumber data riwayat: Peminjaman/Pengembalian (BUKAN Kunjungan) -
 *    dikonfirmasi eksplisit sebelumnya.
 *  - TIDAK ADA polling - riwayat dihitung ulang saat page load/navigate,
 *    dan SEKARANG JUGA saat event 'transaksi-sirkulasi-berhasil'
 *    di-dispatch dari prosesEksemplar() override di bawah (bukan
 *    berdasarkan interval waktu).
 *  - TIDAK ada Widget Filament riwayat kunjungan di halaman ini.
 *  - Halaman ini TETAP TIDAK menulis ke tabel kunjungans/peminjamans di
 *    luar jalur scan yang sudah ada.
 *
 * Otorisasi: reuse canAccess() yang diwarisi dari TransaksiCepat
 * (Create:Peminjaman) - TIDAK ada permission baru. Child Livewire
 * component riwayat TIDAK punya otorisasi sendiri karena hanya dirender
 * di dalam halaman ini yang sudah digerbang canAccess() - TODO: GAP-SPEC,
 * belum diverifikasi eksplisit bahwa component tsb tidak bisa diakses
 * langsung lewat mekanisme lain di luar halaman ini (biasanya aman by
 * default untuk child component non-full-page Livewire).
 *
 * TODO: verifikasi signature terhadap versi package yang terpasang -
 * Livewire\Attributes\Computed/On dipakai sama seperti sebelumnya, cek
 * ulang terhadap composer.lock jika belum diverifikasi sebelumnya.
 */
class Sirkulasi extends TransaksiCepat
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Sirkulasi';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected string $view = 'filament.pages.sirkulasi';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    /**
     * BARU (gap iterasi ini) - halaman Sirkulasi dipakai sebagai layar
     * operasional full-screen (sidebar & topbar sirkulasi minimal, lihat
     * CSS di sirkulasi.blade.php) - heading besar "Sirkulasi" bawaan
     * Filament (di atas logo/topbar) dianggap noise, dihilangkan supaya
     * layar lebih ringkas utk operator. TIDAK memengaruhi navigationLabel
     * (tetap "Sirkulasi" untuk referensi internal, meski
     * shouldRegisterNavigation() sudah false sehingga label itu pun
     * tidak pernah tampil di sidebar).
     *
     * TODO: verifikasi signature terhadap versi package yang terpasang -
     * getHeading(): string|Htmlable|null adalah API dasar
     * Filament\Pages\Page sejak v3, dipertahankan di v4 - cek ulang jika
     * versi filament/filament di composer.lock berbeda dari asumsi ini.
     */
    public function getHeading(): string
    {
        return '';
    }

    /**
     * Override RINGAN dari TransaksiCepat::prosesEksemplar() - HANYA
     * menambahkan dispatch event ke RiwayatSirkulasiHarian (child
     * component) setelah proses pinjam/kembali diproses, supaya section
     * riwayat ikut refresh TANPA polling dan TANPA numpang di siklus
     * render form scan untuk kasus normal (mengetik/scan yang masih
     * mencari kandidat tidak memicu apa pun di sini - hanya dipanggil
     * SETELAH eksemplar benar-benar diproses).
     *
     * TIDAK menduplikasi logic pinjam/kembali - tetap panggil parent
     * (Aturan poin 3, DRY). Dispatch dilakukan baik hasil sukses maupun
     * gagal/ditolak (rate limit) supaya counter riwayat tetap konsisten
     * dengan kondisi terbaru database, bukan berdasarkan asumsi sukses.
     */
    protected function prosesEksemplar(Eksemplar $eksemplar): void
    {
        parent::prosesEksemplar($eksemplar);

        $this->dispatch('transaksi-sirkulasi-berhasil');
    }
}
