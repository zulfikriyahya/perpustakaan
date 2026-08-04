<?php

namespace App\Filament\Resources;

use App\Filament\Exports\RakExporter;
use App\Filament\Imports\RakImporter;
use App\Filament\Resources\RakResource\Pages;
use App\Filament\Resources\RakResource\RelationManagers\EksemplarsRelationManager;
use App\Models\Eksemplar;
use App\Models\Rak;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RakResource extends Resource
{
    protected static ?string $model = Rak::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Rak';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Rak')
                ->description('Identitas dan lokasi fisik rak penyimpanan.')
                ->columns(2)
                ->schema([
                    TextInput::make('nama')
                        ->required()
                        ->maxLength(255)
                        ->validationMessages([
                            'required' => 'Nama rak wajib diisi.',
                            'max' => 'Nama rak maksimal 255 karakter.',
                        ]),
                    TextInput::make('lokasi')
                        ->maxLength(255)
                        ->helperText('Opsional - mis. "Lantai 2, Sisi Timur".')
                        ->validationMessages([
                            'max' => 'Lokasi maksimal 255 karakter.',
                        ]),
                ]),

            Section::make('Kategori Terkait')
                ->description('Kategori buku yang biasanya ditempatkan di rak ini.')
                ->schema([
                    Select::make('kategoris')
                        ->label('Kategori Terkait')
                        ->relationship('kategoris', 'nama')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        // BARU - createOptionForm timbal-balik dengan Kategori
                        // (KategoriResource sudah punya untuk Rak), supaya
                        // Pustakawan tidak perlu pindah halaman saat mengisi data
                        // rak baru sekaligus kategori barunya (Aturan gap ini).
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
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()->importer(RakImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Rak::class) ?? false),
                ExportAction::make()->exporter(RakExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Rak::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('lokasi')->searchable(),
                TextColumn::make('eksemplars_count')->label('Jumlah Eksemplar')->counts('eksemplars')->sortable(),
                TextColumn::make('stok_tersedia')
                    ->label('Stok Tersedia')
                    ->state(fn (Rak $record) => $record->stokTersedia())
                    ->badge()
                    ->color(fn (Rak $record) => $record->stokTersedia() > 0 ? 'success' : 'danger'),
                TextColumn::make('jumlah_judul_unik')
                    ->label('Jumlah Judul Unik')
                    ->state(fn (Rak $record) => $record->jumlahJudulUnik())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime('d F Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->action(function (Rak $record) {
                        $dipakai = Eksemplar::query()->withTrashed()
                            ->where('rak_id', $record->id)->exists();

                        if ($dipakai) {
                            Notification::make()
                                ->danger()->title('Tidak bisa dihapus permanen')
                                ->body('Rak ini masih dipakai Eksemplar - pindahkan dulu Eksemplar ke Rak lain.')
                                ->send();

                            return;
                        }

                        $record->forceDelete();
                    }),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
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
            'index' => Pages\ListRaks::route('/'),
            'create' => Pages\CreateRak::route('/create'),
            'edit' => Pages\EditRak::route('/{record}/edit'),
        ];
    }
}
