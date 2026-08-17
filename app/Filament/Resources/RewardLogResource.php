<?php

namespace App\Filament\Resources;

use App\Filament\Exports\RewardLogExporter;
use App\Filament\Resources\RewardLogResource\Pages;
use App\Models\RewardLog;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

/**
 * // TODO: ASUMSI - dibuat sebagai Resource terpisah (bukan RelationManager
 * di UserResource), mengikuti pola RiwayatKelasSiswaResource. Jika Anda
 * lebih suka tab "Riwayat Reward" langsung di form/edit User, beri tahu -
 * mudah dipindah karena logic query-nya identik.
 *
 * Read-only - RewardLog HANYA dihasilkan otomatis oleh PointService saat
 * threshold tercapai (Aturan poin 3, DRY). Tidak ada Import - insert
 * manual lewat spreadsheet akan melewati validasi threshold PointService
 * DAN tidak akan pernah menghasilkan sertifikat (hanya dibuat via
 * SertifikatService yang dipanggil PointService).
 */
class RewardLogResource extends Resource
{
    protected static ?string $model = RewardLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Riwayat Reward';

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
                    ->exporter(RewardLogExporter::class)
                    ->authorize(fn() => auth()->user()?->can('viewAny', RewardLog::class) ?? false),
            ])
            ->columns([
                TextColumn::make('user.nama')->label('User')->searchable()->sortable(),
                TextColumn::make('reward.nama')->label('Reward')->searchable()->sortable(),
                TextColumn::make('tanggal_didapat')->dateTime('d F Y H:i')->sortable(),
                TextColumn::make('nomor_sertifikat')
                    ->label('No. Sertifikat')
                    ->placeholder('Belum tersedia')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('reward_id')->label('Reward')->relationship('reward', 'nama'),
            ])
            ->defaultSort('tanggal_didapat', 'desc')
            ->recordActions([
                Action::make('downloadSertifikat')
                    ->label('Download Sertifikat')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn(RewardLog $record) => filled($record->sertifikat_path))
                    ->url(fn(RewardLog $record) => Storage::disk('public')->url($record->sertifikat_path))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRewardLogs::route('/'),
        ];
    }
}
