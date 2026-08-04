<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

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
                ->hidden(fn ($record) => $record && $record->hasRole('super_admin')),
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
}
