<?php

namespace App\Filament\Resources\RewardLogResource\Pages;

use App\Filament\Resources\RewardLogResource;
use Filament\Resources\Pages\ListRecords;

class ListRewardLogs extends ListRecords
{
    protected static string $resource = RewardLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
