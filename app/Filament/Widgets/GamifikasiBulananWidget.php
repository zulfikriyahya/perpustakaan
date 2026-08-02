<?php

namespace App\Filament\Widgets;

use App\Models\LevelBadgeLog;
use App\Models\PunishmentLog;
use App\Models\RewardLog;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Tren perolehan Badge, Reward, dan penerapan Punishment per bulan tahun
 * berjalan. Badge/Reward dari kolom tanggal_didapat, Punishment dari
 * tanggal_diterapkan (bukan tanggal_berakhir - poin start peristiwa yang
 * relevan untuk tren "terjadi kapan").
 */
class GamifikasiBulananWidget extends ChartWidget
{
    protected static ?int $sort = 6;

    protected ?string $maxHeight = '500px';

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Badge, Reward & Punishment per Bulan ('.now()->year.')';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getData(): array
    {
        $tahun = now()->year;

        $badge = LevelBadgeLog::query()
            ->selectRaw('MONTH(tanggal_didapat) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_didapat', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $reward = RewardLog::query()
            ->selectRaw('MONTH(tanggal_didapat) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_didapat', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $punishment = PunishmentLog::query()
            ->selectRaw('MONTH(tanggal_diterapkan) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_diterapkan', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $labels = [];
        $dataBadge = [];
        $dataReward = [];
        $dataPunishment = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $labels[] = Carbon::create($tahun, $bulan, 1)->translatedFormat('M');
            $dataBadge[] = (int) ($badge[$bulan] ?? 0);
            $dataReward[] = (int) ($reward[$bulan] ?? 0);
            $dataPunishment[] = (int) ($punishment[$bulan] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Badge',
                    'data' => $dataBadge,
                    'borderColor' => '#a855f7',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Reward',
                    'data' => $dataReward,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Punishment',
                    'data' => $dataPunishment,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.12)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
            'elements' => [
                'point' => ['radius' => 2, 'hoverRadius' => 5],
            ],
        ];
    }
}
