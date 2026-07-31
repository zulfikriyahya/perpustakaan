<?php

namespace App\Filament\Resources\TahunPelajaranResource\Pages;

use App\Filament\Resources\TahunPelajaranResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTahunPelajaran extends EditRecord
{
    protected static string $resource = TahunPelajaranResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
