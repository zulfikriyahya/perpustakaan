<?php

namespace App\Filament\Resources;

use App\Enums\StatusPeminjaman;
use App\Filament\Exports\BukuExporter;
use App\Filament\Imports\BukuImporter;
use App\Filament\Resources\BukuResource\Pages;
use App\Filament\Resources\BukuResource\RelationManagers\EksemplarsRelationManager;
use App\Jobs\GenerateLabelBarcodePdfJob;
use App\Models\Buku;
use App\Models\Eksemplar;
use App\Models\Rak;
use App\Services\LabelBarcodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class BukuResource extends Resource
{
    protected static ?string $model = Buku::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Buku';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('judul')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            FileUpload::make('cover')
                ->image()
                ->directory('buku-cover'),
            TextInput::make('penulis')
                ->maxLength(255),
            TextInput::make('penerbit')
                ->maxLength(255),
            TextInput::make('isbn')
                ->label('ISBN')
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('1 ISBN = 1 judul. Jumlah eksemplar fisik dikelola di tab Eksemplar setelah buku disimpan.'),
            TextInput::make('tahun_terbit')
                ->label('Tahun Terbit')
                ->numeric()
                ->minValue(1000)
                ->maxValue((int) date('Y'))
                ->maxLength(4),
            Select::make('kategoris')
                ->label('Kategori')
                ->relationship('kategoris', 'nama')
                ->multiple()
                ->preload()
                ->searchable(),
            TextInput::make('harga_ganti')
                ->label('Harga Ganti')
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->helperText('Dipakai sebagai basis perhitungan Denda kerusakan/kehilangan untuk semua eksemplar judul ini.'),
            Textarea::make('deskripsi')
                ->columnSpanFull(),
            // GAP-SPEC ditutup: field non-persisten, hanya dipakai saat
            // create (lihat CreateBuku::afterCreate()) untuk sekaligus
            // membuat N Eksemplar baru - tidak ada kolom 'jumlah_eksemplar'
            // di tabel bukus, jadi dehydrated(false) dan disembunyikan di
            // context edit (Aturan poin 3 - ubah stok setelah create tetap
            // HANYA lewat tab Eksemplar/BukuImporter, bukan di sini).
            TextInput::make('jumlah_eksemplar_awal')
                ->label('Jumlah Eksemplar Awal')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->helperText('Opsional - langsung membuat N eksemplar berstatus Tersedia. Jumlah eksemplar SETELAH buku dibuat tetap dikelola lewat tab Eksemplar atau Import Buku.')
                ->dehydrated(false)
                ->visibleOn('create'),
            Select::make('rak_id_eksemplar_awal')
                ->label('Rak untuk Eksemplar Awal')
                ->options(fn () => Rak::query()->pluck('nama', 'id'))
                ->searchable()
                ->helperText('Opsional - rak yang sama dipakaikan ke semuaeksemplar awal yang dibuat.')
                ->dehydrated(false)
                ->visibleOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(BukuImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Buku::class) ?? false),
                ExportAction::make()
                    ->exporter(BukuExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Buku::class) ?? false),
            ])
            ->columns([
                ImageColumn::make('cover')
                    ->square(),
                TextColumn::make('judul')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('isbn')
                    ->label('ISBN')
                    ->searchable(),
                TextColumn::make('tahun_terbit')
                    ->label('Tahun')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('eksemplars_count')
                    ->label('Total Eksemplar')
                    ->counts('eksemplars')
                    ->sortable(),
                TextColumn::make('stok_tersedia')
                    ->label('Stok Tersedia')
                    ->state(fn (Buku $record) => $record->stokTersedia())
                    ->badge()
                    ->color(fn (Buku $record) => $record->stokTersedia() > 0 ? 'success' : 'danger'),
                TextColumn::make('harga_ganti')
                    ->label('Harga Ganti')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                DeleteAction::make(),
                ForceDeleteAction::make()
                    ->action(function (Buku $record) {
                        $adaPeminjamanBerjalan = Eksemplar::query()
                            ->withTrashed()
                            ->where('buku_id', $record->id)
                            ->whereHas('peminjamans', fn ($q) => $q->whereIn('status', [
                                StatusPeminjaman::Aktif,
                                StatusPeminjaman::Terlambat,
                            ]))
                            ->exists();

                        if ($adaPeminjamanBerjalan) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak bisa dihapus permanen')
                                ->body('Masih ada Peminjaman Aktif/Terlambat yang menggunakan eksemplar buku ini. Selesaikan/kembalikan dulu sebelum force delete.')
                                ->send();

                            return;
                        }

                        $record->forceDelete();
                    }),
                RestoreAction::make(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('kategoris')
                    ->label('Kategori')
                    ->relationship('kategoris', 'nama'),
            ])
            ->toolbarActions([
                // BARU - cetak label barcode lintas Buku terpilih. Ambil
                // SEMUA Eksemplar milik Buku-Buku yang dicentang (Aturan
                // poin 3 - reuse LabelBarcodeService, jangan duplikasi
                // logic generate barcode di sini).
                BulkAction::make('cetak_label_massal')
                    ->label('Cetak Label Eksemplar')
                    ->icon('heroicon-o-printer')
                    ->authorize(fn () => auth()->user()?->can('viewAny', Eksemplar::class) ?? false)
                    ->action(function (Collection $records, LabelBarcodeService $service) {
                        $eksemplars = Eksemplar::query()
                            ->whereIn('buku_id', $records->pluck('id'))
                            ->with('buku')
                            ->get();

                        if ($eksemplars->isEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('Tidak ada Eksemplar')
                                ->body('Buku yang dipilih belum punya Eksemplar untuk dicetak labelnya.')
                                ->send();

                            return;
                        }

                        $labels = $service->generateData($eksemplars);

                        $pdf = Pdf::loadView('pdf.label-barcode', ['labels' => $labels])
                            ->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn () => print ($pdf->output()),
                            'label-barcode-buku-'.now()->format('Ymd-His').'.pdf',
                            ['Content-Type' => 'application/pdf'],
                        );
                    })
                    ->deselectRecordsAfterCompletion(),

                // // BARU - generate PDF di background (queue 'default') agar
                // // tidak timeout HTTP saat Buku terpilih banyak. Hasil
                // // dikirim via Filament database notification (bell icon)
                // // dengan tombol Download - lihat GenerateLabelBarcodePdfJob.
                // BulkAction::make('cetak_label_massal')
                //     ->label('Cetak Label Eksemplar')
                //     ->icon('heroicon-o-printer')
                //     ->authorize(fn() => auth()->user()?->can('viewAny', Eksemplar::class) ?? false)
                //     ->action(function (Collection $records) {
                //         GenerateLabelBarcodePdfJob::dispatch(
                //             $records->pluck('id')->all(),
                //             (string) auth()->id(),
                //         );

                //         \Filament\Notifications\Notification::make()
                //             ->info()
                //             ->title('Sedang memproses label barcode')
                //             ->body('Anda akan menerima notifikasi begitu PDF siap diunduh.')
                //             ->send();
                //     })
                //     ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EksemplarsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBukus::route('/'),
            'create' => Pages\CreateBuku::route('/create'),
            'edit' => Pages\EditBuku::route('/{record}/edit'),
        ];
    }
}
