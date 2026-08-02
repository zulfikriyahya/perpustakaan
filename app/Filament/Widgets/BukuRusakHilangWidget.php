<?php

namespace App\Filament\Widgets;

use App\Enums\StatusEksemplar;
use App\Models\Eksemplar;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Daftar Eksemplar dengan status Rusak/Hilang SAAT INI - sumber
 * kebenaran Eksemplar.status (Aturan poin 3, konsisten dengan
 * Buku::stokTersedia()), BUKAN histori Pengembalian.kondisi/Denda.tipe -
 * eksemplar yang sudah diperbaiki/dihapus setelah kejadian rusak/hilang
 * TIDAK akan muncul di sini, karena statusnya sudah berubah.
 */
class BukuRusakHilangWidget extends TableWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getTableHeading(): string
    {
        return 'Eksemplar Rusak & Hilang';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Eksemplar::query()
                    ->with('buku', 'rak')
                    ->whereIn('status', [StatusEksemplar::Rusak, StatusEksemplar::Hilang])
                    ->latest('updated_at')
            )
            ->columns([
                TextColumn::make('buku.judul')->label('Judul Buku')->searchable(),
                TextColumn::make('barcode')->label('Barcode')->searchable(),
                TextColumn::make('rak.nama')->label('Rak')->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (StatusEksemplar $state) => match ($state) {
                        StatusEksemplar::Rusak => 'warning',
                        StatusEksemplar::Hilang => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')->label('Diperbarui')->dateTime('d M Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        StatusEksemplar::Rusak->value => 'Rusak',
                        StatusEksemplar::Hilang->value => 'Hilang',
                    ]),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
