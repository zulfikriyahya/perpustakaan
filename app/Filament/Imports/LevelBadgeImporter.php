<?php

namespace App\Filament\Imports;

use App\Models\LevelBadge;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * TODO: GAP-SPEC - upsert berdasarkan 'nama_badge'. Migration tidak
 * memberi unique constraint pada kolom ini (hanya di form Filament) -
 * jika sumber data ternyata mengizinkan nama badge duplikat secara sah,
 * upsert key ini perlu direvisi.
 */
class LevelBadgeImporter extends Importer
{
    protected static ?string $model = LevelBadge::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama_badge')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('min_point')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('max_point')
                ->numeric()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('urutan')
                ->numeric()
                ->rules(['nullable', 'integer']),
        ];
    }

    public function resolveRecord(): ?LevelBadge
    {
        return LevelBadge::query()->firstOrNew(['nama_badge' => $this->data['nama_badge']]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Level Badge selesai, ' . number_format($import->successful_rows) . ' / ' . number_format($import->total_rows) . ' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}
