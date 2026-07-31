<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KelasResource\Pages;
use App\Models\Kelas;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KelasResource extends Resource
{
    protected static ?string $model = Kelas::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Kelas';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama Kelas (mis. X IPA 1)')
                ->required()
                ->maxLength(255),
            TextInput::make('tingkat')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->required()
                ->helperText('Angka tingkat, mis. 10, 11, 12 - dipakai untuk urutan kenaikan kelas.'),
            Select::make('jurusan_id')
                ->label('Jurusan')
                ->relationship('jurusan', 'nama')
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('tingkat')->sortable(),
                TextColumn::make('jurusan.nama')->label('Jurusan')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jurusan_id')->label('Jurusan')->relationship('jurusan', 'nama'),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKelas::route('/'),
            'create' => Pages\CreateKelas::route('/create'),
            'edit' => Pages\EditKelas::route('/{record}/edit'),
        ];
    }
}
