<?php

namespace App\Filament\Resources;

use App\Filament\Exports\RewardExporter;
use App\Filament\Imports\RewardImporter;
use App\Filament\Resources\RewardResource\Pages;
use App\Models\Reward;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
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
            TextInput::make('nama')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Textarea::make('deskripsi')
                ->columnSpanFull(),
            TextInput::make('threshold_point')
                ->numeric()
                ->integer()
                ->required(),
            Toggle::make('aktif')
                ->default(true)
                ->helperText('Reward nonaktif tidak akan dicek/direalisasikan lagi oleh PointService.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(RewardImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Reward::class) ?? false),
                ExportAction::make()
                    ->exporter(RewardExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Reward::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('threshold_point')->sortable(),
                IconColumn::make('aktif')->boolean(),
                TextColumn::make('reward_logs_count')->label('Jumlah Diperoleh')->counts('rewardLogs'),
            ])
            ->filters([TernaryFilter::make('aktif')])
            ->recordActions([DeleteAction::make()])
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
