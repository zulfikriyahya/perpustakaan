<?php

namespace App\Providers;

use App\Models\Denda;
use App\Models\Setting;
use App\Models\User;
use App\Observers\DendaObserver;
use App\Observers\SettingObserver;
use App\Observers\UserObserver;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Octane;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // GAP-SPEC: Octane menjaga proses PHP hidup lintas banyak request,
        // sehingga resolusi skema/root URL bisa "nyangkut" dari request
        // sebelumnya jika tidak di-flush eksplisit - dampaknya signature
        // Livewire (GenerateSignedUploadUrl vs FileUploadController::
        // hasValidSignature) bisa mismatch antara saat digenerate dan saat
        // diverifikasi. config/octane.php 'flush' masih kosong, jadi
        // dipaksa ulang manual di sini tiap request masuk.
        if ($this->app->bound(Octane::class) || class_exists(RequestReceived::class)) {
            Event::listen(RequestReceived::class, function () {
                URL::forceScheme('https');
            });
        }

        FilamentShield::enforcePolicies();
        Denda::observe(DendaObserver::class);
        User::observe(UserObserver::class);
        Setting::observe(SettingObserver::class); // invalidasi cache Setting::get()
    }
}
