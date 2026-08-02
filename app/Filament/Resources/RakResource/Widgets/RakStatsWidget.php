<?php

namespace App\Filament\Resources\RakResource\Widgets;

use App\Enums\StatusEksemplar;
use App\Models\Eksemplar;
use App\Models\Rak;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RakStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Rak::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Rak', Rak::query()->count()),
            Stat::make('Total Eksemplar Ter-rak', Eksemplar::query()->whereNotNull('rak_id')->count()),
            Stat::make('Stok Tersedia', Eksemplar::query()->whereNotNull('rak_id')->where('status', StatusEksemplar::Tersedia)->count()),
        ];
    }
}
