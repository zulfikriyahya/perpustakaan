<?php

namespace App\Filament\Widgets;

use App\Models\Kategori;
use Filament\Widgets\ChartWidget;

/**
 * Jumlah judul Buku per Kategori - pakai withCount('bukus') (relasi
 * BelongsToMany langsung Kategori::bukus(), bukan hasManyThrough
 * eksemplars() yang menghitung EKSEMPLAR, bukan judul - beda makna,
 * jangan tertukar).
 */
class BukuPerKategoriWidget extends ChartWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Jumlah Judul Buku per Kategori';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getData(): array
    {
        $kategoris = Kategori::query()
            ->withCount('bukus')
            ->orderByDesc('bukus_count')
            // ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Judul Buku',
                    'data' => $kategoris->pluck('bukus_count')->all(),
                    'backgroundColor' => '#06b6d4',
                ],
            ],
            'labels' => $kategoris->pluck('nama')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
