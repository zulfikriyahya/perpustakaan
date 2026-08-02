<?php

namespace App\Filament\Widgets;

use App\Enums\RoleUser;
use App\Enums\StatusPeminjaman;
use App\Models\Buku;
use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\SnapshotHarian;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

/**
 * BARU (iterasi ini): 6 chart trend sebelumnya menghitung ulang query
 * agregat 7x per metrik SETIAP dashboard dibuka (~35-42 query total).
 * Sekarang trend dibaca dari SnapshotHarian - 1 query untuk 7 hari
 * sekaligus, diisi harian oleh ProsesCronHarianPerpustakaan (Aturan
 * poin 3/9 - performa). Nilai "sekarang" (angka besar di tiap Stat,
 * BUKAN chart-nya) tetap dihitung live seperti sebelumnya - itu memang
 * harus real-time, bukan snapshot kemarin.
 */
class PeminjamanStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    /**
     * Snapshot 7 hari terakhir (termasuk hari ini), di-key per format
     * tanggal singkat (d/m) - dimuat SEKALI dan dipakai ulang oleh semua
     * Stat di getStats(), bukan query per Stat (Aturan poin 3).
     *
     * @return Collection<string, SnapshotHarian>
     */
    private function muatSnapshot7Hari(): Collection
    {
        $mulai = Carbon::now()->subDays(6)->startOfDay();
        $akhir = Carbon::now()->startOfDay();

        return SnapshotHarian::query()
            ->whereBetween('tanggal', [$mulai->toDateString(), $akhir->toDateString()])
            ->get()
            ->keyBy(fn (SnapshotHarian $s) => $s->tanggal->format('d/m'));
    }

    /**
     * @return array<string, float>
     */
    private function trendDariSnapshot(Collection $snapshots, string $kolom): array
    {
        $hasil = [];

        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::now()->subDays($i)->startOfDay();
            $label = $tanggal->format('d/m');
            $hasil[$label] = (float) ($snapshots[$label]?->{$kolom} ?? 0);
        }

        return $hasil;
    }

    protected function getStats(): array
    {
        $snapshots = $this->muatSnapshot7Hari();

        $aktif = Peminjaman::query()->where('status', StatusPeminjaman::Aktif)->count();
        $terlambat = Peminjaman::query()->where('status', StatusPeminjaman::Terlambat)->count();

        $dendaBelumLunas = Denda::query()->where('status_lunas', false);
        $jumlahDendaBelumLunas = $dendaBelumLunas->count();
        $nominalDendaBelumLunas = (clone $dendaBelumLunas)->sum('nominal');

        $kunjunganHariIni = Kunjungan::query()->whereDate('tanggal', now()->toDateString())->count();

        $totalJudulBuku = Buku::query()->count();
        $totalAnggotaAktif = User::query()
            ->where('status_suspend', false)
            ->whereNotIn('role', [RoleUser::Admin, RoleUser::Pustakawan])
            ->count();

        return [
            Stat::make('Peminjaman Aktif', (string) $aktif)
                ->icon('heroicon-o-book-open')
                ->color('success')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'peminjaman_baru'))),

            Stat::make('Peminjaman Terlambat', (string) $terlambat)
                ->icon('heroicon-o-clock')
                ->color($terlambat > 0 ? 'danger' : 'gray')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'peminjaman_terlambat'))),

            Stat::make('Denda Belum Lunas', $jumlahDendaBelumLunas.' transaksi')
                ->icon('heroicon-o-banknotes')
                ->description('Rp '.number_format((float) $nominalDendaBelumLunas, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($jumlahDendaBelumLunas > 0 ? 'warning' : 'gray')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'denda_baru'))),

            Stat::make('Kunjungan Hari Ini', (string) $kunjunganHariIni)
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->color('info')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'kunjungan'))),

            Stat::make('Total Judul Buku', (string) $totalJudulBuku)
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'total_judul_buku'))),

            Stat::make('Total Anggota Aktif', (string) $totalAnggotaAktif)
                ->icon('heroicon-o-users')
                ->color('gray')
                ->chart(array_values($this->trendDariSnapshot($snapshots, 'total_anggota_aktif'))),
        ];
    }
}
