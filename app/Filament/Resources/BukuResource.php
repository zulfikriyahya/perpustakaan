<?php

namespace App\Filament\Resources;

use App\Filament\Exports\BukuExporter;
use App\Filament\Imports\BukuImporter;
use App\Filament\Resources\BukuResource\Pages;
use App\Filament\Resources\BukuResource\RelationManagers\EksemplarsRelationManager;
use App\Models\Buku;
use App\Models\Rak;
use Filament\Actions\DeleteAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

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
                // Import/Export Eksemplar TIDAK lagi ada di sini - dipindah
                // sepenuhnya ke EksemplarsRelationManager (satu lokasi,
                // sebelumnya duplikat di dua tempat dengan sumber
                // otorisasi berbeda - lihat EksemplarPolicy baru).
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
                // GAP-SPEC ditutup: "stok tersedia" = HANYA status Tersedia
                // (Buku::stokTersedia()) - Dipinjam, Rusak, DAN Hilang semua
                // dikecualikan, bukan cuma Dipinjam+Hilang. Dihitung
                // on-the-fly (bukan counts() bawaan) karena butuh filter
                // where status, bukan sekadar hitung semua baris relasi.
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
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('kategoris')
                    ->label('Kategori')
                    ->relationship('kategoris', 'nama'),
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
