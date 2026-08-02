<?php

namespace App\Filament\Resources\LevelBadgeResource\Widgets;

use App\Models\LevelBadge;
use App\Models\LevelBadgeLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LevelBadgeStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', LevelBadge::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Level Badge', LevelBadge::query()->count()),
            Stat::make('Total Kenaikan Badge Tercatat', LevelBadgeLog::query()->count()),
        ];
    }
}
