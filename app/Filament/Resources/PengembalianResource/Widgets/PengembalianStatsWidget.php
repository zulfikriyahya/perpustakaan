<?php

namespace App\Filament\Resources\PengembalianResource\Widgets;

use App\Enums\KondisiBuku;
use App\Models\Pengembalian;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PengembalianStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Pengembalian::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Pengembalian', Pengembalian::query()->count()),
            Stat::make('Kondisi Baik', Pengembalian::query()->where('kondisi', KondisiBuku::Baik)->count())->color('success'),
            Stat::make('Kondisi Rusak', Pengembalian::query()->where('kondisi', KondisiBuku::Rusak)->count())->color('warning'),
            Stat::make('Kondisi Hilang', Pengembalian::query()->where('kondisi', KondisiBuku::Hilang)->count())->color('danger'),
        ];
    }
}
