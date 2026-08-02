<?php

namespace App\Filament\Widgets;

use App\Enums\StatusEksemplar;
use App\Enums\StatusRefund;
use App\Enums\TipeDenda;
use App\Models\Denda;
use App\Models\Eksemplar;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daftar Eksemplar Rusak/Hilang SAAT INI, DITAMBAH eksemplar yang baru
 * saja ditemukan kembali (status kembali Tersedia tapi masih ada Denda
 * kerusakan/kehilangan yang dibatalkan otomatis - lihat
 * PeminjamanService::batalkanDenda()/bukuDitemukanKembali()).
 *
 * TODO: GAP-SPEC - "baru saja ditemukan kembali" didefinisikan sebagai
 * Denda dengan keterangan mengandung "Dibatalkan otomatis" DAN
 * updated_at dalam 30 hari terakhir (belum ada spec eksplisit soal
 * jendela waktu ini) - supaya widget tidak membengkak menampilkan
 * seluruh histori eksemplar yang pernah rusak/hilang sejak awal.
 * Kalau perlu histori penuh, sebaiknya dibuat halaman/laporan terpisah,
 * bukan menambah beban widget dashboard ini.
 */
class BukuRusakHilangWidget extends TableWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 2;

    protected const HARI_JENDELA_DITEMUKAN = 30;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'pustakawan']) ?? false;
    }

    protected function getTableHeading(): string
    {
        return 'Eksemplar Rusak, Hilang & Ditemukan Kembali';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Eksemplar::query()
                    ->with([
                        'buku',
                        'rak',
                        'peminjamanTerakhir.user',
                        'peminjamanTerakhir.dendas' => fn($q) => $q->whereIn('tipe', [TipeDenda::Kerusakan, TipeDenda::Kehilangan])
                            ->latest('updated_at'),
                    ])
                    ->where(function (Builder $query) {
                        $query->whereIn('status', [StatusEksemplar::Rusak, StatusEksemplar::Hilang])
                            ->orWhereHas('peminjamanTerakhir.dendas', function (Builder $q) {
                                $q->whereIn('tipe', [TipeDenda::Kerusakan, TipeDenda::Kehilangan])
                                    ->where('keterangan', 'like', '%Dibatalkan otomatis%')
                                    ->where('updated_at', '>=', now()->subDays(self::HARI_JENDELA_DITEMUKAN));
                            });
                    })
                    ->latest('updated_at')
            )
            ->columns([
                TextColumn::make('buku.judul')->label('Judul Buku')->searchable(),
                TextColumn::make('barcode')->label('Barcode')->searchable(),
                TextColumn::make('rak.nama')->label('Rak')->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(StatusEksemplar $state) => match ($state) {
                        StatusEksemplar::Rusak => 'warning',
                        StatusEksemplar::Hilang => 'danger',
                        StatusEksemplar::Tersedia => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(Eksemplar $record, StatusEksemplar $state) => $state === StatusEksemplar::Tersedia
                        ? 'Ditemukan Kembali'
                        : ucfirst($state->value)),
                // BARU - siapa yang merusak/menghilangkan, diambil dari
                // Peminjaman terakhir eksemplar ini (pola sama seperti
                // EksemplarsRelationManager).
                TextColumn::make('peminjamanTerakhir.user.nama')
                    ->label('Dirusak/Dihilangkan Oleh')
                    ->placeholder('-')
                    ->searchable(),
                // BARU - status pembayaran Denda terkait (ambil Denda
                // kerusakan/kehilangan terbaru dari Peminjaman terakhir).
                TextColumn::make('status_denda')
                    ->label('Status Denda')
                    ->state(fn(Eksemplar $record) => static::dendaTerkait($record)?->status_lunas)
                    ->badge()
                    ->formatStateUsing(fn(?bool $state) => $state === null ? null : ($state ? 'Lunas' : 'Belum Lunas'))
                    ->color(fn(?bool $state) => match (true) {
                        $state === true => 'success',
                        $state === false => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('-'),
                // BARU - status refund, relevan khusus untuk kasus Denda
                // yang SUDAH terbayar lalu dibatalkan karena buku ditemukan
                // kembali/koreksi kondisi (lihat PeminjamanService::batalkanDenda()).
                TextColumn::make('status_refund_denda')
                    ->label('Status Refund')
                    ->state(fn(Eksemplar $record) => static::dendaTerkait($record)?->status_refund)
                    ->badge()
                    ->formatStateUsing(fn(?StatusRefund $state) => $state ? ucfirst(str_replace('_', ' ', $state->value)) : null)
                    ->color(fn(?StatusRefund $state) => match ($state) {
                        StatusRefund::PerluRefund => 'warning',
                        StatusRefund::SudahDirefund => 'success',
                        StatusRefund::TidakPerlu, null => 'gray',
                    })
                    ->placeholder('-'),
                // BARU - keterangan Denda memuat info "ditemukan kembali/
                // dibatalkan otomatis" (diisi PeminjamanService::batalkanDenda()),
                // ditampilkan apa adanya, bukan kolom boolean terpisah -
                // menghindari duplikasi sumber kebenaran (Aturan poin 3).
                TextColumn::make('keterangan_denda')
                    ->label('Keterangan')
                    ->state(fn(Eksemplar $record) => static::dendaTerkait($record)?->keterangan)
                    ->limit(60)
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('updated_at')->label('Diperbarui')->dateTime('d F Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        StatusEksemplar::Rusak->value => 'Rusak',
                        StatusEksemplar::Hilang->value => 'Hilang',
                        StatusEksemplar::Tersedia->value => 'Ditemukan Kembali',
                    ]),
                TernaryFilter::make('status_lunas')
                    ->label('Status Denda')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('peminjamanTerakhir.dendas', fn($q) => $q->whereIn('tipe', [TipeDenda::Kerusakan, TipeDenda::Kehilangan])->where('status_lunas', true)),
                        false: fn(Builder $query) => $query->whereHas('peminjamanTerakhir.dendas', fn($q) => $q->whereIn('tipe', [TipeDenda::Kerusakan, TipeDenda::Kehilangan])->where('status_lunas', false)),
                    ),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }

    /**
     * dihitung sekali per baris - Denda kerusakan/kehilangan TERBARU dari
     * Peminjaman terakhir eksemplar ini. Reuse pola yang sama dengan
     * EksemplarsRelationManager::dendaTerkait() (Aturan poin 3 - DRY),
     * tapi di sini tidak bergantung pada Eksemplar::status karena widget
     * ini juga menampilkan eksemplar yang statusnya sudah Tersedia lagi.
     */
    protected static function dendaTerkait(Eksemplar $record): ?Denda
    {
        return $record->peminjamanTerakhir?->dendas
            ->whereIn('tipe', [TipeDenda::Kerusakan, TipeDenda::Kehilangan])
            ->sortByDesc('updated_at')
            ->first();
    }
}
