<?php

namespace App\Filament\Resources\RewardLogResource\Widgets;

use App\Models\RewardLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RewardLogStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', RewardLog::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Diperoleh', RewardLog::query()->count()),
            Stat::make('Bulan Ini', RewardLog::query()->whereMonth('tanggal_didapat', now()->month)->whereYear('tanggal_didapat', now()->year)->count()),
        ];
    }
}
