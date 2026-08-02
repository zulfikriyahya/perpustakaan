<?php

namespace App\Filament\Widgets;

use App\Enums\RoleUser;
use App\Enums\StatusPeminjaman;
use App\Models\Buku;
use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PeminjamanStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getStats(): array
    {
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
                ->color('success'),

            Stat::make('Peminjaman Terlambat', (string) $terlambat)
                ->icon('heroicon-o-clock')
                ->color($terlambat > 0 ? 'danger' : 'gray'),

            Stat::make('Denda Belum Lunas', $jumlahDendaBelumLunas.' transaksi')
                ->icon('heroicon-o-banknotes')
                ->description('Rp '.number_format((float) $nominalDendaBelumLunas, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($jumlahDendaBelumLunas > 0 ? 'warning' : 'gray'),

            Stat::make('Kunjungan Hari Ini', (string) $kunjunganHariIni)
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->color('info'),

            Stat::make('Total Judul Buku', (string) $totalJudulBuku)
                ->icon('heroicon-o-book-open')
                ->color('gray'),

            Stat::make('Total Anggota Aktif', (string) $totalAnggotaAktif)
                ->icon('heroicon-o-users')
                ->color('gray'),
        ];
    }
}
