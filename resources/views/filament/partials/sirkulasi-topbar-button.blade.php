@php
    $bisaAkses = \App\Filament\Pages\Sirkulasi::canAccess();
    $sedangDiSirkulasi = request()->routeIs('filament.dashboard.pages.sirkulasi');
@endphp
@if ($bisaAkses)
    <style>
        .fi-topbar .sirkulasi-topbar-btn {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.375rem !important;
            padding: 0.375rem 0.75rem !important;
            border-radius: 0.5rem !important;
            font-size: 0.8125rem !important;
            font-weight: 500 !important;
            color: #ffffff !important;
            background-color: #1f4d2c !important;
            border: 1px solid transparent !important;
            flex-shrink: 0 !important;
            white-space: nowrap !important;
            margin-inline-start: 1rem !important;
            text-decoration: none !important;
        }

        .fi-topbar .sirkulasi-topbar-btn:hover {
            background-color: #163a20 !important;
        }

        html.dark .fi-topbar .sirkulasi-topbar-btn {
            background-color: #2a6b3c !important;
        }

        html.dark .fi-topbar .sirkulasi-topbar-btn:hover {
            background-color: #1f4d2c !important;
        }
    </style>

    @if ($sedangDiSirkulasi)
<a
            href="{{ \App\Filament\Pages\Dashboard::getUrl() }}"
            wire:navigate
            class="sirkulasi-topbar-btn"
        >
            <x-filament::icon icon="heroicon-o-home" style="width: 1rem; height: 1rem;" />
            <span>Dashboard</span>
        </a>
    @else
<a
            href="{{ \App\Filament\Pages\Sirkulasi::getUrl() }}"
            wire:navigate
            class="sirkulasi-topbar-btn"
        >
            <x-filament::icon icon="heroicon-o-viewfinder-circle" style="width: 1rem; height: 1rem;" />
            <span>Sirkulasi</span>
        </a>
    @endif
@endif
