<?php

namespace App\Filament\Imports;

use App\Enums\RoleUser;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;

/**
 * TODO: GAP-SPEC - 'role' dan 'no_kartu_rfid' SENGAJA tidak termasuk kolom
 * import (dikonfirmasi: harus manual lewat form demi keamanan). User baru
 * hasil import otomatis role='siswa' (default migration/kolom).
 *
 * Upsert berdasarkan 'nisn' jika ada, fallback 'nip' - baris tanpa
 * keduanya akan gagal (lihat rules 'required_without').
 *
 * Password digenerate random - TIDAK ada mekanisme kirim WA/email
 * notifikasi password ke user baru dalam iterasi ini (lihat status
 * verifikasi di respons utama untuk gap ini).
 */
class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('nisn')
                ->label('NISN')
                ->rules(['nullable', 'required_without:nip', 'string', 'max:255']),
            ImportColumn::make('nip')
                ->label('NIP')
                ->rules(['nullable', 'required_without:nisn', 'string', 'max:255']),
            ImportColumn::make('kelas')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('jabatan')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('no_telepon')
                ->label('No. Telepon')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?User
    {
        if (! empty($this->data['nisn'])) {
            $record = User::query()->firstOrNew(['nisn' => $this->data['nisn']]);
        } else {
            $record = User::query()->firstOrNew(['nip' => $this->data['nip']]);
        }

        if (! $record->exists) {
            $record->role = RoleUser::Siswa;
            $record->password = Str::random(12); // di-hash otomatis via cast 'hashed'
        }

        return $record;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import User selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}
