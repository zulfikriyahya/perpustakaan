<?php

namespace App\Filament\Resources;

use App\Enums\JenisFileBuku;
use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Filament\Exports\BukuExporter;
use App\Filament\Imports\BukuImporter;
use App\Filament\Resources\BukuResource\Pages;
use App\Filament\Resources\BukuResource\RelationManagers\EksemplarsRelationManager;
use App\Jobs\GenerateLabelBarcodePdfJob;
use App\Models\Buku;
use App\Models\Eksemplar;
use App\Models\Rak;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * TODO: ASUMSI - dipakai Section (bukan Wizard) untuk mengompakkan form,
 * konsisten dengan alasan yang sama di UserResource: form ini dipakai untuk
 * create DAN edit di satu halaman, dan Section "Eksemplar Awal" hanya
 * relevan/visible saat create (->visibleOn('create')) sehingga alur Wizard
 * bertahap kurang cocok untuk mode edit. Beri tahu jika sebenarnya
 * diinginkan Wizard khusus create.
 */
class BukuResource extends Resource
{
    protected static ?string $model = Buku::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Buku';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('judul')
                        ->required()
                        ->maxLength(255)
                        ->validationMessages([
                            'required' => 'Judul buku wajib diisi.',
                            'max' => 'Judul buku maksimal 255 karakter.',
                        ]),
                    FileUpload::make('cover')
                        ->image()
                        ->disk('public')
                        ->directory('buku-cover'),
                    TextInput::make('penulis')
                        ->maxLength(255)
                        ->validationMessages([
                            'max' => 'Nama penulis maksimal 255 karakter.',
                        ]),
                    TextInput::make('penerbit')
                        ->maxLength(255)
                        ->validationMessages([
                            'max' => 'Nama penerbit maksimal 255 karakter.',
                        ]),
                    TextInput::make('isbn')
                        ->label('ISBN')
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                        ->maxLength(255)
                        ->helperText('1 ISBN = 1 judul. Jumlah eksemplar fisik dikelola di tab Eksemplar setelah buku disimpan.')
                        ->validationMessages([
                            'unique' => 'ISBN ini sudah dipakai judul buku lain yang masih aktif.',
                            'max' => 'ISBN maksimal 255 karakter.',
                        ]),
                    TextInput::make('tahun_terbit')
                        ->label('Tahun Terbit')
                        ->numeric()
                        ->minValue(1000)
                        ->maxValue((int) date('Y'))
                        ->maxLength(4)
                        ->validationMessages([
                            'numeric' => 'Tahun terbit harus berupa angka.',
                            'min' => 'Tahun terbit tidak valid.',
                            'max' => 'Tahun terbit tidak boleh lebih dari tahun berjalan.',
                        ]),
                ]),

            Section::make('Klasifikasi & Harga')
                ->columns(2)
                ->schema([
                    Select::make('kategoris')
                        ->label('Kategori')
                        ->relationship('kategoris', 'nama')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('nama')
                                ->required()
                                ->maxLength(255)
                                ->validationMessages([
                                    'required' => 'Nama kategori wajib diisi.',
                                ]),
                            Textarea::make('deskripsi')
                                ->columnSpanFull(),
                        ]),
                    Select::make('authors')
                        ->label('Penulis (Author)')
                        ->relationship('authors', 'nama')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->helperText('Terpisah dari kolom "Penulis" lama - dipakai untuk halaman Authors publik.'),
                    TextInput::make('harga_ganti')
                        ->label('Harga Ganti')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->required()
                        ->helperText('Dipakai sebagai basis perhitungan Denda kerusakan/kehilangan untuk semua eksemplar judul ini.')
                        ->validationMessages([
                            'required' => 'Harga ganti wajib diisi.',
                            'numeric' => 'Harga ganti harus berupa angka.',
                            'min' => 'Harga ganti tidak boleh negatif.',
                        ]),
                    Textarea::make('deskripsi')
                        ->columnSpanFull(),
                ]),

            // GAP-SPEC ditutup: field non-persisten, hanya dipakai saat
            // create (lihat CreateBuku::afterCreate()) untuk sekaligus
            // membuat N Eksemplar baru - tidak ada kolom 'jumlah_eksemplar'
            // di tabel bukus, jadi dehydrated(false) dan disembunyikan di
            // context edit (Aturan poin 3 - ubah stok setelah create tetap
            // HANYA lewat tab Eksemplar/BukuImporter, bukan disini).
            Section::make('Eksemplar Awal')
                ->description('Opsional - langsung membuat N eksemplar berstatus Tersedia saat buku dibuat.')
                ->columns(2)
                ->visibleOn('create')
                ->schema([
                    TextInput::make('jumlah_eksemplar_awal')
                        ->label('Jumlah Eksemplar Awal')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Jumlah eksemplar SETELAH buku dibuat tetap dikelola lewat tab Eksemplar atau Import Buku.')
                        ->dehydrated(false)
                        ->validationMessages([
                            'numeric' => 'Jumlah eksemplar awal harus berupa angka.',
                            'min' => 'Jumlah eksemplar awal tidak boleh negatif.',
                        ]),
                    Select::make('rak_id_eksemplar_awal')
                        ->label('Rak untuk Eksemplar Awal')
                        ->options(fn () => Rak::query()->pluck('nama', 'id'))
                        ->searchable()
                        ->helperText('Rak yang sama dipakaikan ke semua eksemplar awal yang dibuat.')
                        ->dehydrated(false),
                ]),
            Section::make('File Digital (E-book / Audiobook)')
                ->description('Unggah PDF/EPUB untuk e-book atau MP3/WAV untuk audiobook. Bisa lebih dari satu file/jenis.')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('files')
                        ->relationship('files')
                        ->schema([
                            Select::make('jenis')
                                ->options(collect(JenisFileBuku::cases())->mapWithKeys(
                                    fn ($c) => [$c->value => $c->label()]
                                ))
                                ->required(),
                            TextInput::make('nama_file')
                                ->label('Nama Tampilan')
                                ->maxLength(255),
                            FileUpload::make('path')
                                ->label('File')
                                ->disk('public')
                                ->directory('buku-files')
                                ->acceptedFileTypes(['application/pdf', 'application/epub+zip', 'audio/mpeg', 'audio/wav'])
                                ->required()
                                ->storeFileNamesIn('nama_file_asli')
                                ->dehydrateStateUsing(fn ($state) => $state), // TODO: verifikasi signature FileUpload disk custom terhadap Filament ^5.7
                            TextInput::make('urutan')
                                ->numeric()
                                ->default(0)
                                ->helperText('Untuk urutan track audiobook.'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Tambah File')
                        ->reorderable('urutan')
                        ->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'eksemplars as jumlah_eksemplar_aktif' => fn ($q) => $q->where('status', '!=', StatusEksemplar::Hilang),
                'eksemplars as jumlah_stok_tersedia' => fn ($q) => $q->where('status', StatusEksemplar::Tersedia),
                'eksemplars as jumlah_eksemplar_rusak' => fn ($q) => $q->where('status', StatusEksemplar::Rusak),
                'eksemplars as jumlah_eksemplar_hilang' => fn ($q) => $q->where('status', StatusEksemplar::Hilang),
            ]))
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
                TextColumn::make('jumlah_eksemplar_aktif')
                    ->label('Jumlah Buku')
                    ->description(fn (Buku $record) => $record->jumlah_eksemplar_hilang > 0
                        ? "{$record->jumlah_eksemplar_hilang} hilang (tidak dihitung)"
                        : null)
                    ->badge()
                    ->color(fn (Buku $record) => $record->jumlah_eksemplar_aktif > 0 ? 'gray' : 'danger')
                    ->sortable(),
                TextColumn::make('jumlah_stok_tersedia')
                    ->label('Stok Tersedia')
                    ->badge()
                    ->color(fn (Buku $record) => $record->jumlah_stok_tersedia > 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('eksemplar_bermasalah')
                    ->label('Rusak/Hilang')
                    ->state(fn (Buku $record) => $record->jumlah_eksemplar_rusak + $record->jumlah_eksemplar_hilang)
                    ->badge()
                    ->color(fn (Buku $record) => ($record->jumlah_eksemplar_rusak + $record->jumlah_eksemplar_hilang) > 0 ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('harga_ganti')
                    ->label('Harga Ganti')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d F Y H:i')
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
                BulkAction::make('cetak_label_massal')
                    ->label('Cetak Label Eksemplar')
                    ->icon('heroicon-o-printer')
                    ->authorize(fn () => auth()->user()?->can('viewAny', Eksemplar::class) ?? false)
                    ->action(function (Collection $records) {
                        $eksemplarIds = Eksemplar::query()
                            ->whereIn('buku_id', $records->pluck('id'))
                            ->pluck('id')
                            ->all();

                        if (empty($eksemplarIds)) {
                            Notification::make()
                                ->warning()
                                ->title('Tidak ada Eksemplar')
                                ->body('Buku yang dipilih belum punya Eksemplar untuk dicetak labelnya.')
                                ->send();

                            return;
                        }

                        GenerateLabelBarcodePdfJob::dispatch($eksemplarIds, (string) auth()->id());

                        Notification::make()
                            ->info()
                            ->title('Sedang memproses label barcode')
                            ->body('Anda akan menerima notifikasi begitu PDF siap diunduh.')
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
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
