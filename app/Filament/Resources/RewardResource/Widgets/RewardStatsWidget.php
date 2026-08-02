<?php

namespace App\Filament\Resources\RewardResource\Widgets;

use App\Models\Reward;
use App\Models\RewardLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RewardStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Reward::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Reward', Reward::query()->count()),
            Stat::make('Reward Aktif', Reward::query()->where('aktif', true)->count()),
            Stat::make('Total Diperoleh', RewardLog::query()->count()),
        ];
    }
}
