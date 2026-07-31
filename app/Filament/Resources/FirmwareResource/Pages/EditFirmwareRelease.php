<?php

namespace App\Filament\Resources\FirmwareResource\Pages;

use App\Filament\Resources\FirmwareResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditFirmwareRelease extends EditRecord
{
    protected static string $resource = FirmwareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $path = $data['file'] ?? null;
        unset($data['file']);

        // Hanya recompute url/md5 kalau ada file BARU diupload - lihat
        // GAP-SPEC di FirmwareResource (form Edit tidak preload file lama).
        if ($path) {
            $data['url'] = Storage::disk('public')->url($path);
            $data['md5'] = md5_file(Storage::disk('public')->path($path));
        }

        return $data;
    }
}
