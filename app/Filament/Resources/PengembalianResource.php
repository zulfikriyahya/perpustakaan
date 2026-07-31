<?php

namespace App\Filament\Resources;

use App\Enums\KondisiBuku;
use App\Filament\Exports\PengembalianExporter;
use App\Filament\Resources\PengembalianResource\Pages;
use App\Models\Pengembalian;
use App\Services\PeminjamanService;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
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
 * Tidak ada halaman Create/Edit - Pengembalian adalah HASIL dari
 * PeminjamanService::prosesPengembalian() (dipicu Action "Proses
 * Pengembalian" di PeminjamanResource).
 *
 * SATU pengecualian: Action "Koreksi Kondisi" di tabel ini, untuk kasus
 * Pustakawan salah input kondisi saat transaksi cepat (lihat gap iterasi
 * ini). Sengaja berupa Action terbatas (bukan Edit page penuh) - field yang
 * bisa diubah cuma 'kondisi' + 'catatan', supaya lebih mudah di-audit.
 * Seluruh efek samping (stok, Denda, status Peminjaman) wajib lewat
 * PeminjamanService::koreksiKondisiPengembalian() (Aturan poin 3, DRY).
 *
 * TODO: ShieldSeeder perlu diberi permission 'Update:Pengembalian' untuk
 * role Pustakawan DAN Admin (dikonfirmasi keduanya boleh koreksi) - Action
 * ini digerbang oleh PengembalianPolicy::update().
 */
class PengembalianResource extends Resource
{
    protected static ?string $model = Pengembalian::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Pengembalian';

    protected static string|\UnitEnum|null $navigationGroup = 'Operasional';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(PengembalianExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Pengembalian::class) ?? false),
            ])
            ->columns([
                TextColumn::make('peminjaman.user.nama')
                    ->label('Peminjam')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('peminjaman.buku.judul')
                    ->label('Buku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_kembali')
                    ->date()
                    ->sortable(),
                TextColumn::make('kondisi')
                    ->badge()
                    ->color(fn (KondisiBuku $state) => match ($state) {
                        KondisiBuku::Baik => 'success',
                        KondisiBuku::Rusak => 'warning',
                        KondisiBuku::Hilang => 'danger',
                    }),
                TextColumn::make('catatan')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('diprosesOleh.nama')
                    ->label('Diproses Oleh')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('kondisi')
                    ->options(collect(KondisiBuku::cases())->mapWithKeys(fn ($k) => [$k->value => ucfirst($k->value)])),
            ])
            ->recordActions([
                Action::make('koreksi_kondisi')
                    ->label('Koreksi Kondisi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->authorize(fn (Pengembalian $record) => auth()->user()?->can('update', $record) ?? false)
                    // dijaga PengembalianPolicy - lihat TODO ShieldSeeder di atas
                    ->requiresConfirmation()
                    ->modalDescription('Mengubah kondisi akan otomatis menyesuaikan stok dan Denda terkait (batalkan Denda lama, catat Denda baru jika perlu). Ini tidak bisa dibatalkan lewat tombol undo.')
                    ->schema(fn (Pengembalian $record) => [
                        Select::make('kondisi_baru')
                            ->label('Kondisi Baru')
                            ->options(
                                collect(KondisiBuku::cases())
                                    ->reject(fn ($k) => $k === $record->kondisi)
                                    ->mapWithKeys(fn ($k) => [$k->value => ucfirst($k->value)])
                            )
                            ->required(),
                        Textarea::make('catatan')
                            ->label('Catatan Koreksi')
                            ->default($record->catatan),
                    ])
                    ->action(function (Pengembalian $record, array $data) {
                        try {
                            app(PeminjamanService::class)->koreksiKondisiPengembalian(
                                pengembalian: $record,
                                kondisiBaru: KondisiBuku::from($data['kondisi_baru']),
                                catatan: $data['catatan'] ?? null,
                                diprosesOleh: auth()->user(),
                            );

                            Notification::make()
                                ->success()
                                ->title('Kondisi berhasil dikoreksi')
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal mengoreksi kondisi')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengembalians::route('/'),
        ];
    }
}
