<?php

namespace App\Filament\Resources\PunishmentLogResource\Pages;

use App\Filament\Resources\PunishmentLogResource;
use App\Filament\Resources\PunishmentLogResource\Widgets\PunishmentLogStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListPunishmentLogs extends ListRecords
{
    protected static string $resource = PunishmentLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [PunishmentLogStatsWidget::class];
    }
}
