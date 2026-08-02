<?php

namespace App\Filament\Resources\BukuResource\RelationManagers;

use App\Enums\KondisiBuku;
use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Enums\TipeDenda;
use App\Filament\Exports\EksemplarExporter;
use App\Filament\Imports\EksemplarImporter;
use App\Jobs\GenerateLabelBarcodePdfJob;
use App\Models\Eksemplar;
use App\Services\LabelBarcodeService;
use App\Services\PeminjamanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class EksemplarsRelationManager extends RelationManager
{
    protected static string $relationship = 'eksemplars';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('barcode')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Select::make('rak_id')
                ->label('Rak')
                ->relationship('rak', 'nama')
                ->searchable()
                ->preload(),
            Select::make('status')
                ->options(collect(StatusEksemplar::cases())->mapWithKeys(fn($s) => [$s->value => ucfirst($s->value)]))
                ->required()
                ->default(StatusEksemplar::Tersedia->value)
                ->helperText('Ubah manual hanya untuk koreksi data - alur normal status diubah otomatis oleh PeminjamanService.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('barcode')
            // BARU (iterasi ini) - eager load relasi yang dipakai kolom
            // Peminjam/Denda/Refund di bawah, supaya tidak N+1 query per
            // baris (Aturan poin 3/9 - performa).
            ->modifyQueryUsing(fn(Builder $query) => $query->with([
                'peminjamanTerakhir.user',
                'peminjamanTerakhir.dendas',
            ]))
            ->headerActions([
                ImportAction::make()
                    ->importer(EksemplarImporter::class)
                    ->authorize(fn() => auth()->user()?->can('create', Eksemplar::class) ?? false),
                ExportAction::make()
                    ->exporter(EksemplarExporter::class)
                    ->authorize(fn() => auth()->user()?->can('viewAny', Eksemplar::class) ?? false),
            ])
            ->columns([
                TextColumn::make('barcode')->searchable(),
                TextColumn::make('rak.nama')->label('Rak'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(StatusEksemplar $state) => match ($state) {
                        StatusEksemplar::Tersedia => 'success',
                        StatusEksemplar::Dipinjam => 'warning',
                        StatusEksemplar::Rusak, StatusEksemplar::Hilang => 'danger',
                    }),
                // BARU (iterasi ini) - hanya relevan untuk eksemplar
                // Rusak/Hilang, tapi ditampilkan apa adanya (placeholder '-'
                // untuk status lain) supaya tidak perlu logic visible/hidden
                // per kolom yang rumit di Filament table.
                TextColumn::make('peminjamanTerakhir.user.nama')
                    ->label('Dipinjam/Dirusak/Dihilangkan Oleh')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status_denda')
                    ->label('Status Denda')
                    ->state(fn(Eksemplar $record) => static::labelStatusDenda($record))
                    ->badge()
                    ->color(fn(Eksemplar $record) => match (static::labelStatusDenda($record)) {
                        'Lunas' => 'success',
                        'Belum Lunas' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status_refund_denda')
                    ->label('Status Refund')
                    ->state(fn(Eksemplar $record) => static::dendaTerkait($record)?->status_refund?->value)
                    ->formatStateUsing(fn(?string $state) => $state ? ucfirst(str_replace('_', ' ', $state)) : null)
                    ->badge()
                    ->color(fn(?string $state) => match ($state) {
                        'perlu_refund' => 'warning',
                        'sudah_direfund' => 'success',
                        default => 'gray',
                    })
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(StatusEksemplar::cases())->mapWithKeys(fn($s) => [$s->value => ucfirst($s->value)])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->disabled(fn(Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam)
                    ->tooltip(fn(Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam
                        ? 'Eksemplar sedang dipinjam - tidak bisa diedit manual di sini.'
                        : null),
                DeleteAction::make()
                    ->disabled(fn(Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam)
                    ->tooltip(fn(Eksemplar $record) => $record->status === StatusEksemplar::Dipinjam
                        ? 'Eksemplar sedang dipinjam - tidak bisa dihapus.'
                        : null),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->action(function (Eksemplar $record) {
                        $adaPeminjamanBerjalan = $record->peminjamans()
                            ->whereIn('status', [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat])
                            ->exists();

                        if ($adaPeminjamanBerjalan) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak bisa dihapus permanen')
                                ->body('Eksemplar ini masih punya Peminjaman Aktif/Terlambat. Selesaikan/kembalikan dulu sebelum force delete.')
                                ->send();

                            return;
                        }

                        $record->forceDelete();
                    }),
                // BARU (iterasi ini) - satu tombol menangani DUA jalur data
                // "Hilang" (lihat penjelasan di respons, TODO: ASUMSI
                // otorisasi memakai 'Update:Pengembalian' - lihat catatan di
                // PeminjamanService::bukuDitemukanKembali()):
                // 1. Ada Pengembalian (hilang dilaporkan via proses
                //    pengembalian normal) -> koreksiKondisiPengembalian().
                // 2. Tidak ada Pengembalian (hilang via laporkanHilang() saat
                //    masih dipinjam) -> bukuDitemukanKembali().
                Action::make('buku_ditemukan_kembali')
                    ->label('Buku Ditemukan Kembali')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn(Eksemplar $record) => $record->status === StatusEksemplar::Hilang)
                    ->requiresConfirmation()
                    ->modalDescription('Eksemplar akan dikembalikan ke status Tersedia dan Denda kehilangan terkait dibatalkan. Jika Denda sudah lunas, refund manual tetap perlu diproses pustakawan.')
                    ->authorize(fn() => auth()->user()?->can('Update:Pengembalian') ?? false)
                    ->action(function (Eksemplar $record) {
                        $peminjaman = $record->peminjamanTerakhir;

                        if (! $peminjaman || $peminjaman->status !== StatusPeminjaman::Hilang) {
                            Notification::make()
                                ->danger()
                                ->title('Data Peminjaman tidak konsisten')
                                ->body('Eksemplar berstatus Hilang tapi Peminjaman terkait tidak ditemukan/tidak berstatus Hilang. Periksa data secara manual.')
                                ->send();

                            return;
                        }

                        try {
                            if ($peminjaman->pengembalian) {
                                app(PeminjamanService::class)->koreksiKondisiPengembalian(
                                    pengembalian: $peminjaman->pengembalian,
                                    kondisiBaru: KondisiBuku::Baik,
                                    diprosesOleh: auth()->user(),
                                );
                            } else {
                                app(PeminjamanService::class)->bukuDitemukanKembali($peminjaman);
                            }

                            Notification::make()
                                ->success()
                                ->title('Eksemplar ditandai ditemukan kembali')
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal memproses')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                // BARU - cetak 1 label barcode langsung dari baris tabel.
                // Reuse ability 'view' Eksemplar (tidak ada permission baru,
                // Aturan poin 15 - tidak mengubah skema/otorisasi).
                Action::make('cetak_label')
                    ->label('Cetak Label')
                    ->icon('heroicon-o-printer')
                    ->authorize(fn(Eksemplar $record) => auth()->user()?->can('view', $record) ?? false)
                    ->action(function (Eksemplar $record, LabelBarcodeService $service) {
                        $record->loadMissing('buku');
                        $labels = $service->generateData(collect([$record]));

                        $pdf = Pdf::loadView('pdf.label-barcode', ['labels' => $labels])
                            ->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn() => print($pdf->output()),
                            "label-{$record->barcode}.pdf",
                            ['Content-Type' => 'application/pdf'],
                        );
                    }),
            ])
            ->toolbarActions([
                // Cetak label massal dari eksemplar terpilih -DIPINDAH ke
                // queue 'default' (dikonfirmasi, konsisten dengan
                // BukuResource::cetak_label_massal). Reuse ability
                // 'viewAny' Eksemplar, tidak ada permission baru.
                BulkAction::make('cetak_label_massal')
                    ->label('Cetak Label (Massal)')
                    ->icon('heroicon-o-printer')
                    ->authorize(fn() => auth()->user()?->can('viewAny', Eksemplar::class) ?? false)
                    ->action(function (Collection $records) {
                        GenerateLabelBarcodePdfJob::dispatch(
                            $records->pluck('id')->all(),
                            (string) auth()->id(),
                        );

                        Notification::make()
                            ->info()
                            ->title('Sedang memproses label barcode')
                            ->body('Anda akan menerima notifikasi begitu PDF siap diunduh.')
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    /**
     * dihitung sekali, dipakai kolom Status Denda & Status Refund supaya
     * tidak duplikasi logic pencarian Denda terkait (Aturan poin 3).
     */
    protected static function dendaTerkait(Eksemplar $record): ?\App\Models\Denda
    {
        $tipe = match ($record->status) {
            StatusEksemplar::Rusak => TipeDenda::Kerusakan,
            StatusEksemplar::Hilang => TipeDenda::Kehilangan,
            default => null,
        };

        if (! $tipe || ! $record->peminjamanTerakhir) {
            return null;
        }

        return $record->peminjamanTerakhir->dendas
            ->where('tipe', $tipe)
            ->sortByDesc('created_at')
            ->first();
    }

    protected static function labelStatusDenda(Eksemplar $record): ?string
    {
        $denda = static::dendaTerkait($record);

        if (! $denda) {
            return null;
        }

        return $denda->status_lunas ? 'Lunas' : 'Belum Lunas';
    }
}
