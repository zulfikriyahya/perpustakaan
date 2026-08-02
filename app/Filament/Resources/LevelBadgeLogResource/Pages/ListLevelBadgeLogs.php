<?php

namespace App\Filament\Resources\LevelBadgeLogResource\Pages;

use App\Filament\Resources\LevelBadgeLogResource;
use App\Filament\Resources\LevelBadgeLogResource\Widgets\LevelBadgeLogStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListLevelBadgeLogs extends ListRecords
{
    protected static string $resource = LevelBadgeLogResource::class;

    protected function getHeaderWidgets(): array
    {
        return [LevelBadgeLogStatsWidget::class];
    }
}
