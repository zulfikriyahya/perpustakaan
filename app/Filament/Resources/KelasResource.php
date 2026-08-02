<?php

namespace App\Filament\Resources;

use App\Filament\Exports\KelasExporter;
use App\Filament\Imports\KelasImporter;
use App\Filament\Resources\KelasResource\Pages;
use App\Models\Kelas;
use App\Models\KelasTahunPelajaran;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class KelasResource extends Resource
{
    protected static ?string $model = Kelas::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Kelas';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama Kelas (mis. X IPA 1)')
                ->required()
                ->maxLength(255)
                ->unique(
                    table: 'kelas',
                    column: 'nama',
                    ignoreRecord: true,
                    modifyRuleUsing: fn ($rule, $get) => $rule
                        ->whereNull('deleted_at')
                        ->where('jurusan_id', $get('jurusan_id')),
                ),
            TextInput::make('tingkat')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->required()
                ->helperText('Angka tingkat, mis. 10, 11, 12 - dipakai untuk urutan kenaikan kelas.'),
            Select::make('jurusan_id')
                ->label('Jurusan')
                ->relationship('jurusan', 'nama')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->helperText('Setiap Kelas wajib punya Jurusan. Nama Kelas unik per Jurusan (boleh sama di Jurusan berbeda).'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()->importer(KelasImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Kelas::class) ?? false),
                ExportAction::make()->exporter(KelasExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Kelas::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('tingkat')->sortable(),
                TextColumn::make('jurusan.nama')->label('Jurusan')->toggleable(),
                TextColumn::make('created_at')->dateTime('d F Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jurusan_id')->label('Jurusan')->relationship('jurusan', 'nama'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                DeleteAction::make(),
                RestoreAction::make(),
                // TODO: GAP-SPEC - blokir force-delete jika ada KTP (termasuk
                // yang sudah di-soft-delete) di bawah Kelas ini yang masih
                // punya siswa aktif - mencegah cascade DB diam-diam
                // memutus assignment siswa yang sedang berjalan.
                ForceDeleteAction::make()
                    ->action(function (Kelas $record) {
                        $adaSiswaAktif = KelasTahunPelajaran::query()
                            ->withTrashed()
                            ->where('kelas_id', $record->id)
                            ->whereHas('siswaAktif')
                            ->exists();

                        if ($adaSiswaAktif) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak bisa dihapus permanen')
                                ->body('Masih ada siswa aktif di Kelas Tahun Pelajaran bawah Kelas ini.')
                                ->send();

                            return;
                        }

                        $record->forceDelete();
                    }),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKelas::route('/'),
            'create' => Pages\CreateKelas::route('/create'),
            'edit' => Pages\EditKelas::route('/{record}/edit'),
        ];
    }
}
