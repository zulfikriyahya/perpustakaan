<?php

namespace App\Filament\Imports;

use App\Models\Punishment;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

// Upsert berdasarkan 'nama' (unique di form).
class PunishmentImporter extends Importer
{
    protected static ?string $model = Punishment::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string']),
            ImportColumn::make('threshold_point_minus')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'max:0']),
            ImportColumn::make('durasi_suspend_hari')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:1']),
            ImportColumn::make('aktif')
                ->boolean()
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): ?Punishment
    {
        return Punishment::query()->firstOrNew(['nama' => $this->data['nama']]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Punishment selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}
