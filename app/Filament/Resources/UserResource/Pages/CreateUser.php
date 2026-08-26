<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\KelasTahunPelajaran;
use App\Services\KenaikanKelasService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->assignKtpId = $data['assign_kelas_tahun_pelajaran_id'] ?? null;

        unset($data['assign_kelas_tahun_pelajaran_id']);

        return $data;
    }

    protected ?string $assignKtpId = null;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (QueryException $e) {
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }

            Notification::make()
                ->danger()
                ->title('Gagal menyimpan User')
                ->body('Salah satu data (No. Telepon/NISN/NIP/No. Kartu RFID) sudah dipakai user lain yang masih aktif. Periksa kembali isian, khususnya No. Telepon - kemungkinan sudah terdaftar dalam format penulisan yang berbeda.')
                ->persistent()
                ->send();

            throw new Halt;
        }
    }

    protected function afterCreate(): void
    {
        if (! $this->assignKtpId) {
            return;
        }

        $ktp = KelasTahunPelajaran::query()->find($this->assignKtpId);

        if ($ktp) {
            app(KenaikanKelasService::class)->assignKelas($this->record, $ktp);
        }
    }
}
