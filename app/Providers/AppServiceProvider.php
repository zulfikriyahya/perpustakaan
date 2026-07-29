<?php

namespace App\Providers;

use App\Models\Denda;
use App\Models\User;
use App\Observers\DendaObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Denda::observe(DendaObserver::class);
        User::observe(UserObserver::class);
    }
}
