<?php

namespace App\Filament\Imports;

use App\Models\TahunPelajaran;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

// Upsert berdasarkan 'nama' (unique).
class TahunPelajaranImporter extends Importer
{
    protected static ?string $model = TahunPelajaran::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('tanggal_mulai')
                ->requiredMapping()
                ->rules(['required', 'date']),
            ImportColumn::make('tanggal_selesai')
                ->requiredMapping()
                ->rules(['required', 'date', 'after_or_equal:tanggal_mulai']),
        ];
    }

    public function resolveRecord(): ?TahunPelajaran
    {
        // 'aktif' SENGAJA tidak diimpor - perubahan Tahun Pelajaran aktif
        // hanya lewat action "Jadikan Aktif" di TahunPelajaranResource,
        // supaya logic "nonaktifkan yang lain" tetap terpusat di satu
        // tempat (Aturan poin 3).
        return TahunPelajaran::query()->firstOrNew(['nama' => $this->data['nama']]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Tahun Pelajaran selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}
