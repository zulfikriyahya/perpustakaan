<?php

namespace App\Filament\Resources\KunjunganResource\Pages;

use App\Filament\Resources\KunjunganResource;
use App\Filament\Resources\KunjunganResource\Widgets\KunjunganStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListKunjungans extends ListRecords
{
    protected static string $resource = KunjunganResource::class;

    protected function getHeaderWidgets(): array
    {
        return [KunjunganStatsWidget::class];
    }
}
