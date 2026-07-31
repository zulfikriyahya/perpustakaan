<?php

namespace App\Filament\Resources\RakResource\RelationManagers;

use App\Enums\StatusEksemplar;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Rak -> Eksemplar adalah relasi one-to-many ASLI (Eksemplar.rak_id).
 * Rak TIDAK lagi berelasi langsung ke Buku (lihat migration
 * 2026_08_02_000003 dan 2026_08_02_000002) - satu judul Buku bisa punya
 * banyak Eksemplar tersebar di rak berbeda.
 *
 * RelationManager ini murni untuk LIHAT eksemplar yang ada di rak ini -
 * pindah rak / ubah status eksemplar tetap lewat BukuResource >
 * EksemplarsRelationManager langsung (satu sumber kebenaran, Aturan
 * poin 3 - DRY), supaya tidak ada dua tempat berbeda yang bisa mengubah
 * data Eksemplar yang sama.
 */
class EksemplarsRelationManager extends RelationManager
{
    protected static string $relationship = 'eksemplars';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('barcode')
            ->columns([
                TextColumn::make('buku.judul')
                    ->label('Judul Buku')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (StatusEksemplar $state) => $state->value),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}
