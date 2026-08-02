<?php

namespace App\Filament\Resources\RiwayatKelasSiswaResource\Pages;

use App\Filament\Resources\RiwayatKelasSiswaResource;
use App\Filament\Resources\RiwayatKelasSiswaResource\Widgets\RiwayatKelasSiswaStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListRiwayatKelasSiswas extends ListRecords
{
    protected static string $resource = RiwayatKelasSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [RiwayatKelasSiswaStatsWidget::class];
    }
}
