<?php

namespace App\Filament\Resources\PeminjamanResource\Widgets;

use App\Enums\StatusPeminjaman;
use App\Models\Peminjaman;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PeminjamanOverviewWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Peminjaman::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Aktif', Peminjaman::query()->where('status', StatusPeminjaman::Aktif)->count())->color('success'),
            Stat::make('Terlambat', Peminjaman::query()->where('status', StatusPeminjaman::Terlambat)->count())->color('danger'),
            Stat::make('Selesai', Peminjaman::query()->where('status', StatusPeminjaman::Selesai)->count())->color('gray'),
            Stat::make('Hilang', Peminjaman::query()->where('status', StatusPeminjaman::Hilang)->count())->color('warning'),
        ];
    }
}
