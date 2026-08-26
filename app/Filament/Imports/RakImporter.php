<?php

namespace App\Filament\Imports;

use App\Models\Kategori;
use App\Models\Rak;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class RakImporter extends Importer
{
    protected static ?string $model = Rak::class;

    /**
     * @var array<int, string>|null ID Kategori hasil resolve nama di
     *                              beforeSave() - null berarti kolom 'kategori' kosong. Divalidasi
     *                              SEBELUM save() supaya baris dengan nama kategori tidak
     *                              ditemukan GAGAL TOTAL (dikonfirmasi, sama pola dengan
     *                              BukuImporter) - bukan diam-diam melepas kategori yang salah.
     */
    protected ?array $kategoriIdsTerresolve = null;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Rak A'),
            ImportColumn::make('lokasi')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Lantai 1, dekat pintu masuk'),
            ImportColumn::make('kategori')
                ->label('Kategori (nama, pisah titik-koma jika lebih dari satu)')
                ->helperText('Isi persis sesuai nama Kategori yang sudah ada di Master Data > Kategori. Jika salah satu nama tidak ditemukan, seluruhbaris ini akan GAGAL diimpor (tidak sebagian tersimpan).')
                ->rules(['nullable', 'string'])
                ->example('Fiksi;Sains')
                // BUG FIX - lookup-only, lihat docblock class.
                ->fillRecordUsing(fn (?string $state) => null),
        ];
    }

    public function resolveRecord(): ?Rak
    {
        $nama = trim($this->data['nama']);

        return Rak::query()->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first()
            ?? new Rak(['nama' => $nama]);
    }

    protected function beforeSave(): void
    {
        if (! empty($this->data['kategori'])) {
            $namaKategoris = array_values(array_filter(array_map('trim', explode(';', $this->data['kategori']))));
            $kategoris = Kategori::query()->whereIn('nama', $namaKategoris)->get(['id', 'nama']);

            $namaTidakDitemukan = array_diff($namaKategoris, $kategoris->pluck('nama')->all());

            if (! empty($namaTidakDitemukan)) {
                throw new RowImportFailedException('Kategori tidak ditemukan: "'.implode('", "', $namaTidakDitemukan).'". Cek ejaan atau tambahkan Kategori-nya dulu di Master Data > Kategori.');
            }

            $this->kategoriIdsTerresolve = $kategoris->pluck('id')->all();
        }
    }

    protected function afterSave(): void
    {
        if ($this->kategoriIdsTerresolve !== null) {
            $this->record->kategoris()->sync($this->kategoriIdsTerresolve);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Rak selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal- buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}
