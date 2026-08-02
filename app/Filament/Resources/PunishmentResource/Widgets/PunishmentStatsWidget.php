<?php

namespace App\Filament\Resources\PunishmentResource\Widgets;

use App\Models\Punishment;
use App\Models\PunishmentLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PunishmentStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Punishment::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Punishment', Punishment::query()->count()),
            Stat::make('Punishment Aktif', Punishment::query()->where('aktif', true)->count()),
            Stat::make('Total Diterapkan', PunishmentLog::query()->count()),
        ];
    }
}
