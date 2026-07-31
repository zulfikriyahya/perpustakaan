<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\KelasTahunPelajaran;
use App\Services\KenaikanKelasService;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    /**
     * Field 'assign_kelas_tahun_pelajaran_id' HANYA ada di form create
     * (lihat UserResource::form(), visibleOn('create')) - bukan kolom
     * User sungguhan, jadi wajib dibuang sebelum mass-assign, lalu
     * assignment dilakukan di afterCreate() lewat KenaikanKelasService
     * supaya RiwayatKelasSiswa tetap tercatat (Aturan poin 3, DRY).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->assignKtpId = $data['assign_kelas_tahun_pelajaran_id'] ?? null;

        unset($data['assign_kelas_tahun_pelajaran_id']);

        return $data;
    }

    protected ?string $assignKtpId = null;

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
