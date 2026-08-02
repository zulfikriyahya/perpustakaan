<?php

namespace App\Filament\Widgets;

use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

/**
 * Tren bulanan tahun berjalan - Kunjungan, Peminjaman, Pengembalian
 * dalam satu chart supaya mudah dibandingkan (Aturan poin 3, data
 * dihitung langsung dari tabel masing-masing, tidak ada tabel agregat
 * baru).
 */
class TrenBulananWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Tren Bulanan ('.now()->year.')';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getData(): array
    {
        $tahun = now()->year;

        $kunjungan = Kunjungan::query()
            ->selectRaw('MONTH(tanggal) as bulan, COUNT(*) as total')
            ->whereYear('tanggal', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $peminjaman = Peminjaman::query()
            ->selectRaw('MONTH(tanggal_pinjam) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_pinjam', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $pengembalian = Pengembalian::query()
            ->selectRaw('MONTH(tanggal_kembali) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_kembali', $tahun)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $labels = [];
        $dataKunjungan = [];
        $dataPeminjaman = [];
        $dataPengembalian = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $labels[] = Carbon::create($tahun, $bulan, 1)->translatedFormat('M');
            $dataKunjungan[] = (int) ($kunjungan[$bulan] ?? 0);
            $dataPeminjaman[] = (int) ($peminjaman[$bulan] ?? 0);
            $dataPengembalian[] = (int) ($pengembalian[$bulan] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Kunjungan',
                    'data' => $dataKunjungan,
                    'borderColor' => '#06b6d4',
                    'backgroundColor' => 'rgba(6, 182, 212, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Peminjaman',
                    'data' => $dataPeminjaman,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Pengembalian',
                    'data' => $dataPengembalian,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'tension' => 0.35,
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
