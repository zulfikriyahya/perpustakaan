<?php

namespace App\Filament\Exports;

use App\Filament\Support\GenericExportSheet;
use App\Support\MasterDataRegistry;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MasterDataExporter implements WithMultipleSheets
{
    public function sheets(): array
    {
        return array_map(
            fn (array $item) => new GenericExportSheet($item),
            MasterDataRegistry::items()
        );
    }
}
