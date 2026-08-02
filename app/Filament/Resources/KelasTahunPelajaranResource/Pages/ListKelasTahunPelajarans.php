<?php

namespace App\Filament\Resources\KelasTahunPelajaranResource\Pages;

use App\Filament\Resources\KelasTahunPelajaranResource;
use App\Filament\Resources\KelasTahunPelajaranResource\Widgets\KelasTahunPelajaranStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKelasTahunPelajarans extends ListRecords
{
    protected static string $resource = KelasTahunPelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    protected function getHeaderWidgets(): array
    {
        return [KelasTahunPelajaranStatsWidget::class];
    }
}
