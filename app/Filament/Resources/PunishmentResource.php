<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PunishmentExporter;
use App\Filament\Imports\PunishmentImporter;
use App\Filament\Resources\PunishmentResource\Pages;
use App\Models\Punishment;
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

class PunishmentResource extends Resource
{
    protected static ?string $model = Punishment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationLabel = 'Punishment';

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
            TextInput::make('threshold_point_minus')
                ->numeric()
                ->integer()
                ->maxValue(0)
                ->required()
                ->helperText('Nilai negatif - akumulasi point <= nilai ini akan memicu punishment.'),
            TextInput::make('durasi_suspend_hari')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->helperText('Kosongkan jika punishment tidak memicu suspend otomatis.'),
            Toggle::make('aktif')
                ->default(true)
                ->helperText('Punishment nonaktif tidak akan dicek/direalisasikan lagi oleh PointService.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(PunishmentImporter::class)
                    ->authorize(fn () => auth()->user()?->can('create', Punishment::class) ?? false),
                ExportAction::make()
                    ->exporter(PunishmentExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', Punishment::class) ?? false),
            ])
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('threshold_point_minus')->sortable(),
                TextColumn::make('durasi_suspend_hari')->placeholder('-'),
                IconColumn::make('aktif')->boolean(),
                TextColumn::make('punishment_logs_count')->label('Jumlah Diterapkan')->counts('punishmentLogs'),
            ])
            ->filters([TernaryFilter::make('aktif')])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPunishments::route('/'),
            'create' => Pages\CreatePunishment::route('/create'),
            'edit' => Pages\EditPunishment::route('/{record}/edit'),
        ];
    }
}
