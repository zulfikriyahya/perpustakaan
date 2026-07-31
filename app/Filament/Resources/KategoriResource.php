<?php

namespace App\Filament\Resources;

use App\Filament\Exports\KategoriExporter;
use App\Filament\Imports\KategoriImporter;
use App\Filament\Resources\KategoriResource\Pages;
use App\Models\Kategori;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KategoriResource extends Resource
{
    protected static ?string $model = Kategori::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Kategori';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->required()
                ->maxLength(255),
            Textarea::make('deskripsi')
                ->columnSpanFull(),
            Select::make('raks')
                ->label('Rak Terkait')
                ->relationship('raks', 'nama')
                ->multiple()
                ->preload()
                ->searchable()
                ->createOptionForm([
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
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(KategoriImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Kategori::class) ?? false),
                ExportAction::make()
                    ->exporter(KategoriExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Kategori::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('deskripsi')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('bukus_count')
                    ->label('Jumlah Buku')
                    ->counts('bukus')
                    ->sortable(),
                TextColumn::make('eksemplars_count')
                    ->label('Jumlah Eksemplar')
                    ->counts('eksemplars')
                    ->sortable(),
                TextColumn::make('stok_tersedia')
                    ->label('Stok Tersedia')
                    ->state(fn (Kategori $record) => $record->stokTersedia())
                    ->badge()
                    ->color(fn (Kategori $record) => $record->stokTersedia() > 0 ? 'success' : 'danger'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKategoris::route('/'),
            'create' => Pages\CreateKategori::route('/create'),
            'edit' => Pages\EditKategori::route('/{record}/edit'),
        ];
    }
}
