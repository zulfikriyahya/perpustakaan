<?php

namespace App\Filament\Pages;

use App\Services\LaporanBulananService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LaporanBulanan extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Bulanan';

    protected string $view = 'filament.pages.laporan-bulanan';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:LaporanBulanan') ?? false;
    }

    public function getHeading(): string|HtmlString
    {
        return 'Laporan Bulanan';
    }

    public function mount(): void
    {
        $this->form->fill([
            'bulan' => (int) now()->format('n'),
            'tahun' => (int) now()->format('Y'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('bulan')
                ->label('Bulan')
                ->options([
                    1 => 'Januari',
                    2 => 'Februari',
                    3 => 'Maret',
                    4 => 'April',
                    5 => 'Mei',
                    6 => 'Juni',
                    7 => 'Juli',
                    8 => 'Agustus',
                    9 => 'September',
                    10 => 'Oktober',
                    11 => 'November',
                    12 => 'Desember',
                ])
                ->required(),
            Select::make('tahun')
                ->label('Tahun')
                ->options(
                    collect(range((int) now()->format('Y'), 2024))
                        ->mapWithKeys(fn($y) => [$y => $y])
                )
                ->required(),
        ])->statePath('data');
    }

    public function generate(LaporanBulananService $service): mixed
    {
        $data = $this->form->getState();

        $laporan = $service->generate((int) $data['bulan'], (int) $data['tahun']);

        $pdf = Pdf::loadView('pdf.laporan-bulanan', $laporan)
            ->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            "laporan-bulanan-{$data['tahun']}-{$data['bulan']}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }
}
