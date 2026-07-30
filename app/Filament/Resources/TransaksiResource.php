<?php

namespace App\Filament\Resources;

use App\Enums\JenisTransaksi;
use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\RelationManagers\PeminjamansRelationManager;
use App\Models\Transaksi;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Transaksi dibuat otomatis sebagai pembungkus proses (peminjaman/
 * kunjungan/pembayaran_denda) - tidak ada Create/Edit manual di sini.
 * Read-only log + Admin boleh Delete untuk koreksi (dikonfirmasi).
 *
 * TODO: GAP-SPEC - belum ditemukan kode yang membuat Transaksi dengan
 * jenis 'kunjungan' atau 'pembayaran_denda' (PeminjamanService hanya
 * terlihat menangani jenis 'peminjaman' lewat pinjamBuku()). Kemungkinan
 * dua jenis ini memang belum diimplementasikan - perlu dikonfirmasi apakah
 * ini scope iterasi selanjutnya, bukan gap di TransaksiResource ini.
 */
class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Transaksi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nama')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jenis')
                    ->badge()
                    ->color(fn(JenisTransaksi $state) => match ($state) {
                        JenisTransaksi::Peminjaman => 'info',
                        JenisTransaksi::Kunjungan => 'gray',
                        JenisTransaksi::PembayaranDenda => 'success',
                    }),
                TextColumn::make('diprosesOleh.nama')
                    ->label('Diproses Oleh')
                    ->toggleable(),
                TextColumn::make('tanggal')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->options(collect(JenisTransaksi::cases())->mapWithKeys(fn($j) => [$j->value => ucfirst(str_replace('_', ' ', $j->value))])),
            ])
            ->recordActions([
                DeleteAction::make(), // digerbang TransaksiPolicy::delete() - hanya Admin
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            PeminjamansRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksis::route('/'),
            'view' => Pages\ViewTransaksi::route('/{record}'),
        ];
    }
}
