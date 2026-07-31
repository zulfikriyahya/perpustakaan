<?php

namespace App\Filament\Resources;

use App\Filament\Exports\BukuExporter;
use App\Filament\Imports\BukuImporter;
use App\Filament\Resources\BukuResource\Pages;
use App\Filament\Resources\BukuResource\RelationManagers\EksemplarsRelationManager;
use App\Models\Buku;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BukuResource extends Resource
{
    protected static ?string $model = Buku::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Buku';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('judul')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            FileUpload::make('cover')
                ->image()
                ->directory('buku-cover'),
            TextInput::make('penulis')
                ->maxLength(255),
            TextInput::make('penerbit')
                ->maxLength(255),
            TextInput::make('isbn')
                ->label('ISBN')
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('1 ISBN = 1 judul. Jumlah eksemplar fisik dikelola di tab Eksemplar setelah buku disimpan.'),
            TextInput::make('tahun_terbit')
                ->label('Tahun Terbit')
                ->numeric()
                ->minValue(1000)
                ->maxValue((int) date('Y'))
                ->maxLength(4),
            Select::make('kategoris')
                ->label('Kategori')
                ->relationship('kategoris', 'nama')
                ->multiple()
                ->preload()
                ->searchable(),
            TextInput::make('harga_ganti')
                ->label('Harga Ganti')
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->helperText('Dipakai sebagai basis perhitungan Denda kerusakan/kehilangan untuk semua eksemplar judul ini.'),
            Textarea::make('deskripsi')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(BukuImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Buku::class) ?? false),
                ExportAction::make()
                    ->exporter(BukuExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Buku::class) ?? false),
            ])
            ->columns([
                ImageColumn::make('cover')
                    ->square(),
                TextColumn::make('judul')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('isbn')
                    ->label('ISBN')
                    ->searchable(),
                TextColumn::make('tahun_terbit')
                    ->label('Tahun')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('eksemplars_count')
                    ->label('Total Eksemplar')
                    ->counts('eksemplars')
                    ->sortable(),
                TextColumn::make('harga_ganti')
                    ->label('Harga Ganti')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kategoris')
                    ->label('Kategori')
                    ->relationship('kategoris', 'nama'),
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
            'index' => Pages\ListBukus::route('/'),
            'create' => Pages\CreateBuku::route('/create'),
            'edit' => Pages\EditBuku::route('/{record}/edit'),
        ];
    }
}
