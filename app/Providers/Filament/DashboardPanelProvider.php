<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Auth\ResetPassword;
use App\Filament\Pages\Dashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->sidebarWidth('250px')
            ->unsavedChangesAlerts()
            ->favicon(asset('images/favicon.ico'))
            ->simplePageMaxContentWidth(Width::Medium)
            ->maxContentWidth(Width::Full)
            ->globalSearch(false)
            ->default()
            ->databaseNotifications()
            ->id('dashboard')
            ->path('dashboard')
            ->login(Login::class)
            ->brandLogo(new HtmlString(
                '<img src="'.asset('images/brand-lightmode.png').'" alt="Logo MTs Negeri 1 Pandeglang" class="fi-logo-light" />'.
                    '<img src="'.asset('images/brand-darkmode.png').'" alt="Logo MTs Negeri 1 Pandeglang" class="fi-logo-dark" />'
            ))
            ->brandLogoHeight('2.5rem')
            ->spa(hasPrefetching: true)
            ->pages([
                Dashboard::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.partials.global-logo-style')->render()
                    .view('filament.partials.global-footer-style')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => request()->routeIs('filament.dashboard.auth.*')
                    ? ''
                    : view('filament.partials.app-footer')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.partials.chart-export-script')->render(),
            )
            /**
             * BARU - tombol akses halaman Sirkulasi di topbar (kanan
             * atas, bersebelahan icon database notification, sesuai gap
             * iterasi ini). Disembunyikan otomatis untuk halaman auth
             * (belum login, topbar tidak relevan) memakai pola
             * routeIs() yang sama seperti footer di atas.
             *
             * TODO: verifikasi signature terhadap versi package yang
             * terpasang - enum case PanelsRenderHook::TOPBAR_END
             * diasumsikan tersedia dan posisinya berdekatan dengan
             * notifikasi database di filament/filament ^5.7 (composer.lock
             * proyek ini); WAJIB dicek visual (poin 12) - kalau posisi
             * ternyata tidak bersebelahan icon notification, ganti ke
             * render hook lain yang tersedia (mis. USER_MENU_BEFORE) atau
             * sesuaikan CSS margin di partial terkait.
             */
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => request()->routeIs('filament.dashboard.auth.*')
                    ? ''
                    : view('filament.partials.sirkulasi-topbar-button')->render(),
            )
            ->passwordReset(
                RequestPasswordReset::class,
                ResetPassword::class,
            )
            ->colors([
                'primary' => Color::Cyan,
            ])
            ->font('Lexend', provider: null)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
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
