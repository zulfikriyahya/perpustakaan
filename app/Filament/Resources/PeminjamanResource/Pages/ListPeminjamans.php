<?php

namespace App\Filament\Resources\PeminjamanResource\Pages;

use App\Filament\Resources\PeminjamanResource;
use App\Filament\Resources\PeminjamanResource\Widgets\PeminjamanOverviewWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPeminjamans extends ListRecords
{
    protected static string $resource = PeminjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Input Peminjaman Manual'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [PeminjamanOverviewWidget::class];
    }
}
