<?php

namespace App\Filament\Resources;

use App\Enums\JenisTransaksi;
use App\Filament\Exports\TransaksiExporter;
use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\RelationManagers\PeminjamansRelationManager;
use App\Models\Peminjaman;
use App\Models\Transaksi;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(TransaksiExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Transaksi::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jenis')
                    ->badge()
                    ->color(fn (JenisTransaksi $state) => match ($state) {
                        JenisTransaksi::Peminjaman => 'info',
                        JenisTransaksi::Kunjungan => 'gray',
                        JenisTransaksi::PembayaranDenda => 'success',
                    }),
                TextColumn::make('diprosesOleh.nama')
                    ->label('Diproses Oleh')
                    ->toggleable(),
                TextColumn::make('tanggal')
                    ->dateTime('d F Y H:i')
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->options(collect(JenisTransaksi::cases())->mapWithKeys(fn ($j) => [$j->value => ucfirst(str_replace('_', '', $j->value))])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                DeleteAction::make(),
                RestoreAction::make(),
                // FK peminjamans.transaksi_id default RESTRICT.
                ForceDeleteAction::make()
                    ->action(function (Transaksi $record) {
                        $dipakai = Peminjaman::query()->withTrashed()
                            ->where('transaksi_id', $record->id)->exists();

                        if ($dipakai) {
                            Notification::make()->danger()->title('Tidak bisa dihapus permanen')
                                ->body('Transaksi ini masih direferensikan oleh Peminjaman.')->send();

                            return;
                        }

                        $record->forceDelete();
                    }),
            ])
            ->toolbarActions([DeleteBulkAction::make()])
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
