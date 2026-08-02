<?php

namespace App\Filament\Resources;

use App\Enums\JenisTransaksi;
use App\Enums\StatusRefund;
use App\Enums\TipeDenda;
use App\Filament\Exports\DendaExporter;
use App\Filament\Resources\DendaResource\Pages;
use App\Models\Denda;
use App\Models\Transaksi;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
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
                    ->exporter(DendaExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Denda::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                // BUG FIX (iterasi ini): 'peminjaman.buku.judul' DIHAPUS -
                // Peminjaman tidak lagi punya relasi langsung ke Buku sejak
                // migration 2026_08_02_000002-000004. Diganti
                // 'peminjaman.eksemplar.buku.judul', konsisten dengan
                // PengembalianResource yang sudah benar.
                TextColumn::make('peminjaman.eksemplar.buku.judul')
                    ->label('Buku')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tipe')
                    ->badge()
                    ->color(fn (TipeDenda $state) => match ($state) {
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
                    ->formatStateUsing(fn (bool $state) => $state ? 'Lunas' : 'Belum Lunas')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                TextColumn::make('tanggal_lunas')
                    ->dateTime('d F Y H:i')
                    ->toggleable(),
                TextColumn::make('status_refund')
                    ->label('Refund')
                    ->badge()
                    ->color(fn (StatusRefund $state) => match ($state) {
                        StatusRefund::TidakPerlu => 'gray',
                        StatusRefund::PerluRefund => 'warning',
                        StatusRefund::SudahDirefund => 'success',
                    })
                    ->toggleable(),
                TextColumn::make('keterangan')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->options(collect(TipeDenda::cases())->mapWithKeys(fn ($t) => [$t->value => ucfirst($t->value)])),
                TernaryFilter::make('status_lunas')->label('Status Lunas'),
                SelectFilter::make('status_refund')
                    ->label('Status Refund')
                    ->options(collect(StatusRefund::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('tandai_lunas')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    // TODO: ASUMSI - Pustakawan boleh tandai lunas (setara
                    // proses pembayaran di meja), sama seperti pola akses
                    // Aksi "Proses Pengembalian" - perlu dikonfirmasi.
                    ->authorize(fn (Denda $record) => auth()->user()?->can('update', $record) ?? false)
                    ->visible(fn (Denda $record) => ! $record->status_lunas)
                    ->requiresConfirmation()
                    ->schema([
                        DateTimePicker::make('tanggal_lunas')
                            ->label('Tanggal Lunas')
                            ->default(now())
                            ->required(),
                        Textarea::make('keterangan')
                            ->label('Catatan')
                            ->default(fn (Denda $record) => $record->keterangan),
                    ])
                    ->action(function (Denda $record, array $data) {
                        // dipicu DendaObserver::updated() -> cek auto-unsuspend user
                        $record->update([
                            'status_lunas' => true,
                            'tanggal_lunas' => $data['tanggal_lunas'],
                            'keterangan' => $data['keterangan'] ?? $record->keterangan,
                        ]);

                        // FITUR BARU: catat Transaksi jenis pembayaran_denda -
                        // satu sumber kebenaran pembuatan Transaksi tipe ini,
                        // jangan duplikasi di tempat lain (Aturan poin 3).
                        Transaksi::create([
                            'user_id' => $record->user_id,
                            'jenis' => JenisTransaksi::PembayaranDenda,
                            'diproses_oleh' => auth()->id(),
                            'tanggal' => $data['tanggal_lunas'],
                            'keterangan' => "Pembayaran Denda {$record->tipe->value} sebesar Rp".number_format((float) $record->nominal, 0, ',', '.'),
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
                    ->authorize(fn () => auth()->user()?->hasRole('super_admin') ?? false)
                    ->schema([
                        Select::make('status_refund')
                            ->label('Status Refund')
                            ->options(collect(StatusRefund::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst(str_replace('_', ' ', $s->value))]))
                            ->required(),
                    ])
                    ->action(function (Denda $record, array $data) {
                        $record->update(['status_refund' => $data['status_refund']]);

                        Notification::make()
                            ->success()
                            ->title('Status refund diperbarui')
                            ->send();
                    }),

                DeleteAction::make(),
                RestoreAction::make(),
                // TODO: GAP-SPEC - blokir force-delete jika status_lunas masih
                // false dan nominal > 0 (hutang belum selesai) - jejak
                // keuangan yang belum tuntas tidak boleh dimusnahkan permanen.
                ForceDeleteAction::make()
                    ->action(function (Denda $record) {
                        if (! $record->status_lunas && (float) $record->nominal > 0) {
                            Notification::make()->danger()->title('Tidak bisa dihapus permanen')
                                ->body('Denda ini belum lunas - selesaikan pembayaran/pembatalan dulu.')->send();

                            return;
                        }

                        $record->forceDelete();
                    }),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
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
