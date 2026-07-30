<?php

namespace App\Filament\Resources;

use App\Enums\KondisiBuku;
use App\Enums\StatusPeminjaman;
use App\Filament\Resources\PeminjamanResource\Pages;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use RuntimeException;

/**
 * Form Create di sini adalah FALLBACK MANUAL untuk Pustakawan (input lewat
 * panel jika device RFID/scan barcode error) - lihat konfirmasi Aturan.
 * Alur normal tetap lewat endpoint device RFID + scan barcode fisik.
 *
 * Create SENGAJA tidak memakai Peminjaman::create() bawaan Filament -
 * seluruh logic (validasi limit/suspend, kalkulasi jatuh tempo, stok, Point,
 * WA) WAJIB lewat PeminjamanService::pinjamBuku() (Aturan poin 3, DRY).
 * Lihat Pages\CreatePeminjaman::handleRecordCreation().
 *
 * Status Peminjaman TIDAK bisa diedit manual - transisi hanya lewat
 * PeminjamanService (cron/Action pengembalian/laporkan hilang), karenanya
 * Resource ini TIDAK punya halaman Edit sama sekali.
 */
class PeminjamanResource extends Resource
{
    protected static ?string $model = Peminjaman::class;

    protected static ?string $slug = 'peminjaman';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';


    protected static ?string $navigationLabel = 'Peminjaman';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Peminjam')
                ->relationship('user', 'nama')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('buku_ids')
                ->label('Buku')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(fn() => Buku::query()->where('stok', '>', 0)->pluck('judul', 'id'))
                ->helperText('Hanya menampilkan buku dengan stok tersedia. Validasi limit peminjaman aktif & status suspend dicek otomatis saat submit.')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nama')
                    ->label('Peminjam')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('buku.judul')
                    ->label('Buku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_pinjam')
                    ->date()
                    ->sortable(),
                TextColumn::make('tanggal_jatuh_tempo')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(StatusPeminjaman $state) => match ($state) {
                        StatusPeminjaman::Aktif => 'success',
                        StatusPeminjaman::Terlambat => 'danger',
                        StatusPeminjaman::Selesai => 'gray',
                        StatusPeminjaman::Hilang => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(StatusPeminjaman::cases())->mapWithKeys(fn($s) => [$s->value => ucfirst($s->value)])),
            ])
            ->recordActions([
                Action::make('proses_pengembalian')
                    ->label('Proses Pengembalian')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn(Peminjaman $record) => in_array($record->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true))
                    ->schema([
                        Select::make('kondisi')
                            ->label('Kondisi Buku')
                            ->options(collect(KondisiBuku::cases())->mapWithKeys(fn($k) => [$k->value => ucfirst($k->value)]))
                            ->required(),
                        Textarea::make('catatan')
                            ->label('Catatan'),
                    ])
                    ->action(function (Peminjaman $record, array $data) {
                        try {
                            app(PeminjamanService::class)->prosesPengembalian(
                                peminjaman: $record,
                                kondisi: KondisiBuku::from($data['kondisi']),
                                catatan: $data['catatan'] ?? null,
                                diprosesOleh: auth()->user(),
                            );

                            Notification::make()
                                ->success()
                                ->title('Pengembalian berhasil diproses')
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal memproses pengembalian')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('laporkan_hilang')
                    ->label('Laporkan Hilang')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Buku belum dikembalikan secara fisik. Denda kehilangan penuh (Buku.harga_ganti) akan langsung dicatat.')
                    ->visible(fn(Peminjaman $record) => in_array($record->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true))
                    ->action(function (Peminjaman $record) {
                        try {
                            app(PeminjamanService::class)->laporkanHilang($record);

                            Notification::make()
                                ->success()
                                ->title('Peminjaman ditandai hilang, Denda tercatat')
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal melaporkan hilang')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPeminjamans::route('/'),
            'create' => Pages\CreatePeminjaman::route('/create'),
        ];
    }
}
