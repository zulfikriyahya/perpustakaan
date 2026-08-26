<?php

namespace App\Services;

use App\Models\Eksemplar;
use Illuminate\Support\Collection;
use Picqer\Barcode\BarcodeGeneratorPNG;

class LabelBarcodeService
{
    protected BarcodeGeneratorPNG $generator;

    public function __construct()
    {
        $this->generator = new BarcodeGeneratorPNG;
    }

    /**
     * @param  Collection<int, Eksemplar>  $eksemplars
     * @return array<int, array{barcode: string, judul: string, gambar: string}>
     */
    public function generateData(Collection $eksemplars): array
    {
        return $eksemplars->map(function (Eksemplar $eksemplar) {
            $png = $this->generator->getBarcode(
                $eksemplar->barcode,
                $this->generator::TYPE_CODE_128,
                2,
                50,
            );

            return [
                'barcode' => $eksemplar->barcode,
                'judul' => $eksemplar->buku->judul,
                'gambar' => 'data:image/png;base64,'.base64_encode($png),
            ];
        })->all();
    }
}
