<?php

namespace App\Filament\Imports;

use App\Models\Reward;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class RewardImporter extends Importer
{
    protected static ?string $model = Reward::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Voucher buku gratis'),
            ImportColumn::make('deskripsi')
                ->rules(['nullable', 'string'])
                ->example('Dapat menukar 1 buku baru dari katalog toko rekanan'),
            ImportColumn::make('threshold_point')
                ->label('Ambang batas point')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer'])
                ->example('500'),
            ImportColumn::make('aktif')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->example('1'),
        ];
    }

    public function resolveRecord(): ?Reward
    {
        $nama = trim($this->data['nama']);

        return Reward::query()->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])->first()
            ?? new Reward(['nama' => $nama]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Reward selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}
