<?php

namespace App\Filament\Widgets;

use App\Models\Kunjungan;
use Filament\Widgets\ChartWidget;

/**
 * Tren kunjungan 14 hari terakhir - untuk Admin & Pustakawan.
 * TODO: verifikasi signature getData()/getType() terhadap versi
 * filament/filament ^5.7 yang terpasang.
 */
class TrenKunjunganChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return 'Tren Kunjungan (14 Hari Terakhir)';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getData(): array
    {
        $mulai = now()->subDays(13)->startOfDay();

        $data = Kunjungan::query()
            ->selectRaw('DATE(tanggal) as tgl, COUNT(*) as total')
            ->where('tanggal', '>=', $mulai->toDateString())
            ->groupBy('tgl')
            ->orderBy('tgl')
            ->pluck('total', 'tgl');

        $labels = [];
        $values = [];

        for ($i = 0; $i < 14; $i++) {
            $tanggal = $mulai->copy()->addDays($i);
            $key = $tanggal->toDateString();

            $labels[] = $tanggal->translatedFormat('d M');
            $values[] = (int) ($data[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Kunjungan',
                    'data' => $values,
                    'borderColor' => '#06b6d4',
                    'backgroundColor' => 'rgba(6, 182, 212, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
