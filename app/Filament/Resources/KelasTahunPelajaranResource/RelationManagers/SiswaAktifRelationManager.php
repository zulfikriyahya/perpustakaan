<?php

namespace App\Filament\Resources\KelasTahunPelajaranResource\RelationManagers;

use App\Enums\RoleUser;
use App\Models\KelasTahunPelajaran;
use App\Models\User;
use App\Services\KenaikanKelasService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Assignment/pelepasan siswa SELALU lewat KenaikanKelasService (Aturan
 * poin 3, DRY) - tidak ada attach()/detach() pivot langsung di sini,
 * karena relasi ini belongsTo di sisi User (kelas_tahun_pelajaran_id),
 * bukan pivot, dan setiap perubahan wajib tercatat di RiwayatKelasSiswa.
 *
 * // TODO: GAP-SPEC - "Tambah Siswa" di sini memakai KenaikanKelasService::
 * assignKelas() yang SAMA dengan bulk action UserResource - artinya jika
 * siswa yang dipilih sudah aktif di KTP lain, riwayat lamanya otomatis
 * ditutup status 'keluar' (bukan error/penolakan). Perlu dikonfirmasi
 * apakah perilaku pindah-kelas-implisit ini yang diinginkan di titik
 * masuk ini juga, atau harus menolak siswa yang sudah punya KTP aktif.
 */
class SiswaAktifRelationManager extends RelationManager
{
    protected static string $relationship = 'siswaAktif';

    protected static ?string $title = 'Siswa Aktif';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('nisn')->label('NISN')->searchable(),
                TextColumn::make('status_akademik')->badge(),
            ])
            ->headerActions([
                Action::make('tambah_siswa')
                    ->label('Tambah Siswa')
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Select::make('user_ids')
                            ->label('Pilih Siswa')
                            ->multiple()
                            ->searchable()
                            ->options(
                                User::query()
                                    ->where('role', RoleUser::Siswa)
                                    ->pluck('nama', 'id')
                            )
                            ->required()
                            ->helperText('Siswa yang sudah aktif di kelas lain akan otomatis dipindahkan ke kelas ini.'),
                    ])
                    ->action(function (array $data) {
                        /** @var KelasTahunPelajaran $ktp */
                        $ktp = $this->getOwnerRecord();
                        $service = app(KenaikanKelasService::class);

                        User::query()->whereIn('id', $data['user_ids'])
                            ->get()
                            ->each(fn (User $user) => $service->assignKelas($user, $ktp));

                        Notification::make()
                            ->success()
                            ->title(count($data['user_ids']).' siswa berhasil ditambahkan ke kelas.')
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('keluarkan')
                    ->label('Keluarkan dari Kelas')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Siswa akan dilepas dari kelas ini dan status akademik berubah menjadi Keluar. Aksi ini tercatat di riwayat.')
                    ->action(function (User $record) {
                        app(KenaikanKelasService::class)->keluarkanDariKelas($record);

                        Notification::make()
                            ->success()
                            ->title("{$record->nama} dikeluarkan dari kelas.")
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
    }
}
