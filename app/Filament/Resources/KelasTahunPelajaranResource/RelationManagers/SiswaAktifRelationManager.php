<?php

namespace App\Filament\Resources\KelasTahunPelajaranResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only - assignment siswa ke KTP dilakukan lewat UserResource
 * (edit user, pilih kelas_tahun_pelajaran_id) atau Action Kenaikan Kelas
 * massal (menyusul), BUKAN attach/detach di sini, karena relasi ini
 * belongsTo di sisi User (kelas_tahun_pelajaran_id), bukan pivot.
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
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
