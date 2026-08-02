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
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->globalSearch(false)
            ->default()
            ->databaseNotifications()
            ->id('dashboard')
            ->path('dashboard')
            ->login(Login::class)
            /**
             * Logo dark/light. brandLogo() menerima Htmlable, dua <img>
             * dikirim sekaligus; mana yang tampil diatur via CSS
             * global (renderHook HEAD_END di bawah).
             * TODO: verifikasi signature terhadap versi package yang
             * terpasang - brandLogo() menerima string|Htmlable|Closure
             * di dokumentasi umum Filament v3+; belum diverifikasi
             * terhadap filament/filament ^5.7 di composer.lock proyek ini.
             */
            ->brandLogo(new HtmlString(
                '<img src="'.asset('images/brand-lightmode.png').'" alt="Logo MTs Negeri 1 Pandeglang" class="fi-logo-light" />'.
                    '<img src="'.asset('images/brand-darkmode.png').'" alt="Logo MTs Negeri 1 Pandeglang" class="fi-logo-dark" />'
            ))
            ->brandLogoHeight('2.5rem')
            ->spa()
            ->pages([
                Dashboard::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.partials.global-logo-style')->render()
                    .view('filament.partials.global-footer-style')->render(),
            )
            /**
             * FITUR BARU - footer di BAWAH body, HANYA untuk halaman
             * NON-auth (mis. Dashboard). Untuk halaman auth (Login,
             * RequestPasswordReset, ResetPassword), footer disisipkan
             * manual di ATAS frame form oleh masing-masing halaman
             * (lihat Login::content() dan view Blade auth terkait) -
             * DIHINDARI dobel dengan pengecekan routeIs() disini.
             *
             * TODO: GAP-SPEC - deteksi "halaman auth" via
             * request()->routeIs('filament.dashboard.auth.*') diverifikasi
             * BENAR terhadap route yang sudah dipakai di proyek ini
             * (ResetPassword::prosesReset() memanggil
             * route('filament.dashboard.auth.login'), RequestPasswordReset
             * memakai 'filament.dashboard.auth.password-reset.request'/
             * '.reset') - pola wildcard 'filament.dashboard.auth.*' AMAN
             * mencakup ketiganya. Tetap WAJIB dicek visual (poin 12) jika
             * suatu saat ada halaman auth baru dengan nama route berbeda
             * (mis. registrasi, email verification) - footer bisa dobel
             * atau tidak muncul jika pola route-nya tidak tercakup.
             */
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
