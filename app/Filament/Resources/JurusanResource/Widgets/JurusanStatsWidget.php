<?php

namespace App\Filament\Resources\JurusanResource\Widgets;

use App\Models\Jurusan;
use App\Models\Kelas;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class JurusanStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Jurusan::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Jurusan', Jurusan::query()->count()),
            Stat::make('Total Kelas Terhubung', Kelas::query()->whereNotNull('jurusan_id')->count()),
        ];
    }
}
