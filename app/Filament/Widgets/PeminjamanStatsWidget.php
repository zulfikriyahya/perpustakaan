<?php

namespace App\Filament\Widgets;

use App\Enums\StatusPeminjaman;
use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ringkasan operasional harian - untuk Admin & Pustakawan.
 * TODO: verifikasi signature terhadap versi package yang terpasang
 * (filament/filament ^5.7) - namespace Filament\Widgets\StatsOverviewWidget
 * diasumsikan stabil sejak v3, belum dicek ulang untuk v5.
 */
class PeminjamanStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

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

        return [
            Stat::make('Peminjaman Aktif', (string) $aktif)
                ->color('success'),

            Stat::make('Peminjaman Terlambat', (string) $terlambat)
                ->color($terlambat > 0 ? 'danger' : 'gray'),

            Stat::make('Denda Belum Lunas', $jumlahDendaBelumLunas.' transaksi')
                ->description('Rp '.number_format((float) $nominalDendaBelumLunas, 0, ',', '.'))
                ->color($jumlahDendaBelumLunas > 0 ? 'warning' : 'gray'),

            Stat::make('Kunjungan Hari Ini', (string) $kunjunganHariIni)
                ->color('info'),
        ];
    }
}
