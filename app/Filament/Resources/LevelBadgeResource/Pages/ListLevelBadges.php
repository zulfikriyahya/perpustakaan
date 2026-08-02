<?php

namespace App\Filament\Resources\LevelBadgeResource\Pages;

use App\Filament\Resources\LevelBadgeResource;
use App\Filament\Resources\LevelBadgeResource\Widgets\LevelBadgeStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLevelBadges extends ListRecords
{
    protected static string $resource = LevelBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    protected function getHeaderWidgets(): array
    {
        return [LevelBadgeStatsWidget::class];
    }
}
