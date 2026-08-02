<?php

namespace App\Filament\Resources\BukuResource\Widgets;

use App\Enums\StatusEksemplar;
use App\Models\Buku;
use App\Models\Eksemplar;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BukuStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Buku::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Judul Buku', Buku::query()->count()),
            Stat::make('Total Eksemplar', Eksemplar::query()->count()),
            Stat::make('Stok Tersedia', Eksemplar::query()->where('status', StatusEksemplar::Tersedia)->count()),
            Stat::make('Rusak/Hilang', Eksemplar::query()->whereIn('status', [StatusEksemplar::Rusak, StatusEksemplar::Hilang])->count()),
        ];
    }
}
