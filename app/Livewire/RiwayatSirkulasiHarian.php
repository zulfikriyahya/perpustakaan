<?php

namespace App\Livewire;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Child Livewire component TERPISAH dari Sirkulasi (Page) - dipisah dari
 * Filament Page murni untuk alasan PERFORMA, bukan business decision baru:
 * sebelumnya riwayatTransaksiHariIni()/riwayatLengkapHariIni() adalah
 * computed property di Sirkulasi.php, ikut ter-render ULANG setiap kali
 * form scan (kartuInput/kodeInput) berubah (wire:model.live.debounce),
 * karena satu Livewire component = satu siklus render. Ini yang membuat
 * scan terasa "stuck" dibanding Transaksi Cepat.
 *
 * Dengan dipisah jadi child component sendiri, render section 1 & 3 TIDAK
 * lagi terikat ke siklus render form scan Sirkulasi (Page) - tetap
 * dihitung SEKALI per page load/navigate (BUKAN polling, keputusan awal
 * tetap dipegang), hanya sumber render-nya yang dipisah.
 *
 * BUKAN Filament Widget (tidak extends TableWidget/dsb) - murni Livewire
 * Component biasa yang di-mount manual dari sirkulasi.blade.php lewat
 * <livewire:riwayat-sirkulasi-harian /> - konsisten dengan keputusan awal
 * "tidak ada widget riwayat kunjungan di halaman ini" (itu soal WIDGET
 * FILAMENT/kunjungan, beda konteks dengan pemisahan render performa ini).
 */
class RiwayatSirkulasiHarian extends Component
{
    protected const LIMIT_RIWAYAT_TERBARU = 5;

    #[Computed]
    public function totalPenggunaHariIni(): int
    {
        $userIdDariPinjam = Peminjaman::query()
            ->whereDate('tanggal_pinjam', today())
            ->pluck('user_id');

        $userIdDariKembali = Pengembalian::query()
            ->whereDate('tanggal_kembali', today())
            ->join('peminjamans', 'pengembalians.peminjaman_id', '=', 'peminjamans.id')
            ->pluck('peminjamans.user_id');

        return $userIdDariPinjam->merge($userIdDariKembali)->unique()->count();
    }

    #[Computed]
    public function riwayatTransaksiHariIni(): Collection
    {
        return $this->bangunRiwayatHarian(self::LIMIT_RIWAYAT_TERBARU);
    }

    #[Computed]
    public function riwayatLengkapHariIni(): Collection
    {
        return $this->bangunRiwayatHarian(null);
    }

    protected function bangunRiwayatHarian(?int $limit): Collection
    {
        $pinjam = Peminjaman::query()
            ->whereDate('tanggal_pinjam', today())
            ->with(['user', 'eksemplar.buku', 'diprosesOleh'])
            ->get()
            ->map(fn(Peminjaman $p) => [
                'waktu' => $p->created_at,
                'aksi' => 'dipinjamkan',
                'nama_user' => $p->user?->nama ?? '-',
                'judul_buku' => $p->eksemplar?->buku?->judul ?? '-',
                'diproses_oleh' => $p->diprosesOleh?->nama ?? '-',
            ]);

        $kembali = Pengembalian::query()
            ->whereDate('tanggal_kembali', today())
            ->with(['peminjaman.user', 'peminjaman.eksemplar.buku', 'diprosesOleh'])
            ->get()
            ->map(fn(Pengembalian $pg) => [
                'waktu' => $pg->created_at,
                'aksi' => 'dikembalikan',
                'nama_user' => $pg->peminjaman?->user?->nama ?? '-',
                'judul_buku' => $pg->peminjaman?->eksemplar?->buku?->judul ?? '-',
                'diproses_oleh' => $pg->diprosesOleh?->nama ?? '-',
            ]);

        $gabungan = $pinjam->merge($kembali)->sortByDesc('waktu')->values();

        return $limit ? $gabungan->take($limit) : $gabungan;
    }

    /**
     * Dipanggil dari Sirkulasi (Page) lewat $dispatch setiap kali
     * prosesEksemplar() berhasil (pinjam/kembali) - supaya section 1 & 3
     * ikut ter-update tanpa perlu polling, TAPI tanpa numpang di siklus
     * render form scan untuk kasus normal (mengetik/scan yang gagal/masih
     * mencari tidak memicu query berat ini).
     */
    #[\Livewire\Attributes\On('transaksi-sirkulasi-berhasil')]
    public function refreshRiwayat(): void
    {
        unset($this->totalPenggunaHariIni, $this->riwayatTransaksiHariIni, $this->riwayatLengkapHariIni);
    }

    public function render()
    {
        return view('livewire.riwayat-sirkulasi-harian');
    }
}
