<?php

namespace App\Filament\Resources\LevelBadgeLogResource\Widgets;

use App\Models\LevelBadgeLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LevelBadgeLogStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', LevelBadgeLog::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Kenaikan Badge', LevelBadgeLog::query()->count()),
            Stat::make('Bulan Ini', LevelBadgeLog::query()->whereMonth('tanggal_didapat', now()->month)->whereYear('tanggal_didapat', now()->year)->count()),
        ];
    }
}
