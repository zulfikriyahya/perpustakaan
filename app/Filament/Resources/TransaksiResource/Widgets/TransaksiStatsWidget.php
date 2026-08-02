<?php

namespace App\Filament\Resources\TransaksiResource\Widgets;

use App\Enums\JenisTransaksi;
use App\Models\Transaksi;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransaksiStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Transaksi::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Transaksi', Transaksi::query()->count()),
            Stat::make('Peminjaman', Transaksi::query()->where('jenis', JenisTransaksi::Peminjaman)->count()),
            Stat::make('Kunjungan', Transaksi::query()->where('jenis', JenisTransaksi::Kunjungan)->count()),
            Stat::make('Pembayaran Denda', Transaksi::query()->where('jenis', JenisTransaksi::PembayaranDenda)->count()),
        ];
    }
}
