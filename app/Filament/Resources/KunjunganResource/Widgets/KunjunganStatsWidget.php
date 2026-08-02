<?php

namespace App\Filament\Resources\KunjunganResource\Widgets;

use App\Models\Kunjungan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class KunjunganStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Kunjungan::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Kunjungan Hari Ini', Kunjungan::query()->whereDate('tanggal', Carbon::today())->count()),
            Stat::make('Kunjungan Bulan Ini', Kunjungan::query()->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->count()),
            Stat::make('Total Kunjungan', Kunjungan::query()->count()),
        ];
    }
}
