<?php

namespace App\Filament\Resources\BukuResource\Pages;

use App\Enums\StatusEksemplar;
use App\Filament\Resources\BukuResource;
use App\Models\Eksemplar;
use App\Models\Rak;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

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
     * BukuResource::form()). Logika generate barcode SENGAJA disamakan
     * persis dengan BukuImporter::afterSave() - satu sumber kebenaran
     * format barcode (Aturan poin 3), bukan duplikasi logika terpisah.
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
            $barcode = strtoupper(($buku->isbn ?: Str::slug($buku->judul)).'-'.($i + 1));

            // Pengaman sama seperti BukuImporter - hindari gagal karena
            // unique constraint kalau barcode kebetulan sudah dipakai.
            if (Eksemplar::query()->where('barcode', $barcode)->exists()) {
                $barcode .= '-'.strtoupper(Str::random(4));
            }

            $buku->eksemplars()->create([
                'barcode' => $barcode,
                'rak_id' => $rak?->id,
                'status' => StatusEksemplar::Tersedia,
            ]);
        }
    }
}
