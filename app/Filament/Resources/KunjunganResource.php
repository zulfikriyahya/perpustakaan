<?php

namespace App\Filament\Resources;

use App\Enums\SourceKunjungan;
use App\Filament\Resources\KunjunganResource\Pages;
use App\Models\Kunjungan;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Kunjungan HANYA hasil sinkronisasi device RFID (atau input manual oleh
 * Pustakawan di luar sistem ini - lihat SourceKunjungan::Manual, belum ada
 * UI-nya). Tidak ada Create/Edit page - murni log read-only, Admin boleh
 * Delete untuk koreksi data salah (dikonfirmasi). Tidak ada halaman View
 * terpisah karena semua field sudah tampil penuh di tabel (Aturan poin 6 -
 * hindari file yang tidak menambah nilai).
 */
class KunjunganResource extends Resource
{
    protected static ?string $model = Kunjungan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationLabel = 'Kunjungan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nama')
                    ->label('Pengunjung')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('jam_tap')
                    ->time()
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn(SourceKunjungan $state) => match ($state) {
                        SourceKunjungan::Rfid => 'info',
                        SourceKunjungan::Manual => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->options(collect(SourceKunjungan::cases())->mapWithKeys(fn($s) => [$s->value => ucfirst($s->value)])),
            ])
            ->recordActions([
                DeleteAction::make(), // digerbang KunjunganPolicy::delete() - hanya Admin
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKunjungans::route('/'),
        ];
    }
}
