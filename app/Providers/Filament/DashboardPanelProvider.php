<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Auth\ResetPassword;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->globalSearch(false)
            ->default()
            ->databaseNotifications()
            ->id('dashboard')
            ->path('dashboard')
            ->login(Login::class)
            ->spa()

            ->passwordReset(
                RequestPasswordReset::class,
                ResetPassword::class,
            )
            ->colors([
                'primary' => Color::Cyan,
            ])
            // Pakai Lexend yang sudah di-bundle lokal via @fontsource/lexend
            // (resources/css/app.css), bukan fetch dari Google Fonts CDN.
            // TODO: verifikasi signature terhadap versi package yang
            // terpasang - argumen kedua diasumsikan menonaktifkan provider
            // Google Fonts bawaan Filament v5.7; cek ulang jika behaviour
            // berbeda (mis. tetap muncul request ke fonts.googleapis.com).
            ->font('Lexend', provider: null)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // \App\Filament\Widgets\PeminjamanStatsWidget::class,
                // \App\Filament\Widgets\TrenKunjunganChartWidget::class,
                // \App\Filament\Widgets\PeminjamanJatuhTempoWidget::class,
                // \App\Filament\Widgets\DendaTerbaruWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
