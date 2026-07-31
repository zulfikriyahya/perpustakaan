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
                ->label('Nama (mis. 2025/2026)')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('2025/2026'),
            ImportColumn::make('tanggal_mulai')
                ->helperText('Gunakan format tanggal YYYY-MM-DD (mis. 2025-07-14) supaya tidak salah baca oleh Excel/Google Sheets di komputer dengan format regional berbeda.')
                ->requiredMapping()
                ->rules(['required', 'date'])
                ->example('2025-07-14'),
            ImportColumn::make('tanggal_selesai')
                ->helperText('Format sama dengan Tanggal Mulai (YYYY-MM-DD), harus sama atau setelah Tanggal Mulai.')
                ->requiredMapping()
                ->rules(['required', 'date', 'after_or_equal:tanggal_mulai'])
                ->example('2026-06-30'),
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
