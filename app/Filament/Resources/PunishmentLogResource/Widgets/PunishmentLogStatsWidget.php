<?php

namespace App\Filament\Resources\PunishmentLogResource\Widgets;

use App\Models\PunishmentLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PunishmentLogStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', PunishmentLog::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Diterapkan', PunishmentLog::query()->count()),
            Stat::make('Sedang Berlaku', PunishmentLog::query()
                ->where(fn ($q) => $q->whereNull('tanggal_berakhir')->orWhere('tanggal_berakhir', '>', now()))
                ->count()),
        ];
    }
}
