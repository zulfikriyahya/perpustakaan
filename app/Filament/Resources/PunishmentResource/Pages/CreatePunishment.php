<?php

namespace App\Filament\Resources\PunishmentResource\Pages;

use App\Filament\Resources\PunishmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePunishment extends CreateRecord
{
    protected static string $resource = PunishmentResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
