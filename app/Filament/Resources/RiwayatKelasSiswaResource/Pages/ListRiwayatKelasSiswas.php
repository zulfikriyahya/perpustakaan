<?php

namespace App\Filament\Resources\RiwayatKelasSiswaResource\Pages;

use App\Filament\Resources\RiwayatKelasSiswaResource;
use Filament\Resources\Pages\ListRecords;

class ListRiwayatKelasSiswas extends ListRecords
{
    protected static string $resource = RiwayatKelasSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
