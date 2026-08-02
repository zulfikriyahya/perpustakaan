<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Enums\RoleUser;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', User::class) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total User', User::query()->count()),
            Stat::make('Siswa', User::query()->where('role', RoleUser::Siswa)->count()),
            Stat::make('Pegawai', User::query()->where('role', RoleUser::Pegawai)->count()),
            Stat::make('Suspend Aktif', User::query()->where('status_suspend', true)->count())->color('danger'),
        ];
    }
}
