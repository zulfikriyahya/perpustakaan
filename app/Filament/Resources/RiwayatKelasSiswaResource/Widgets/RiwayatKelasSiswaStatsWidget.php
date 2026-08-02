<?php

namespace App\Filament\Resources\RiwayatKelasSiswaResource\Widgets;

use App\Enums\StatusRiwayatKelas;
use App\Models\RiwayatKelasSiswa;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RiwayatKelasSiswaStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', RiwayatKelasSiswa::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Riwayat', RiwayatKelasSiswa::query()->count()),
            Stat::make('Naik Kelas', RiwayatKelasSiswa::query()->where('status', StatusRiwayatKelas::Naik)->count()),
            Stat::make('Tinggal Kelas', RiwayatKelasSiswa::query()->where('status', StatusRiwayatKelas::Tinggal)->count()),
            Stat::make('Lulus', RiwayatKelasSiswa::query()->where('status', StatusRiwayatKelas::Lulus)->count()),
        ];
    }
}
