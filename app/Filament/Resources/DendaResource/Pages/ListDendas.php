<?php

namespace App\Filament\Resources\DendaResource\Pages;

use App\Filament\Resources\DendaResource;
use App\Filament\Resources\DendaResource\Widgets\DendaStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListDendas extends ListRecords
{
    protected static string $resource = DendaResource::class;

    protected function getHeaderWidgets(): array
    {
        return [DendaStatsWidget::class];
    }
}
