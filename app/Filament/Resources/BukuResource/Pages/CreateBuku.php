<?php

namespace App\Filament\Resources\BukuResource\Pages;

use App\Enums\StatusEksemplar;
use App\Filament\Resources\BukuResource;
use App\Models\Eksemplar;
use App\Models\Rak;
use Filament\Resources\Pages\CreateRecord;

class CreateBuku extends CreateRecord
{
    protected static string $resource = BukuResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    /**
     * GAP-SPEC ditutup: buku bisa langsung dibuat sekaligus dengan N
     * Eksemplar awal (field 'jumlah_eksemplar_awal' non-persisten, lihat
     * BukuResource::form()). Format barcode kini SATU SUMBER KEBENARAN
     * lewat Eksemplar::generateBarcodeUntuk() - sebelumnya kode generate
     * barcode disalin persis dari BukuImporter::afterSave() (Aturan poin 3).
     */
    protected function afterCreate(): void
    {
        $jumlah = (int) ($this->data['jumlah_eksemplar_awal'] ?? 0);

        if ($jumlah <= 0) {
            return;
        }

        $buku = $this->record;
        $rak = ! empty($this->data['rak_id_eksemplar_awal'])
            ? Rak::query()->find($this->data['rak_id_eksemplar_awal'])
            : null;

        for ($i = 0; $i < $jumlah; $i++) {
            $buku->eksemplars()->create([
                'barcode' => Eksemplar::generateBarcodeUntuk($buku, $i + 1),
                'rak_id' => $rak?->id,
                'status' => StatusEksemplar::Tersedia,
            ]);
        }
    }
}
