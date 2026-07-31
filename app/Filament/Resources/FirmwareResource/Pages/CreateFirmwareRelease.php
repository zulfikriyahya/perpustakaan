<?php

namespace App\Filament\Resources\FirmwareResource\Pages;

use App\Filament\Resources\FirmwareResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateFirmwareRelease extends CreateRecord
{
    protected static string $resource = FirmwareResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $path = $data['file'] ?? null;
        unset($data['file']);

        if ($path) {
            $data['url'] = Storage::disk('public')->url($path);
            $data['md5'] = md5_file(Storage::disk('public')->path($path));
        }

        return $data;
    }
}
