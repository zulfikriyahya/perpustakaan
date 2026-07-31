<?php

namespace App\Filament\Resources\KelasTahunPelajaranResource\Pages;

use App\Filament\Resources\KelasTahunPelajaranResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKelasTahunPelajaran extends CreateRecord
{
    protected static string $resource = KelasTahunPelajaranResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
