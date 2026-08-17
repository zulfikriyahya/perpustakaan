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

    /**
     * BARU (iterasi ini) - jaring terakhir untuk unique constraint DB
     * pada 'no_telepon' (juga menutupi 'nisn'/'nip'/'no_kartu_rfid' yang
     * unique) yang lolos validasi form Filament tapi tetap gagal di level
     * database - paling sering terjadi untuk no_telepon karena normalisasi
     * (lihat NomorTeleponFormatter) bisa membuat dua input BERBEDA jadi
     * SAMA setelah dinormalisasi, sementara unique() Filament membandingkan
     * terhadap nilai mentah yang tersimpan (termasuk data lama yang belum
     * pernah dinormalisasi). Tanpa ini, QueryException akan menghasilkan
     * halaman error generik/blank bagi user - digantikan Notification yang
     * jelas + Halt agar form tidak jadi ter-reset/hilang isian.
     */
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
