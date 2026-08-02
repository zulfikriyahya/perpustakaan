<?php

namespace App\Filament\Resources;

use App\Filament\Exports\TahunPelajaranExporter;
use App\Filament\Imports\TahunPelajaranImporter;
use App\Filament\Resources\TahunPelajaranResource\Pages;
use App\Models\KelasTahunPelajaran;
use App\Models\TahunPelajaran;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TahunPelajaranResource extends Resource
{
    protected static ?string $model = TahunPelajaran::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Tahun Pelajaran';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama (mis. 2026/2027)')
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                ->maxLength(255),
            DatePicker::make('tanggal_mulai')->required(),
            DatePicker::make('tanggal_selesai')->required()->afterOrEqual('tanggal_mulai'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()->importer(TahunPelajaranImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', TahunPelajaran::class) ?? false),
                ExportAction::make()->exporter(TahunPelajaranExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', TahunPelajaran::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('tanggal_mulai')->date('d F Y'),
                TextColumn::make('tanggal_selesai')->date('d F Y'),
                IconColumn::make('aktif')->boolean()->label('Aktif'),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([
                Action::make('jadikan_aktif')
                    ->label('Jadikan Aktif')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (TahunPelajaran $record) => ! $record->aktif)
                    ->requiresConfirmation()
                    ->modalDescription('Tahun Pelajaran lain yang sedang aktif akan otomatis dinonaktifkan.')
                    ->action(function (TahunPelajaran $record) {
                        TahunPelajaran::query()->where('id', '!=', $record->id)->update(['aktif' => false]);
                        $record->update(['aktif' => true]);
                        Notification::make()->success()->title('Tahun Pelajaran diaktifkan')->send();
                    }),
                DeleteAction::make()->visible(fn (TahunPelajaran $record) => ! $record->aktif),
                RestoreAction::make(),
                // TODO: GAP-SPEC - blokir force-delete jika sedang aktif ATAU
                // ada KTP (termasuk trashed) di bawahnya yang masih punya
                // siswa aktif.
                ForceDeleteAction::make()
                    ->action(function (TahunPelajaran $record) {
                        if ($record->aktif) {
                            Notification::make()->danger()->title('Tidak bisa dihapus permanen')
                                ->body('Tahun Pelajaran ini sedang aktif.')->send();

                            return;
                        }

                        $adaSiswaAktif = KelasTahunPelajaran::query()
                            ->withTrashed()
                            ->where('tahun_pelajaran_id', $record->id)
                            ->whereHas('siswaAktif')
                            ->exists();

                        if ($adaSiswaAktif) {
                            Notification::make()->danger()->title('Tidak bisa dihapus permanen')
                                ->body('Masih ada siswa aktif di Kelas Tahun Pelajaran bawah Tahun Pelajaran ini.')->send();

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
            'index' => Pages\ListTahunPelajarans::route('/'),
            'create' => Pages\CreateTahunPelajaran::route('/create'),
            'edit' => Pages\EditTahunPelajaran::route('/{record}/edit'),
        ];
    }
}
