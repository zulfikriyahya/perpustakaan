<?php

namespace App\Filament\Resources\PeminjamanResource\Pages;

use App\Filament\Resources\PeminjamanResource;
use App\Models\Peminjaman;
use App\Models\User;
use App\Services\PeminjamanService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreatePeminjaman extends CreateRecord
{
    protected static string $resource = PeminjamanResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    /**
     * Override total - TIDAK memakai Peminjaman::create($data) bawaan
     * Filament. Seluruh proses (validasi limit/suspend, stok, jatuh tempo,
     * Point, WA) wajib lewat PeminjamanService::pinjamBuku() (Aturan poin 3).
     *
     * $data berisi 'user_id' dan 'buku_ids' (array) dari form - bukan kolom
     * asli tabel peminjamans, jadi tidak bisa diserahkan ke Model::create().
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            $transaksi = app(PeminjamanService::class)->pinjamBuku(
                user: User::findOrFail($data['user_id']),
                bukuIds: $data['buku_ids'],
                diprosesOleh: auth()->user(),
            );

            // Filament expects instance dari $this->getModel() (Peminjaman) -
            // kembalikan salah satu baris hasil transaksi (bisa multi-buku).
            return $transaksi->peminjamans->first();
        } catch (RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('Gagal memproses peminjaman')
                ->body($e->getMessage())
                ->send();

            $this->halt();
        }
    }
}
