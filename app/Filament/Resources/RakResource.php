<?php

namespace App\Filament\Resources;

use App\Filament\Exports\RakExporter;
use App\Filament\Imports\RakImporter;
use App\Filament\Resources\RakResource\Pages;
use App\Filament\Resources\RakResource\RelationManagers\EksemplarsRelationManager;
use App\Models\Rak;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RakResource extends Resource
{
    protected static ?string $model = Rak::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Rak';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->required()
                ->maxLength(255),
            TextInput::make('lokasi')
                ->maxLength(255),
            Select::make('kategoris')
                ->label('Kategori Terkait')
                ->relationship('kategoris', 'nama')
                ->multiple()
                ->preload()
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(RakImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Rak::class) ?? false),
                ExportAction::make()
                    ->exporter(RakExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Rak::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lokasi')
                    ->searchable(),
                // FIX: dulu counts('bukus') - kolom bukus.rak_id sudah tidak
                // ada, jadi dihitung dari eksemplars (lihat Rak::eksemplars()).
                TextColumn::make('eksemplars_count')
                    ->label('Jumlah Eksemplar')
                    ->counts('eksemplars')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EksemplarsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRaks::route('/'),
            'create' => Pages\CreateRak::route('/create'),
            'edit' => Pages\EditRak::route('/{record}/edit'),
        ];
    }
}
