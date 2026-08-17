<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn($record) => $record && $record->hasRole('super_admin')),
        ];
    }

    /**
     * BARU (iterasi ini) - proteksi server-side untuk field sensitif
     * (nip, nisn, role, jenis_kelamin, no_telepon, no_kartu_rfid) saat
     * record yang diedit SUDAH berstatus super_admin di DB (dicek dari
     * $this->record, BUKAN dari $data yang dikirim - supaya payload yang
     * dimanipulasi manual tidak bisa mengubah nilai ini walau field-nya
     * disembunyikan di UserResource::form()).
     *
     * Berlaku juga saat super_admin mengedit akunnya sendiri (dikonfirmasi
     * eksplisit - lihat sesi ini) - field tetap dipaksa ke nilai lama.
     *
     * TODO: GAP-SPEC - proteksi ini dipilih di level "field individual"
     * (whitelist kolom), bukan blokir seluruh update(). Jika ada field baru
     * yang dianggap sensitif di masa depan, WAJIB ditambahkan ke daftar
     * $fieldTerlindungi ini juga (Aturan poin 11 - telusuri semua
     * pemakaian).
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record && $this->record->hasRole('super_admin')) {
            $fieldTerlindungi = [
                'nip',
                'nisn',
                'role',
                'jenis_kelamin',
                'no_telepon',
                'no_kartu_rfid',
            ];

            foreach ($fieldTerlindungi as $field) {
                $data[$field] = $this->record->getRawOriginal($field);
            }
        }

        return $data;
    }

    /**
     * BARU (iterasi ini) - jaring terakhir untuk unique constraint DB,
     * pasangan dari CreateUser::handleRecordCreation() - lihat docblock
     * di sana untuk alasan lengkap (normalisasi no_telepon bisa membuat
     * dua nilai berbeda jadi sama, unique() form tidak selalu menangkap
     * ini terhadap data lama yang belum ternormalisasi).
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (QueryException $e) {
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }

            Notification::make()
                ->danger()
                ->title('Gagal menyimpan perubahan User')
                ->body('Salah satu data (No. Telepon/NISN/NIP/No. Kartu RFID) sudah dipakai user lain yang masih aktif. Periksa kembali isian, khususnya No. Telepon - kemungkinan sudah terdaftar dalam format penulisan yang berbeda.')
                ->persistent()
                ->send();

            throw new Halt;
        }
    }
}
