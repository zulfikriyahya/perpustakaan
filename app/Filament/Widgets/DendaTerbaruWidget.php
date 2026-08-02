<?php

namespace App\Filament\Widgets;

use App\Enums\TipeDenda;
use App\Models\Denda;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Denda terbaru yang belum lunas - untuk Admin & Pustakawan.
 */
class DendaTerbaruWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getTableHeading(): string
    {
        return 'Denda Belum Lunas Terbaru';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Denda::query()
                    ->where('status_lunas', false)
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('user.nama')->label('User'),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (TipeDenda $state) => match ($state) {
                        TipeDenda::Keterlambatan => 'warning',
                        TipeDenda::Kerusakan => 'danger',
                        TipeDenda::Kehilangan => 'gray',
                    }),
                TextColumn::make('nominal')->label('Nominal')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.')),
                IconColumn::make('status_refund')
                    ->label('Refund')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')->label('Tanggal')->dateTime('d M Y H:i'),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
