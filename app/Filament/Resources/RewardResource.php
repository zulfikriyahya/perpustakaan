<?php

namespace App\Filament\Resources;

use App\Filament\Exports\RewardExporter;
use App\Filament\Imports\RewardImporter;
use App\Filament\Resources\RewardResource\Pages;
use App\Models\Reward;
use App\Models\RewardLog;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ImportAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RewardResource extends Resource
{
    protected static ?string $model = Reward::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Reward';

    protected static string|\UnitEnum|null $navigationGroup = 'Poin & Reward';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Reward')
                ->columns(2)
                ->schema([
                    TextInput::make('nama')
                        ->required()
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->validationMessages([
                            'required' => 'Nama reward wajib diisi.',
                            'unique' => 'Nama reward ini sudah dipakai dan masih aktif.',
                        ]),
                    Textarea::make('deskripsi')
                        ->columnSpanFull(),
                    TextInput::make('threshold_point')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->validationMessages([
                            'required' => 'Threshold point wajib diisi.',
                            'integer' => 'Threshold point harus berupa bilangan bulat.',
                            'min' => 'Threshold point minimal 1.',
                        ]),
                    Toggle::make('aktif')
                        ->default(true)
                        ->helperText('Reward nonaktif tidak akan dicek/direalisasikan lagi oleh PointService.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()->importer(RewardImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Reward::class) ?? false),
                ExportAction::make()->exporter(RewardExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Reward::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('threshold_point')->sortable(),
                IconColumn::make('aktif')->boolean(),
                TextColumn::make('reward_logs_count')->label('Jumlah Diperoleh')->counts('rewardLogs'),
            ])
            ->filters([TernaryFilter::make('aktif'), TrashedFilter::make()])
            ->recordActions([
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->action(function (Reward $record) {
                        $dipakai = RewardLog::query()->withTrashed()
                            ->where('reward_id', $record->id)->exists();

                        if ($dipakai) {
                            Notification::make()
                                ->danger()->title('Tidak bisa dihapus permanen')
                                ->body('Reward ini masih punya riwayat diperoleh.')
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
            'index' => Pages\ListRewards::route('/'),
            'create' => Pages\CreateReward::route('/create'),
            'edit' => Pages\EditReward::route('/{record}/edit'),
        ];
    }
}
