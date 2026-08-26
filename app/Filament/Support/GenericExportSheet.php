<?php

namespace App\Filament\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GenericExportSheet implements FromCollection, ShouldAutoSize, ShouldQueue, WithHeadings, WithMapping, WithTitle
{
    public function __construct(protected array $item) {}

    public function collection()
    {
        $modelClass = $this->item['model'];
        $eager = $this->item['eager'] ?? [];

        return $eager === []
            ? $modelClass::query()->get()
            : $modelClass::query()->with($eager)->get();
    }

    public function map($record): array
    {
        return array_map(fn ($callback) => (string) ($callback($record) ?? ''), array_values($this->item['columns']));
    }

    public function headings(): array
    {
        return array_keys($this->item['columns']);
    }

    public function title(): string
    {
        return $this->item['label'];
    }
}
