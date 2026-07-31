<?php

namespace App\Services;

use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Point;
use Illuminate\Support\Carbon;

/**
 * Satu sumber kebenaran agregasi data untuk Laporan Bulanan (Aturan poin 3)
 * - dipanggil dari LaporanBulanan Page, jangan duplikasi query di tempat lain.
 *
 * TODO: GAP-SPEC - filter tanggal per domain memakai kolom "kejadian"
 * masing-masing (tanggal_pinjam, tanggal_kembali, created_at untuk
 * Denda/Point, tanggal untuk Kunjungan) - bukan tanggal_lunas untuk Denda.
 * Perlu dikonfirmasi jika laporan dimaksudkan sebagai laporan kas/arus
 * pemasukan (yang mestinya pakai tanggal_lunas), bukan laporan aktivitas.
 */
class LaporanBulananService
{
    public function generate(int $bulan, int $tahun): array
    {
        $awal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'periode_label' => $awal->translatedFormat('F Y'),
            'peminjaman' => $this->dataPeminjaman($awal, $akhir),
            'pengembalian' => $this->dataPengembalian($awal, $akhir),
            'denda' => $this->dataDenda($awal, $akhir),
            'kunjungan' => $this->dataKunjungan($awal, $akhir),
            'point' => $this->dataPoint($awal, $akhir),
        ];
    }

    protected function dataPeminjaman(Carbon $awal, Carbon $akhir): array
    {
        $records = Peminjaman::query()
            ->with(['user', 'buku'])
            ->whereBetween('tanggal_pinjam', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal_pinjam')
            ->get();

        return [
            'total' => $records->count(),
            'per_status' => $records->groupBy(fn ($r) => $r->status->value)->map->count(),
            'detail' => $records,
        ];
    }

    protected function dataPengembalian(Carbon $awal, Carbon $akhir): array
    {
        $records = Pengembalian::query()
            ->with(['peminjaman.user', 'peminjaman.buku'])
            ->whereBetween('tanggal_kembali', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal_kembali')
            ->get();

        return [
            'total' => $records->count(),
            'per_kondisi' => $records->groupBy(fn ($r) => $r->kondisi->value)->map->count(),
            'detail' => $records,
        ];
    }

    protected function dataDenda(Carbon $awal, Carbon $akhir): array
    {
        $records = Denda::query()
            ->with(['user', 'peminjaman.buku'])
            ->whereBetween('created_at', [$awal, $akhir])
            ->orderBy('created_at')
            ->get();

        return [
            'total' => $records->count(),
            'total_nominal' => $records->sum('nominal'),
            'total_nominal_lunas' => $records->where('status_lunas', true)->sum('nominal'),
            'total_nominal_belum_lunas' => $records->where('status_lunas', false)->sum('nominal'),
            'per_tipe' => $records->groupBy(fn ($r) => $r->tipe->value)->map(fn ($g) => [
                'jumlah' => $g->count(),
                'nominal' => $g->sum('nominal'),
            ]),
            'detail' => $records,
        ];
    }

    protected function dataKunjungan(Carbon $awal, Carbon $akhir): array
    {
        $records = Kunjungan::query()
            ->with('user')
            ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->orderBy('tanggal')
            ->get();

        return [
            'total' => $records->count(),
            'user_unik' => $records->pluck('user_id')->unique()->count(),
            'per_source' => $records->groupBy(fn ($r) => $r->source->value)->map->count(),
            'detail' => $records,
        ];
    }

    protected function dataPoint(Carbon $awal, Carbon $akhir): array
    {
        $records = Point::query()
            ->with('user')
            ->whereBetween('created_at', [$awal, $akhir])
            ->orderBy('created_at')
            ->get();

        return [
            'total_transaksi' => $records->count(),
            'total_nilai' => $records->sum('nilai'),
            'per_event' => $records->groupBy(fn ($r) => $r->event_type->value)->map(fn ($g) => [
                'jumlah' => $g->count(),
                'total_nilai' => $g->sum('nilai'),
            ]),
            'detail' => $records,
        ];
    }
}
