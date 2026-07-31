<?php

namespace App\Filament\Resources;

use App\Enums\StatusRiwayatKelas;
use App\Filament\Resources\RiwayatKelasSiswaResource\Pages;
use App\Models\RiwayatKelasSiswa;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Read-only - histori kenaikan/perpindahan kelas siswa. Tidak ada
 * form/create/edit karena data ini hanya dihasilkan otomatis oleh
 * KenaikanKelasService (Aturan poin 3, DRY - satu sumber kebenaran).
 *
 * // TODO: GAP-SPEC - canAccess dibatasi via Policy standar
 * (ViewAny:RiwayatKelasSiswa). Saat ini di ShieldSeeder permission ini
 * HANYA otomatis dimiliki super_admin (lewat syncPermissions(all())) -
 * BELUM ditambahkan ke daftar permission Pustakawan. Konfirmasi apakah
 * Pustakawan/wali kelas juga perlu akses lihat riwayat ini.
 */
class RiwayatKelasSiswaResource extends Resource
{
    protected static ?string $model = RiwayatKelasSiswa::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Riwayat Kelas Siswa';

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nama')->label('Siswa')->searchable()->sortable(),
                TextColumn::make('user.nisn')->label('NISN')->searchable(),
                TextColumn::make('kelasTahunPelajaran.kelas.nama')->label('Kelas')->sortable(),
                TextColumn::make('kelasTahunPelajaran.tahunPelajaran.nama')->label('Tahun Pelajaran')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (StatusRiwayatKelas $state) => match ($state) {
                        StatusRiwayatKelas::Aktif => 'success',
                        StatusRiwayatKelas::Naik => 'info',
                        StatusRiwayatKelas::Tinggal => 'warning',
                        StatusRiwayatKelas::Lulus => 'primary',
                        StatusRiwayatKelas::Keluar => 'gray',
                    }),
                TextColumn::make('tanggal_mulai')->date()->sortable(),
                TextColumn::make('tanggal_selesai')->date()->sortable()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(StatusRiwayatKelas::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRiwayatKelasSiswas::route('/'),
        ];
    }
}
