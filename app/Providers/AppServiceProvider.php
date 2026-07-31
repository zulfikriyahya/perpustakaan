<?php

namespace App\Providers;

use App\Models\Denda;
use App\Models\Setting;
use App\Models\User;
use App\Observers\DendaObserver;
use App\Observers\SettingObserver;
use App\Observers\UserObserver;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        FilamentShield::enforcePolicies();
        Denda::observe(DendaObserver::class);
        User::observe(UserObserver::class);
        Setting::observe(SettingObserver::class); // invalidasi cache Setting::get()
    }
}
