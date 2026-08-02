<?php

namespace App\Http\Controllers;

use App\Filament\Widgets\BukuPerKategoriWidget;
use App\Filament\Widgets\GamifikasiBulananWidget;
use App\Filament\Widgets\PeminjamanStatsWidget;
use App\Filament\Widgets\PerJenisKelaminWidget;
use App\Filament\Widgets\TrenBulananWidget;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ReflectionMethod;

/**
 * Generate PDF grafik berisi gambar + tabel data + ringkasan, diambil
 * ULANG dari database lewat widget terkait (bukan dari angka yang
 * dikirim client) - satu sumber kebenaran tetap di getData()/getStats()
 * masing-masing widget (Aturan poin 3), di sini hanya dipanggil ulang
 * lewat reflection karena method-nya protected.
 *
 * TODO: verifikasi signature terhadap versi barryvdh/laravel-dompdf
 * yang benar-benar terpasang (composer.json ^3.1) - method
 * Pdf::loadView()->download() diasumsikan stabil, belum diverifikasi
 * langsung terhadap composer.lock.
 */
class ChartExportController extends Controller
{
    // Whitelist ketat - JANGAN pernah instantiate class dari input client
    // tanpa validasi ini, mencegah instansiasi class sembarangan.
    private const ALLOWED_CHART_WIDGETS = [
        TrenBulananWidget::class,
        GamifikasiBulananWidget::class,
        PerJenisKelaminWidget::class,
        BukuPerKategoriWidget::class,
    ];

    private const ALLOWED_STAT_WIDGETS = [
        PeminjamanStatsWidget::class,
    ];

    public function pdf(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'string', 'starts_with:data:image/png;base64,'],
            'filename' => ['nullable', 'string', 'max:100'],
            'widget' => ['required', 'string'],
            'type' => ['required', 'in:chart,stat'],
            'stat_label' => ['nullable', 'string', 'max:150'],
        ]);

        $filename = Str::slug($validated['filename'] ?? 'grafik').'.pdf';

        $data = $validated['type'] === 'chart'
            ? $this->buildChartExportData($validated['widget'])
            : $this->buildStatExportData($validated['widget'], $validated['stat_label'] ?? '');

        $pdf = Pdf::loadView('pdf.chart-export', [
            'image' => $validated['image'],
            'heading' => $data['heading'],
            'rows' => $data['rows'],
            'summary' => $data['summary'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    private function buildChartExportData(string $widgetClass): array
    {
        abort_unless(in_array($widgetClass, self::ALLOWED_CHART_WIDGETS, true), 403);

        $widget = app($widgetClass);

        $heading = (new ReflectionMethod($widget, 'getHeading'))->invoke($widget);
        $heading = $heading instanceof Htmlable ? strip_tags($heading->toHtml()) : (string) $heading;

        $chartData = (new ReflectionMethod($widget, 'getData'))->invoke($widget);
        $labels = $chartData['labels'] ?? [];
        $datasets = $chartData['datasets'] ?? [];

        $rows = [];
        foreach ($labels as $i => $label) {
            $row = ['label' => $label];
            foreach ($datasets as $dataset) {
                $row[$dataset['label'] ?? '-'] = $dataset['data'][$i] ?? 0;
            }
            $rows[] = $row;
        }

        $summary = [];
        foreach ($datasets as $dataset) {
            $values = array_map('floatval', $dataset['data'] ?? []);
            $summary[] = [
                'label' => $dataset['label'] ?? '-',
                'total' => array_sum($values),
                'rata_rata' => count($values) ? round(array_sum($values) / count($values), 2) : 0,
            ];
        }

        return ['heading' => $heading, 'rows' => $rows, 'summary' => $summary];
    }

    private function buildStatExportData(string $widgetClass, string $statLabel): array
    {
        abort_unless(in_array($widgetClass, self::ALLOWED_STAT_WIDGETS, true), 403);

        $widget = app($widgetClass);

        $stats = (new ReflectionMethod($widget, 'getStats'))->invoke($widget);

        $stat = collect($stats)->first(
            fn ($s) => $s->getLabel() === $statLabel
        );

        abort_if($stat === null, 404, 'Stat tidak ditemukan.');

        $chart = $stat->getChart() ?? [];

        $rows = [];
        foreach ($chart as $label => $value) {
            $rows[] = ['label' => $label, $statLabel => $value];
        }

        $values = array_map('floatval', array_values($chart));

        return [
            'heading' => $statLabel,
            'rows' => $rows,
            'summary' => [[
                'label' => $statLabel,
                'total' => array_sum($values),
                'rata_rata' => count($values) ? round(array_sum($values) / count($values), 2) : 0,
            ]],
        ];
    }
}
