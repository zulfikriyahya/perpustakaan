<?php

namespace App\Filament\Resources\KelasResource\Pages;

use App\Filament\Resources\KelasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKelas extends EditRecord
{
    protected static string $resource = KelasResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
