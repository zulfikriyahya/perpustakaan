<?php

namespace App\Filament\Resources\TransaksiResource\RelationManagers;

use App\Enums\StatusPeminjaman;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only - Peminjaman diubah HANYA lewat PeminjamanResource/
 * PeminjamanService (Aturan poin 3), RelationManager ini murni untuk lihat
 * buku apa saja yang termasuk dalam satu Transaksi (mirip pola
 * RakResource\BukusRelationManager).
 */
class PeminjamansRelationManager extends RelationManager
{
    protected static string $relationship = 'peminjamans';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('eksemplar.buku.judul')
                    ->label('Buku')
                    ->searchable(),
                TextColumn::make('tanggal_jatuh_tempo')
                    ->date(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (StatusPeminjaman $state) => match ($state) {
                        StatusPeminjaman::Aktif => 'success',
                        StatusPeminjaman::Terlambat => 'danger',
                        StatusPeminjaman::Selesai => 'gray',
                        StatusPeminjaman::Hilang => 'warning',
                    }),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
