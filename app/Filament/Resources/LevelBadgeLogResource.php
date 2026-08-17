<?php

namespace App\Filament\Resources;

use App\Filament\Exports\LevelBadgeLogExporter;
use App\Filament\Resources\LevelBadgeLogResource\Pages;
use App\Models\LevelBadgeLog;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

/**
 * Read-only - LevelBadgeLog HANYA dihasilkan otomatis oleh
 * PointService::cekBadge() saat badge user berubah (Aturan poin 3, DRY).
 * Tidak ada Import - insert manual akan melewati validasi rentang
 * min_point/max_point di PointService DAN tidak akan pernah menghasilkan
 * sertifikat. Pola identik dengan RewardLogResource/PunishmentLogResource.
 */
class LevelBadgeLogResource extends Resource
{
    protected static ?string $model = LevelBadgeLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Riwayat Badge';

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
                    ->exporter(LevelBadgeLogExporter::class)
                    ->authorize(fn() => auth()->user()?->can('viewAny', LevelBadgeLog::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')->label('User')->searchable()->sortable(),
                TextColumn::make('levelBadge.nama_badge')->label('Badge')->searchable()->sortable(),
                TextColumn::make('tanggal_didapat')->dateTime('d F Y H:i')->sortable(),
                TextColumn::make('nomor_sertifikat')
                    ->label('No. Sertifikat')
                    ->placeholder('Belum tersedia')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('level_badge_id')->label('Badge')->relationship('levelBadge', 'nama_badge'),
            ])
            ->defaultSort('tanggal_didapat', 'desc')
            ->recordActions([
                Action::make('downloadSertifikat')
                    ->label('Download Sertifikat')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn(LevelBadgeLog $record) => filled($record->sertifikat_path))
                    ->url(fn(LevelBadgeLog $record) => Storage::disk('public')->url($record->sertifikat_path))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLevelBadgeLogs::route('/'),
        ];
    }
}
