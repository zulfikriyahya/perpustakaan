<?php

namespace App\Filament\Widgets;

use App\Enums\StatusPeminjaman;
use App\Models\Peminjaman;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Peminjaman yang mendekati/melewati jatuh tempo - untuk Admin & Pustakawan.
 * TODO: verifikasi signature terhadap versi filament/filament ^5.7 -
 * TableWidget/table() API diasumsikan sama seperti pola Resource table().
 *
 * BUG FIX (iterasi sebelumnya): kolom 'buku.judul' DIHAPUS - Peminjaman
 * tidak lagi punya relasi langsung ke Buku sejak migration
 * 2026_08_02_000002-000004 (relasi kini lewat Eksemplar). Diganti jadi
 * 'eksemplar.buku.judul', dan query diberi eager load 'eksemplar.buku'
 * supaya tidak N+1 di tabel widget ini.
 *
 * UI (iterasi ini): kolom 'Sisa Hari' diubah dari angka mentah menjadi
 * teks manusiawi ("3 hari lagi", "Terlambat 2 hari", "Jatuh tempo hari
 * ini") - warna badge tetap dipertahankan dari logika sebelumnya
 * (negatif/<=1/lainnya), hanya label teksnya yang diubah. Perhitungan
 * pakai startOfDay() pada kedua tanggal agar "hari ini" akurat tanpa
 * terpengaruh jam saat request dibuat.
 */
class PeminjamanJatuhTempoWidget extends TableWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getTableHeading(): string
    {
        return 'Peminjaman Perlu Perhatian (Jatuh Tempo Terdekat)';
    }

    /**
     * Selisih hari (positif = belum jatuh tempo, 0 = hari ini, negatif =
     * sudah terlambat sekian hari) - dipisah dari format teksnya supaya
     * warna badge tetap konsisten dengan angka asli, bukan hasil parsing
     * teks.
     */
    protected function hitungSelisihHari(Peminjaman $record): int
    {
        return (int) Carbon::now()->startOfDay()
            ->diffInDays($record->tanggal_jatuh_tempo->copy()->startOfDay(), false);
    }

    protected function formatSisaHari(int $selisih): string
    {
        return match (true) {
            $selisih === 0 => 'Jatuh tempo hari ini',
            $selisih === 1 => 'Besok jatuh tempo',
            $selisih > 1 => "{$selisih} hari lagi",
            $selisih === -1 => 'Terlambat 1 hari',
            default => 'Terlambat '.abs($selisih).' hari',
        };
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
                TextColumn::make('tanggal_jatuh_tempo')->label('Jatuh Tempo')->date('d F Y'),
                TextColumn::make('sisa_hari')
                    ->label('Sisa Hari')
                    ->state(fn (Peminjaman $record) => $this->formatSisaHari($this->hitungSelisihHari($record)))
                    ->badge()
                    ->color(function (Peminjaman $record) {
                        $selisih = $this->hitungSelisihHari($record);

                        return $selisih < 0 ? 'danger' : ($selisih <= 1 ? 'warning' : 'success');
                    }),
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
