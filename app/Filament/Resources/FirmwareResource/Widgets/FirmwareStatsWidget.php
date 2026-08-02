<?php

namespace App\Filament\Resources\FirmwareResource\Widgets;

use App\Models\FirmwareRelease;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FirmwareStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', FirmwareRelease::class) ?? false;
    }

    protected function getStats(): array
    {
        $versiAktif = FirmwareRelease::query()->where('aktif', true)->orderByDesc('version')->first();

        return [
            Stat::make('Total Rilis', FirmwareRelease::query()->count()),
            Stat::make('Versi Aktif Tertinggi', $versiAktif?->version ?? '-'),
        ];
    }
}
