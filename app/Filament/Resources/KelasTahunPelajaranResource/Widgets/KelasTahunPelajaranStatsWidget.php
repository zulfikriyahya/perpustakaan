<?php

namespace App\Filament\Resources\KelasTahunPelajaranResource\Widgets;

use App\Models\KelasTahunPelajaran;
use App\Models\TahunPelajaran;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KelasTahunPelajaranStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', KelasTahunPelajaran::class) ?? false;
    }

    protected function getStats(): array
    {
        $tahunAktif = TahunPelajaran::aktif();

        return [
            Stat::make('Total Instance Kelas', KelasTahunPelajaran::query()->count()),
            Stat::make('Tahun Pelajaran Aktif', $tahunAktif?->nama ?? '-'),
            Stat::make(
                'Total Siswa Aktif (Tahun Berjalan)',
                $tahunAktif
                    ? User::query()->whereHas('kelasTahunPelajaran', fn($q) => $q->where('tahun_pelajaran_id', $tahunAktif->id))->count()
                    : 0
            ),
        ];
    }
}
