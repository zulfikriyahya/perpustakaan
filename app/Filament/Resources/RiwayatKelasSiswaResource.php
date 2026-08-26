<?php

namespace App\Filament\Resources;

use App\Enums\StatusRiwayatKelas;
use App\Filament\Exports\RiwayatKelasSiswaExporter;
use App\Filament\Resources\RiwayatKelasSiswaResource\Pages;
use App\Models\RiwayatKelasSiswa;
use Filament\Actions\ExportAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RiwayatKelasSiswaResource extends Resource
{
    protected static ?string $model = RiwayatKelasSiswa::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Riwayat Kelas Siswa';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(RiwayatKelasSiswaExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', RiwayatKelasSiswa::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')->label('Siswa')->searchable()->sortable(),
                TextColumn::make('user.nisn')->label('NISN')->searchable(),
                TextColumn::make('kelasTahunPelajaran.kelas.nama')->label('Kelas')->sortable(),
                TextColumn::make('kelasTahunPelajaran.tahunPelajaran.nama')->label('Tahun Pelajaran')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (StatusRiwayatKelas $state) => match ($state) {
                        StatusRiwayatKelas::Aktif => 'success',
                        StatusRiwayatKelas::Naik => 'info',
                        StatusRiwayatKelas::Tinggal => 'warning',
                        StatusRiwayatKelas::Lulus => 'primary',
                        StatusRiwayatKelas::Keluar => 'gray',
                    }),
                TextColumn::make('tanggal_mulai')->date('d F Y')->sortable(),
                TextColumn::make('tanggal_selesai')->date('d F Y')->sortable()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(StatusRiwayatKelas::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRiwayatKelasSiswas::route('/'),
        ];
    }
}
