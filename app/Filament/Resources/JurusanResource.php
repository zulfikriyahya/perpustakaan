<?php

namespace App\Filament\Resources;

use App\Filament\Exports\JurusanExporter;
use App\Filament\Imports\JurusanImporter;
use App\Filament\Resources\JurusanResource\Pages;
use App\Models\Jurusan;
use App\Models\Kelas;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class JurusanResource extends Resource
{
    protected static ?string $model = Jurusan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Jurusan';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Jurusan')
                ->description('Kelas mensyaratkan Jurusan (wajib diisi) - lihat migration kelas_wajib_jurusan_unique_per_jurusan.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('nama')
                        ->required()
                        ->maxLength(255)
                        ->validationMessages([
                            'required' => 'Nama jurusan wajib diisi.',
                            'max' => 'Nama jurusan maksimal 255 karakter.',
                        ]),
                    TextInput::make('kode')
                        ->required()
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                        ->maxLength(255)
                        ->validationMessages([
                            'required' => 'Kode jurusan wajib diisi.',
                            'unique' => 'Kode jurusan ini sudah dipakai jurusan lain yang masih aktif.',
                            'max' => 'Kode jurusan maksimal 255 karakter.',
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()->importer(JurusanImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Jurusan::class) ?? false),
                ExportAction::make()->exporter(JurusanExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Jurusan::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('kode')->searchable(),
                TextColumn::make('kelas_count')->label('Jumlah Kelas')->counts('kelas'),
                TextColumn::make('created_at')->dateTime('d F Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->action(function (Jurusan $record) {
                        $dipakai = Kelas::query()
                            ->withTrashed()
                            ->where('jurusan_id', $record->id)
                            ->exists();

                        if ($dipakai) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak bisa dihapus permanen')
                                ->body('Masih ada Kelas yang memakai Jurusan ini.')
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
            'index' => Pages\ListJurusans::route('/'),
            'create' => Pages\CreateJurusan::route('/create'),
            'edit' => Pages\EditJurusan::route('/{record}/edit'),
        ];
    }
}
