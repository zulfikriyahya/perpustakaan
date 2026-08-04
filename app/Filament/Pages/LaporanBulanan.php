<?php

namespace App\Filament\Pages;

use App\Services\LaporanBulananService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LaporanBulanan extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Bulanan';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected string $view = 'filament.pages.laporan-bulanan';

    public ?array $data = [];

    // public static function canAccess(): bool
    // {
    //     return auth()->user()?->can('ViewAny:LaporanBulanan') ?? false;
    // }

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

    // TODO: verifikasi signature Section/Grid terhadap versi filament/filament
    // di composer.lock - keduanya diasumsikan tersedia di
    // Filament\Schemas\Components sejalan dengan Schema yang sudah dipakai
    // project ini (Filament v5.7), belum pernah dipakai di file lain project
    // untuk dikonfirmasi persis.
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pilih Periode')
                ->description('Laporan mencakup Peminjaman, Pengembalian, Denda, Kunjungan, Point, serta riwayat Badge/Reward/Punishment pada bulan yang dipilih.')
                ->icon('heroicon-o-calendar-days')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('bulan')
                                ->label('Bulan')
                                ->native(false)
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
                                ->native(false)
                                ->options(
                                    collect(range((int) now()->format('Y'), 2024))
                                        ->mapWithKeys(fn($y) => [$y => $y])
                                )
                                ->required(),
                        ]),
                ]),
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
