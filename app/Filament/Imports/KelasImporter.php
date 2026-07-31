<?php

namespace App\Filament\Imports;

use App\Models\Jurusan;
use App\Models\Kelas;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * TODO: ASUMSI - upsert berdasarkan kombinasi ('nama', 'jurusan_id'),
 * BUKAN 'nama' saja, karena migration tidak memberi unique constraint
 * pada Kelas.nama sendiri (dua Kelas beda jurusan bisa punya nama sama).
 * Jika sumber data ternyata menjamin nama Kelas unik global, upsert key
 * ini bisa disederhanakan - konfirmasi dulu sebelum dianggap final.
 *
 * TODO: ASUMSI - referensi Jurusan dari kolom 'jurusan_kode' (via kode
 * unik Jurusan), bukan nama Jurusan, untuk menghindari ambiguitas nama.
 */
class KelasImporter extends Importer
{
    protected static ?string $model = Kelas::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('tingkat')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'min:1']),
            ImportColumn::make('jurusan_kode')
                ->label('Kode Jurusan (opsional)')
                ->rules(['nullable', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Kelas
    {
        $jurusanId = null;

        if (! empty($this->data['jurusan_kode'])) {
            $jurusan = Jurusan::query()->where('kode', $this->data['jurusan_kode'])->first();

            if (! $jurusan) {
                throw new RowImportFailedException("Jurusan dengan kode \"{$this->data['jurusan_kode']}\" tidak ditemukan.");
            }

            $jurusanId = $jurusan->id;
        }

        return Kelas::query()->firstOrNew([
            'nama' => $this->data['nama'],
            'jurusan_id' => $jurusanId,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Kelas selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}
