<?php

namespace App\Filament\Resources\TahunPelajaranResource\Widgets;

use App\Models\TahunPelajaran;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TahunPelajaranStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', TahunPelajaran::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Tahun Pelajaran', TahunPelajaran::query()->count()),
            Stat::make('Tahun Pelajaran Aktif', TahunPelajaran::aktif()?->nama ?? '-'),
        ];
    }
}
