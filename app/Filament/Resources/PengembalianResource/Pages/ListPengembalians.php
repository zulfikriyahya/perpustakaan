<?php

namespace App\Filament\Resources\PengembalianResource\Pages;

use App\Filament\Resources\PengembalianResource;
use Filament\Resources\Pages\ListRecords;

class ListPengembalians extends ListRecords
{
    protected static string $resource = PengembalianResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
