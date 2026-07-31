<?php

namespace App\Filament\Resources\BukuResource\RelationManagers;

use App\Enums\StatusEksemplar;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EksemplarsRelationManager extends RelationManager
{
    protected static string $relationship = 'eksemplars';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('barcode')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Select::make('rak_id')
                ->label('Rak')
                ->relationship('rak', 'nama')
                ->searchable()
                ->preload(),
            Select::make('status')
                ->options(collect(StatusEksemplar::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)]))
                ->required()
                ->default(StatusEksemplar::Tersedia->value)
                ->helperText('Ubah manual hanya untuk koreksi data - alur normal status diubah otomatis oleh PeminjamanService.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('barcode')
            ->columns([
                TextColumn::make('barcode')->searchable(),
                TextColumn::make('rak.nama')->label('Rak'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (StatusEksemplar $state) => match ($state) {
                        StatusEksemplar::Tersedia => 'success',
                        StatusEksemplar::Dipinjam => 'warning',
                        StatusEksemplar::Rusak, StatusEksemplar::Hilang => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(StatusEksemplar::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])),
            ]);
    }
}
