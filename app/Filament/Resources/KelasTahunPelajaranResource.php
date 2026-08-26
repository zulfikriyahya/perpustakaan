<?php

namespace App\Filament\Resources;

use App\Filament\Exports\KelasTahunPelajaranExporter;
use App\Filament\Imports\KelasTahunPelajaranImporter;
use App\Filament\Pages\ProsesKenaikanKelas;
use App\Filament\Resources\KelasTahunPelajaranResource\Pages;
use App\Filament\Resources\KelasTahunPelajaranResource\RelationManagers\SiswaAktifRelationManager;
use App\Models\KelasTahunPelajaran;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class KelasTahunPelajaranResource extends Resource
{
    protected static ?string $model = KelasTahunPelajaran::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Kelas per Tahun Pelajaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kelas_id')
                ->label('Kelas')
                ->relationship('kelas', 'nama')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('tahun_pelajaran_id')
                ->label('Tahun Pelajaran')
                ->relationship('tahunPelajaran', 'nama')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('wali_kelas_id')
                ->label('Wali Kelas')
                // FIX: 'super_admin' (nilai RoleUser::Admin) DIHAPUS dari
                // daftar ini - super_admin tidak boleh menjadi wali kelas
                // (dikonfirmasi Aturan). Sebelumnya bug: role ini ikut
                // tersaring masuk sebagai kandidat wali kelas.
                ->relationship('waliKelas', 'nama', fn ($query) => $query->whereIn('role', ['pustakawan', 'pegawai']))
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(KelasTahunPelajaranImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', KelasTahunPelajaran::class) ?? false),
                ExportAction::make()
                    ->exporter(KelasTahunPelajaranExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', KelasTahunPelajaran::class) ?? false),
            ])
            ->columns([
                TextColumn::make('kelas.nama')->label('Kelas')->searchable()->sortable(),
                TextColumn::make('tahunPelajaran.nama')->label('Tahun Pelajaran')->searchable()->sortable(),
                TextColumn::make('waliKelas.nama')->label('Wali Kelas')->toggleable(),
                TextColumn::make('siswa_aktif_count')->label('Jumlah Siswa')->counts('siswaAktif'),
            ])
            ->filters([
                SelectFilter::make('tahun_pelajaran_id')->label('Tahun Pelajaran')->relationship('tahunPelajaran', 'nama'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('proses_kenaikan')
                    ->label('Proses Kenaikan Kelas')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('warning')
                    ->url(fn (KelasTahunPelajaran $record) => ProsesKenaikanKelas::getUrl(['ktp' => $record->id])),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->action(function (KelasTahunPelajaran $record) {
                        if ($record->siswaAktif()->exists()) {
                            Notification::make()
                                ->danger()->title('Tidak bisa dihapus permanen')
                                ->body('Masih ada siswa aktif di Kelas Tahun Pelajaran ini.')
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
            SiswaAktifRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKelasTahunPelajarans::route('/'),
            'create' => Pages\CreateKelasTahunPelajaran::route('/create'),
            'edit' => Pages\EditKelasTahunPelajaran::route('/{record}/edit'),
        ];
    }
}
