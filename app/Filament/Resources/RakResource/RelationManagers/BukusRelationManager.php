<?php

namespace App\Filament\Resources\RakResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Rak -> Buku adalah relasi one-to-many ASLI (Buku.rak_id), bukan pivot -
 * jadi RelationManager ini murni untuk LIHAT buku yang ada di rak ini.
 * Create/Edit/Delete Buku tetap lewat BukuResource langsung (di sana Admin/
 * Pustakawan bisa ganti rak_id-nya) - supaya tidak ada dua tempat berbeda
 * yang bisa mengubah data Buku yang sama (Aturan poin 3, DRY).
 */
class BukusRelationManager extends RelationManager
{
    protected static string $relationship = 'bukus';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('judul')
            ->columns([
                TextColumn::make('judul')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('stok'),
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
