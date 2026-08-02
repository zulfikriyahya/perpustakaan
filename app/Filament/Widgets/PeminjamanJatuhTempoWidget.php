<?php

namespace App\Filament\Widgets;

use App\Enums\StatusPeminjaman;
use App\Models\Peminjaman;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Peminjaman yang mendekati/melewati jatuh tempo - untuk Admin & Pustakawan.
 * TODO: verifikasi signature terhadap versi filament/filament ^5.7 -
 * TableWidget/table() API diasumsikan sama seperti pola Resource table().
 *
 * BUG FIX (iterasi ini, pola sama dengan PengembalianResource/RakResource/
 * TransaksiCepat/DendaResource): kolom 'buku.judul' DIHAPUS - Peminjaman
 * tidak lagi punya relasi langsung ke Buku sejak migration
 * 2026_08_02_000002-000004 (relasi kini lewat Eksemplar). Diganti jadi
 * 'eksemplar.buku.judul', dan query diberi eager load 'eksemplar.buku'
 * supaya tidak N+1 di tabel widget ini.
 */
class PeminjamanJatuhTempoWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getTableHeading(): string
    {
        return 'Peminjaman Perlu Perhatian (Jatuh Tempo Terdekat)';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Peminjaman::query()
                    ->with(['user', 'eksemplar.buku'])
                    ->whereIn('status', [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat])
                    ->orderBy('tanggal_jatuh_tempo')
            )
            ->columns([
                TextColumn::make('user.nama')->label('Peminjam'),
                TextColumn::make('eksemplar.buku.judul')->label('Buku'),
                TextColumn::make('tanggal_jatuh_tempo')->label('Jatuh Tempo')->date('d M Y'),
                TextColumn::make('sisa_hari')
                    ->label('Sisa Hari')
                    ->state(fn (Peminjaman $record) => $record->tanggal_jatuh_tempo->diffInDays(now(), true) * ($record->tanggal_jatuh_tempo->isPast() ? -1 : 1))
                    ->badge()
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state <= 1 ? 'warning' : 'success')),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (StatusPeminjaman $state) => match ($state) {
                        StatusPeminjaman::Terlambat => 'danger',
                        StatusPeminjaman::Aktif => 'success',
                        default => 'gray',
                    }),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
