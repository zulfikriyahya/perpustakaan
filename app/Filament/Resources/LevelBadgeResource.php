<?php

namespace App\Filament\Resources;

use App\Filament\Exports\LevelBadgeExporter;
use App\Filament\Imports\LevelBadgeImporter;
use App\Filament\Resources\LevelBadgeResource\Pages;
use App\Models\LevelBadge;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LevelBadgeResource extends Resource
{
    protected static ?string $model = LevelBadge::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Level Badge';

    protected static string|\UnitEnum|null $navigationGroup = 'Poin & Reward';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Badge')
                ->columns(2)
                ->schema([
                    TextInput::make('nama_badge')
                        ->required()
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->validationMessages([
                            'required' => 'Nama badge wajib diisi.',
                            'unique' => 'Nama badge ini sudah dipakai dan masih aktif.',
                        ]),
                    FileUpload::make('icon')
                        ->image()
                        ->directory('level-badge-icon')
                        ->columnSpanFull(),
                ]),

            Section::make('Threshold Point')
                ->description('Rentang akumulasi point yang memicu badge ini (dicek PointService).')
                ->columns(3)
                ->schema([
                    TextInput::make('min_point')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->live(onBlur: true)
                        ->validationMessages([
                            'required' => 'Point minimum wajib diisi.',
                            'integer' => 'Point minimum harus berupa bilangan bulat.',
                        ]),
                    TextInput::make('max_point')
                        ->numeric()
                        ->integer()
                        ->helperText('Kosongkan jika badge tertinggi (tidak ada batas atas).')
                        ->gte('min_point')
                        ->validationMessages([
                            'integer' => 'Point maksimum harus berupa bilangan bulat.',
                            'gte' => 'Point maksimum tidak boleh lebih kecil dari point minimum.',
                        ]),
                    TextInput::make('urutan')
                        ->numeric()
                        ->integer()
                        ->default(0)
                        ->required()
                        ->helperText('Urutan tampilan, bukan urutan threshold.')
                        ->validationMessages([
                            'integer' => 'Urutan harus berupa bilangan bulat.',
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()->importer(LevelBadgeImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', LevelBadge::class) ?? false),
                ExportAction::make()->exporter(LevelBadgeExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', LevelBadge::class) ?? false),
            ])
            ->columns([
                ImageColumn::make('icon')->circular(),
                TextColumn::make('nama_badge')->searchable()->sortable(),
                TextColumn::make('min_point')->sortable(),
                TextColumn::make('max_point')->sortable()->placeholder('Tanpa batas atas'),
                TextColumn::make('urutan')->sortable(),
            ])
            ->defaultSort('urutan')
            ->filters([TrashedFilter::make()])
            ->recordActions([
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->action(function (LevelBadge $record) {
                        $dipakai = User::query()
                            ->withTrashed()
                            ->where('level_badge_id', $record->id)
                            ->exists();

                        if ($dipakai) {
                            Notification::make()
                                ->danger()->title('Tidak bisa dihapus permanen')
                                ->body('Masih ada User yang memakai Level Badge ini.')
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
            'index' => Pages\ListLevelBadges::route('/'),
            'create' => Pages\CreateLevelBadge::route('/create'),
            'edit' => Pages\EditLevelBadge::route('/{record}/edit'),
        ];
    }
}
