<?php

namespace App\Filament\Resources\DendaResource\Widgets;

use App\Enums\StatusRefund;
use App\Models\Denda;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DendaStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Denda::class) ?? false;
    }

    protected function getStats(): array
    {
        $belumLunas = (float) Denda::query()->where('status_lunas', false)->sum('nominal');

        return [
            Stat::make('Total Denda', Denda::query()->count()),
            Stat::make('Belum Lunas (Rp)', 'Rp'.number_format($belumLunas, 0, ',', '.'))->color('danger'),
            Stat::make('Perlu Refund', Denda::query()->where('status_refund', StatusRefund::PerluRefund)->count())->color('warning'),
        ];
    }
}
