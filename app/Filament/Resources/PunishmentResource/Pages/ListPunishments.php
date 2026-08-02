<?php

namespace App\Filament\Resources\PunishmentResource\Pages;

use App\Filament\Resources\PunishmentResource;
use App\Filament\Resources\PunishmentResource\Widgets\PunishmentStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPunishments extends ListRecords
{
    protected static string $resource = PunishmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    protected function getHeaderWidgets(): array
    {
        return [PunishmentStatsWidget::class];
    }
}
