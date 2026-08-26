<?php

namespace App\Filament\Resources;

use App\Enums\KondisiBuku;
use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Filament\Exports\PeminjamanExporter;
use App\Filament\Resources\PeminjamanResource\Pages;
use App\Models\Eksemplar;
use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use RuntimeException;

class PeminjamanResource extends Resource
{
    protected static ?string $model = Peminjaman::class;

    protected static ?string $navigationLabel = 'Peminjaman';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Peminjaman Manual')
                ->columns(2)
                ->columnSpanFull()
                ->description('Fallback jika device RFID/scan barcode error. Validasi limit peminjaman aktif & status suspend dicek otomatis saat submit oleh PeminjamanService.')
                ->schema([
                    Select::make('user_id')
                        ->label('Peminjam')
                        ->relationship('user', 'nama')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->validationMessages([
                            'required' => 'Peminjam wajib dipilih.',
                        ]),
                    Select::make('eksemplar_ids')
                        ->label('Eksemplar (scan barcode / pilih)')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => Eksemplar::query()
                            ->where('status', StatusEksemplar::Tersedia)
                            ->with('buku')
                            ->get()
                            ->mapWithKeys(fn ($e) => [$e->id => "{$e->buku->judul} — {$e->barcode}"]))
                        ->helperText('Hanya menampilkan eksemplar berstatus tersedia. Validasi limit peminjaman aktif & status suspend dicek otomatis saat submit.')
                        ->required()
                        ->validationMessages([
                            'required' => 'Pilih minimal satu eksemplar untuk dipinjam.',
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(PeminjamanExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Peminjaman::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')
                    ->label('Peminjam')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('eksemplar.buku.judul')
                    ->label('Buku')
                    ->placeholder('(eksemplar sudah dihapus permanen)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_pinjam')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('tanggal_jatuh_tempo')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (StatusPeminjaman $state) => match ($state) {
                        StatusPeminjaman::Aktif => 'success',
                        StatusPeminjaman::Terlambat => 'danger',
                        StatusPeminjaman::Selesai => 'gray',
                        StatusPeminjaman::Hilang => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(StatusPeminjaman::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])),
            ])
            ->recordActions([
                Action::make('proses_pengembalian')
                    ->label('Proses Pengembalian')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->authorize(fn (Peminjaman $record) => auth()->user()?->can('update', $record) ?? false)
                    ->visible(fn (Peminjaman $record) => in_array($record->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true))
                    ->schema([
                        Select::make('kondisi')
                            ->label('Kondisi Buku')
                            ->options(collect(KondisiBuku::cases())->mapWithKeys(fn ($k) => [$k->value => ucfirst($k->value)]))
                            ->required()
                            ->validationMessages([
                                'required' => 'Kondisi buku wajib dipilih.',
                            ]),
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
                    ->authorize(fn (Peminjaman $record) => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalDescription('Buku belum dikembalikan secara fisik. Denda kehilangan penuh (Buku.harga_ganti) akan langsung dicatat.')
                    ->visible(fn (Peminjaman $record) => in_array($record->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true))
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
