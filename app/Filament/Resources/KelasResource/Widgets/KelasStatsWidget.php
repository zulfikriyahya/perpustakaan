<?php

namespace App\Filament\Resources\KelasResource\Widgets;

use App\Models\Kelas;
use App\Models\KelasTahunPelajaran;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KelasStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Kelas::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Kelas', Kelas::query()->count()),
            Stat::make('Total Instance Kelas per Tahun Pelajaran', KelasTahunPelajaran::query()->count()),
        ];
    }
}
