<?php

namespace App\Filament\Resources;

use App\Enums\StatusRefund;
use App\Enums\TipeDenda;
use App\Filament\Resources\DendaResource\Pages;
use App\Models\Denda;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Denda SELALU dibuat otomatis oleh PeminjamanService (keterlambatan saat
 * pengembalian, kerusakan/kehilangan saat proses terkait) - tidak ada
 * Create/Edit page di Resource ini, sesuai pola PengembalianResource
 * (Aturan poin 3, DRY - tidak ada jalan lain mengubah data selain lewat
 * Service/Observer terpusat).
 *
 * TODO: GAP-SPEC - PeminjamanService::batalkanDenda() TIDAK men-set
 * status_refund ke 'perlu_refund' saat membatalkan Denda yang sudah
 * terbayar (lihat komentar di method tsb + migration
 * add_status_refund_to_dendas_table). Action 'update_status_refund' di
 * bawah adalah mitigasi manual sementara - Admin harus proaktif mengecek
 * kolom 'keterangan' untuk tahu ada Denda yang perlu direfund, sistem
 * TIDAK memberi notifikasi otomatis untuk ini. Perlu konfirmasi apakah
 * PeminjamanService perlu di-patch.
 */
class DendaResource extends Resource
{
    protected static ?string $model = Denda::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Denda';

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
                TextColumn::make('peminjaman.buku.judul')
                    ->label('Buku')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tipe')
                    ->badge()
                    ->color(fn(TipeDenda $state) => match ($state) {
                        TipeDenda::Keterlambatan => 'warning',
                        TipeDenda::Kerusakan => 'danger',
                        TipeDenda::Kehilangan => 'danger',
                    }),
                TextColumn::make('nominal')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status_lunas')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(bool $state) => $state ? 'Lunas' : 'Belum Lunas')
                    ->color(fn(bool $state) => $state ? 'success' : 'danger'),
                TextColumn::make('tanggal_lunas')
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('status_refund')
                    ->label('Refund')
                    ->badge()
                    ->color(fn(StatusRefund $state) => match ($state) {
                        StatusRefund::TidakPerlu => 'gray',
                        StatusRefund::PerluRefund => 'warning',
                        StatusRefund::SudahDirefund => 'success',
                    })
                    ->toggleable(),
                TextColumn::make('keterangan')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->options(collect(TipeDenda::cases())->mapWithKeys(fn($t) => [$t->value => ucfirst($t->value)])),
                TernaryFilter::make('status_lunas')
                    ->label('Status Lunas'),
                SelectFilter::make('status_refund')
                    ->label('Status Refund')
                    ->options(collect(StatusRefund::cases())->mapWithKeys(fn($s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))])),
            ])
            ->recordActions([
                Action::make('tandai_lunas')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    // TODO: ASUMSI - Pustakawan boleh tandai lunas (setara
                    // proses pembayaran di meja), sama seperti pola akses
                    // Aksi "Proses Pengembalian" - perlu dikonfirmasi.
                    ->authorize(fn(Denda $record) => auth()->user()?->can('update', $record) ?? false)
                    ->visible(fn(Denda $record) => ! $record->status_lunas)
                    ->requiresConfirmation()
                    ->schema([
                        DateTimePicker::make('tanggal_lunas')
                            ->label('Tanggal Lunas')
                            ->default(now())
                            ->required(),
                        Textarea::make('keterangan')
                            ->label('Catatan')
                            ->default(fn(Denda $record) => $record->keterangan),
                    ])
                    ->action(function (Denda $record, array $data) {
                        // dipicu DendaObserver::updated() -> cek auto-unsuspend user
                        $record->update([
                            'status_lunas' => true,
                            'tanggal_lunas' => $data['tanggal_lunas'],
                            'keterangan' => $data['keterangan'] ?? $record->keterangan,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Denda ditandai lunas')
                            ->send();
                    }),

                Action::make('update_status_refund')
                    ->label('Update Status Refund')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    // Sengaja HANYA super_admin (bukan permission 'update'
                    // biasa) - lihat TODO: GAP-SPEC di atas class, ini
                    // mitigasi manual untuk gap yang belum ada alur
                    // otomatisnya, jadi dibatasi lebih ketat dari Update:Denda.
                    ->authorize(fn() => auth()->user()?->hasRole('super_admin') ?? false)
                    ->schema([
                        Select::make('status_refund')
                            ->label('Status Refund')
                            ->options(collect(StatusRefund::cases())->mapWithKeys(fn($s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))]))
                            ->required(),
                    ])
                    ->action(function (Denda $record, array $data) {
                        $record->update(['status_refund' => $data['status_refund']]);

                        Notification::make()
                            ->success()
                            ->title('Status refund diperbarui')
                            ->send();
                    }),

                DeleteAction::make(), // digerbang DendaPolicy::delete() - hanya Admin, lihat ShieldSeeder
            ])
            ->toolbarActions([
                DeleteBulkAction::make(), // digerbang DendaPolicy::deleteAny()
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDendas::route('/'),
        ];
    }
}
