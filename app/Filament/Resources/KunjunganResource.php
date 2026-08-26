<?php

namespace App\Filament\Resources;

use App\Enums\SourceKunjungan;
use App\Filament\Exports\KunjunganExporter;
use App\Filament\Resources\KunjunganResource\Pages;
use App\Models\Kunjungan;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class KunjunganResource extends Resource
{
    protected static ?string $model = Kunjungan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationLabel = 'Kunjungan';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(KunjunganExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Kunjungan::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')
                    ->label('Pengunjung')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('jam_tap')
                    ->time()
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn (SourceKunjungan $state) => match ($state) {
                        SourceKunjungan::Rfid => 'info',
                        SourceKunjungan::Manual => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->options(collect(SourceKunjungan::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([DeleteBulkAction::make()])
            ->defaultSort('tanggal', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKunjungans::route('/'),
        ];
    }
}
