<?php

namespace App\Filament\Resources\KategoriResource\Widgets;

use App\Enums\StatusEksemplar;
use App\Models\Eksemplar;
use App\Models\Kategori;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KategoriStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Kategori::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Kategori', Kategori::query()->count()),
            Stat::make('Total Stok Tersedia (Semua Kategori)', Eksemplar::query()->where('status', StatusEksemplar::Tersedia)->count()),
        ];
    }
}
