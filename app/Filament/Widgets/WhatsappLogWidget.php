<?php

namespace App\Filament\Widgets;

use App\Models\WhatsappLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Log Pengiriman WhatsApp - super_admin & pustakawan bisa lihat widget
 * ini (dikonfirmasi), tapi kolom yang memuat data pribadi (No. Tujuan)
 * atau detail teknis mentah (Keterangan - bisa memuat pesan error
 * gateway apa adanya) DIBATASI hanya untuk super_admin lewat ->visible()
 * per kolom - bukan menyembunyikan widget secara keseluruhan.
 */
class WhatsappLogWidget extends TableWidget
{
    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getTableHeading(): string
    {
        return 'Log Pengiriman WhatsApp Terbaru';
    }

    protected function isSuperAdmin(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(WhatsappLog::query()->latest('updated_at'))
            ->columns([
                TextColumn::make('template_code')->label('Template')->searchable(),
                TextColumn::make('nomor_tujuan')
                    ->label('No. Tujuan')
                    ->searchable()
                    ->visible(fn () => $this->isSuperAdmin()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'terkirim' => 'success',
                        'gagal_transient' => 'warning',
                        'gagal_permanen' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('percobaan_ke')->label('Percobaan'),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->toggleable()
                    ->placeholder('-')
                    ->visible(fn () => $this->isSuperAdmin()),
                TextColumn::make('updated_at')->label('Terakhir Diperbarui')->dateTime('d M Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'terkirim' => 'Terkirim',
                        'gagal_transient' => 'Gagal (Transient)',
                        'gagal_permanen' => 'Gagal (Permanen)',
                    ]),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
