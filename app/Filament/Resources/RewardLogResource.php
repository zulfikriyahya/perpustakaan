<?php

namespace App\Filament\Resources;

use App\Filament\Exports\RewardLogExporter;
use App\Filament\Resources\RewardLogResource\Pages;
use App\Models\RewardLog;
use App\Services\SertifikatService;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

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
                    ->authorize(fn () => auth()->user()?->can('viewAny', RewardLog::class) ?? false),
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
                    ->visible(fn (RewardLog $record) => filled($record->sertifikat_path))
                    ->url(fn (RewardLog $record) => Storage::disk('public')->url($record->sertifikat_path)
                        .'?v='.$record->updated_at?->timestamp)
                    ->openUrlInNewTab(),

                Action::make('regenerateSertifikat')
                    ->label('Regenerate Sertifikat')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (RewardLog $record) => filled($record->sertifikat_path))
                    ->authorize(fn (RewardLog $record) => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate Sertifikat')
                    ->modalDescription('PDF sertifikat akan dibuat ulang dengan desain terbaru. Link download tidak berubah, hanya versinya diperbarui.')
                    ->action(function (RewardLog $record) {
                        $path = app(SertifikatService::class)->generateUntukReward($record);

                        if ($path === null) {
                            Notification::make()
                                ->title('Gagal regenerate sertifikat')
                                ->body('Terjadi kesalahan saat membuat ulang PDF. Cek log aplikasi untuk detail.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Sertifikat berhasil diperbarui')
                            ->success()
                            ->send();
                    }),
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
