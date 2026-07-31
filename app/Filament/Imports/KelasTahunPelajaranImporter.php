<?php

namespace App\Filament\Imports;

use App\Enums\RoleUser;
use App\Models\Kelas;
use App\Models\KelasTahunPelajaran;
use App\Models\TahunPelajaran;
use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

/**
 * Upsert berdasarkan (kelas_id, tahun_pelajaran_id) - sesuai unique
 * index di migration kelas_tahun_pelajarans, bukan tebakan sepihak.
 *
 * Wali kelas direferensikan via NIP (dikonfirmasi Aturan), dan WAJIB
 * bukan role super_admin (RoleUser::Admin) - konsisten dengan filter
 * form KelasTahunPelajaranResource. Baris dengan NIP milik super_admin
 * akan GAGAL divalidasi, bukan diproses diam-diam.
 */
class KelasTahunPelajaranImporter extends Importer
{
    protected static ?string $model = KelasTahunPelajaran::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('kelas_nama')
                ->label('Nama Kelas')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('tahun_pelajaran_nama')
                ->label('Nama Tahun Pelajaran')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('2025/2026'),
            ImportColumn::make('wali_kelas_nip')
                ->label('NIP Wali Kelas (opsional)')
                ->rules(['nullable', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?KelasTahunPelajaran
    {
        $kelas = Kelas::query()->where('nama', $this->data['kelas_nama'])->first();

        if (! $kelas) {
            throw new RowImportFailedException("Kelas \"{$this->data['kelas_nama']}\" tidak ditemukan.");
            // TODO: GAP-SPEC - jika nama Kelas tidak unik global (lihat
            // catatan KelasImporter), where('nama', ...) di atas bisa
            // mengambil baris yang salah tanpa error. Perlu kolom
            // tambahan (mis. kode Jurusan) di sini juga bila kasus
            // tersebut nyata terjadi di data sekolah.
        }

        $tahun = TahunPelajaran::query()->where('nama', $this->data['tahun_pelajaran_nama'])->first();

        if (! $tahun) {
            throw new RowImportFailedException("Tahun Pelajaran \"{$this->data['tahun_pelajaran_nama']}\" tidak ditemukan.");
        }

        return KelasTahunPelajaran::query()->firstOrNew([
            'kelas_id' => $kelas->id,
            'tahun_pelajaran_id' => $tahun->id,
        ]);
    }

    protected function afterSave(): void
    {
        if (empty($this->data['wali_kelas_nip'])) {
            return;
        }

        $waliKelas = User::query()->where('nip', $this->data['wali_kelas_nip'])->first();

        if (! $waliKelas) {
            throw new RowImportFailedException("User dengan NIP \"{$this->data['wali_kelas_nip']}\" tidak ditemukan.");
        }

        if ($waliKelas->role === RoleUser::Admin) {
            throw new RowImportFailedException('User dengan role super_admin tidak boleh menjadi wali kelas.');
        }

        $this->record->update(['wali_kelas_id' => $waliKelas->id]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import Kelas per Tahun Pelajaran selesai, '.number_format($import->successful_rows).' / '.number_format($import->total_rows).' baris berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' baris gagal, cek riwayat import untuk detail.';
        }

        return $body;
    }
}
