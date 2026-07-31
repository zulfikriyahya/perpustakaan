<?php

namespace App\Filament\Resources\LevelBadgeResource\Pages;

use App\Filament\Resources\LevelBadgeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLevelBadge extends EditRecord
{
    protected static string $resource = LevelBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
