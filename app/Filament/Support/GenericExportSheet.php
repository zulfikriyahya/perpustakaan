<?php

namespace App\Filament\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Satu class dipakai ulang untuk SEMUA sheet Export Master (Aturan
 * poin 3, DRY). Dikonstruksi dari satu entri MasterDataRegistry::items().
 *
 * BUG FIX (iterasi ini): heuristik tebakRelasi() (whitelist nama key
 * kolom) DIHAPUS - tidak match untuk banyak sheet (kolom nested seperti
 * 'eksemplar.buku', key yang beda nama dari relasi asli seperti 'badge'
 * vs 'levelBadge', dst.), menyebabkan N+1 tersembunyi. Diganti key
 * 'eager' eksplisit per entri di MasterDataRegistry - deterministik,
 * tidak bergantung tebakan dari nama kolom.
 */
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
