<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PunishmentLogExporter;
use App\Filament\Resources\PunishmentLogResource\Pages;
use App\Models\PunishmentLog;
use Filament\Actions\ExportAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * // TODO: ASUMSI - lihat catatan sama di RewardLogResource.
 * Read-only, tanpa Import - dihasilkan otomatis oleh PointService.
 */
class PunishmentLogResource extends Resource
{
    protected static ?string $model = PunishmentLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Riwayat Punishment';

    protected static string|\UnitEnum|null $navigationGroup = 'Poin & Reward';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(PunishmentLogExporter::class)
                    ->authorize(fn () => auth()->user()?->can('viewAny', PunishmentLog::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')->label('User')->searchable()->sortable(),
                TextColumn::make('punishment.nama')->label('Punishment')->searchable()->sortable(),
                TextColumn::make('tanggal_diterapkan')->dateTime()->sortable(),
                TextColumn::make('tanggal_berakhir')->dateTime()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('punishment_id')->label('Punishment')->relationship('punishment', 'nama'),
            ])
            ->defaultSort('tanggal_diterapkan', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPunishmentLogs::route('/'),
        ];
    }
}
