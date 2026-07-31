<?php

namespace App\Filament\Imports;

use App\Enums\StatusEksemplar;
use App\Models\Buku;
use App\Models\Eksemplar;
use App\Models\Rak;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert berdasarkan 'barcode' (satu baris = satu unit fisik). TERPISAH
 * dari BukuImporter (yang beroperasi per-judul/agregat stok) - lihat
 * keputusan di percakapan: menggabungkan keduanya akan mencampur dua
 * granularitas berbeda (per-judul vs per-unit fisik) dalam satu importer.
 *
 * ATURAN KERAS - tidak boleh bypass PeminjamanService/PointService (Aturan
 * poin 3, dikonfirmasi eksplisit):
 * - Baris TIDAK BOLEH set status ke/dari 'Dipinjam' - status ini HANYA
 *   boleh berubah lewat PeminjamanService::prosesPeminjaman()/
 *   prosesPengembalian(). Baris yang mencoba ini GAGAL TOTAL.
 * - Kalau eksemplar existing (ditemukan by barcode) statusnya SEDANG
 *   'Dipinjam', SELURUH baris ditolak (tidak ada field lain yang
 *   ter-update juga) - selaras persis dengan EksemplarsRelationManager
 *   yang men-disable Edit/Delete untuk status ini.
 */
class EksemplarImporter extends Importer
{
    protected static ?string $model = Eksemplar::class;

    protected ?string $bukuIdTerresolve = null;

    protected ?string $rakIdTerresolve = null;

    protected ?StatusEksemplar $statusTerresolve = null;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('barcode')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('9789793062792-1')
                ->helperText('Kunci upsert. Kalau barcode sudah ada, baris ini meng-update eksemplar tersebut (rak/status). Kalau belum ada, dibuat eksemplar baru (wajib isi ISBN Buku).'),
            ImportColumn::make('isbn')
                ->label('ISBN Buku')
                ->rules(['nullable', 'string'])
                ->example('9789793062792')
                ->helperText('WAJIB diisi untuk eksemplar BARU (barcode belum ada). Untuk eksemplar yang SUDAH ADA, kolom ini diabaikan - pemindahan eksemplar ke judul buku lain tidak didukung lewat import.')
                // lookup-only, buku_id di-assign manual di beforeSave().
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('rak')
                ->label('Rak (nama, opsional)')
                ->rules(['nullable', 'string'])
                ->example('Rak A')
                ->helperText('Isi persis sesuai nama Rak yang sudah ada. Kosongkan untuk tidak mengubah rak eksemplar existing, atau tidak memberi rak pada eksemplar baru.')
                // lookup-only, rak_id di-assign manual di beforeSave().
                ->fillRecordUsing(fn (?string $state) => null),
            ImportColumn::make('status')
                ->rules(['nullable', 'string'])
                ->example('tersedia')
                ->helperText("Hanya 'tersedia', 'rusak', atau 'hilang'. TIDAK BISA di-set/diubah ke/dari 'dipinjam' lewat import - itu hanya lewat proses Peminjaman/Pengembalian.")
                // divalidasi & di-assign manual di beforeSave(), bukan
                // langsung ->rules(['in:...']) supaya pesan errornya lebih
                // jelas via RowImportFailedException.
                ->fillRecordUsing(fn (?string $state) => null),
        ];
    }

    public function resolveRecord(): ?Eksemplar
    {
        $barcode = trim($this->data['barcode']);

        return Eksemplar::query()->where('barcode', $barcode)->first()
            ?? new Eksemplar(['barcode' => $barcode]);
    }

    protected function beforeSave(): void
    {
        $isEksemplarBaru = ! $this->record->exists;

        // GAP-SPEC ditutup: eksemplar existing statusnya Dipinjam -> baris
        // ditolak total, tidak ada field lain yang ikut ter-update.
        if (! $isEksemplarBaru && $this->record->status === StatusEksemplar::Dipinjam) {
            throw new RowImportFailedException(
                "Eksemplar dengan barcode '{$this->record->barcode}' sedang berstatus Dipinjam - tidak bisa diubah lewat import. Ubah hanya lewat proses Pengembalian."
            );
        }

        // Resolusi Buku (WAJIB untuk eksemplar baru, diabaikan untuk existing).
        if ($isEksemplarBaru) {
            $isbn = trim($this->data['isbn'] ?? '');

            if ($isbn === '') {
                throw new RowImportFailedException(
                    "Barcode '{$this->record->barcode}' belum terdaftar - kolom ISBN Buku wajib diisi untuk membuat eksemplar baru."
                );
            }

            $buku = Buku::query()->where('isbn', $isbn)->first();

            if (! $buku) {
                throw new RowImportFailedException(
                    "Buku dengan ISBN '{$isbn}' tidak ditemukan. Tambahkan Buku-nya dulu di Master Data > Buku."
                );
            }

            $this->bukuIdTerresolve = $buku->id;
        }

        // Resolusi Rak (opsional, berlaku untuk baru maupun existing).
        if (! empty($this->data['rak'])) {
            $namaRak = trim($this->data['rak']);
            $rak = Rak::query()->where('nama', $namaRak)->first();

            if (! $rak) {
                throw new RowImportFailedException(
                    "Rak '{$namaRak}' tidak ditemukan. Cek ejaan atau tambahkan Rak-nya dulu di Master Data > Rak."
                );
            }

            $this->rakIdTerresolve = $rak->id;
        }

        // Resolusi & validasi Status - TIDAK BOLEH 'dipinjam' sama sekali.
        if (! empty($this->data['status'])) {
            $statusMentah = strtolower(trim($this->data['status']));

            if ($statusMentah === StatusEksemplar::Dipinjam->value) {
                throw new RowImportFailedException(
                    "Status 'dipinjam' tidak bisa di-set lewat import - status ini hanya berubah otomatis lewat proses Peminjaman."
                );
            }

            $statusValid = collect(StatusEksemplar::cases())->firstWhere('value', $statusMentah);

            if (! $statusValid) {
                throw new RowImportFailedException(
                    "Status '{$this->data['status']}' tidak dikenal. Gunakan salah satu: tersedia, rusak, hilang."
                );
            }

            $this->statusTerresolve = $statusValid;
        } elseif ($isEksemplarBaru) {
            // default untuk eksemplar baru kalau kolom status dikosongkan
            $this->statusTerresolve = StatusEksemplar::Tersedia;
        }

        if ($this->bukuIdTerresolve !== null) {
            $this->record->buku_id = $this->bukuIdTerresolve;
        }

        if ($this->rakIdTerresolve !== null) {
            $this->record->rak_id = $this->rakIdTerresolve;
        }

        if ($this->statusTerresolve !== null) {
            $this->record->status = $this->statusTerresolve;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Eksemplar selesai, '.number_format($import->successful_rows).' dari '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal - buka riwayat import untuk lihat alasannya per baris.';
        }

        return $body;
    }
}
