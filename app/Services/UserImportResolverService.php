<?php

namespace App\Services;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\KelasTahunPelajaran;
use App\Models\TahunPelajaran;
use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserImportResolverService
{
    public function resolvePassword(User $user, ?string $passwordBaru): void
    {
        $passwordBaru = trim((string) $passwordBaru);

        if ($passwordBaru !== '') {
            $user->password = $passwordBaru;

            return;
        }

        if (! $user->exists) {
            $user->password = Str::random(12);
        }
    }

    /**
     * @throws RowImportFailedException jika nomor sudah dipakai user lain
     */
    public function resolveKartuRfid(User $user, ?string $nomorBaru): bool
    {
        $nomorBaru = trim((string) $nomorBaru);

        if ($nomorBaru === '') {
            if ($user->no_kartu_rfid !== null) {
                $user->no_kartu_rfid = null;

                return true;
            }

            return false;
        }

        $dipakaiUserLain = User::query()
            ->where('no_kartu_rfid', $nomorBaru)
            ->when($user->exists, fn ($q) => $q->whereKeyNot($user->id))
            ->exists();

        if ($dipakaiUserLain) {
            throw new RowImportFailedException("Nomor kartu \"{$nomorBaru}\" sudah dipakai user lain. Cek kembali atau kosongkan kolom ini.");
        }

        $user->no_kartu_rfid = $nomorBaru;

        return false;
    }

    /**
     * @throws RowImportFailedException jika sumber tidak bisa diresolusi
     */
    public function resolveAvatar(User $user, ?string $nilaiAvatar, string $identitas): void
    {
        $nilai = trim((string) $nilaiAvatar);

        if ($nilai === '') {
            return;
        }

        $namaFile = $this->namaFileAvatar($nilai, $identitas);

        if (Str::startsWith($nilai, ['http://', 'https://'])) {
            try {
                $response = Http::timeout(15)->get($nilai);
            } catch (\Throwable $e) {
                throw new RowImportFailedException("Gagal mengunduh avatar dari URL \"{$nilai}\": {$e->getMessage()}");
            }

            if (! $response->successful()) {
                throw new RowImportFailedException("URL avatar \"{$nilai}\" tidak bisa diakses (HTTP {$response->status()}).");
            }

            Storage::disk('public')->put($namaFile, $response->body());
            $user->avatar = $namaFile;

            return;
        }

        if (Storage::disk('public')->exists($nilai)) {
            Storage::disk('public')->copy($nilai, $namaFile);
            $user->avatar = $namaFile;

            return;
        }

        if (is_file($nilai)) {
            Storage::disk('public')->put($namaFile, file_get_contents($nilai));
            $user->avatar = $namaFile;

            return;
        }

        throw new RowImportFailedException("Avatar \"{$nilai}\" tidak ditemukan (bukan URL valid, bukan file di storage, bukan path lokal di server).");
    }

    protected function namaFileAvatar(string $sumber, string $identitas): string
    {
        $ekstensi = pathinfo(parse_url($sumber, PHP_URL_PATH) ?? $sumber, PATHINFO_EXTENSION) ?: 'jpg';

        return 'user-avatar/'.$identitas.'.'.$ekstensi;
    }

    /**
     * @throws RowImportFailedException jika salah satu tidak ditemukan/ambigu
     */
    public function resolveKtp(string $namaKelas, string $kodeJurusan, string $namaTahun): KelasTahunPelajaran
    {
        if ($kodeJurusan === '' || $namaTahun === '') {
            throw new RowImportFailedException('Kelas diisi tapi kolom jurusan_kode atau tahun_pelajaran kosong. Isi ketiganya, atau kosongkan ketiganya jika belum mau ditempatkan ke kelas.');
        }

        $jurusan = Jurusan::query()->where('kode', $kodeJurusan)->first();

        if (! $jurusan) {
            throw new RowImportFailedException("Kode jurusan \"{$kodeJurusan}\" tidak ditemukan. Cek ejaan atau tambahkan Jurusan-nya dulu di Master Data.");
        }

        $kelas = Kelas::query()
            ->where('nama', $namaKelas)
            ->where('jurusan_id', $jurusan->id)
            ->first();

        if (! $kelas) {
            throw new RowImportFailedException("Kelas \"{$namaKelas}\" dengan jurusan \"{$kodeJurusan}\" tidak ditemukan. Cek ejaan atau tambahkan Kelas-nya dulu di Master Data.");
        }

        $tahun = TahunPelajaran::query()->where('nama', $namaTahun)->first();

        if (! $tahun) {
            throw new RowImportFailedException("Tahun pelajaran \"{$namaTahun}\" tidak ditemukan. Cek ejaan atau tambahkan dulu di Master Data.");
        }

        $ktp = KelasTahunPelajaran::query()
            ->where('kelas_id', $kelas->id)
            ->where('tahun_pelajaran_id', $tahun->id)
            ->first();

        if (! $ktp) {
            throw new RowImportFailedException("Kombinasi kelas \"{$namaKelas}\" ({$kodeJurusan}) dan tahun pelajaran \"{$namaTahun}\" belum terdaftar. Import Kelas per Tahun Pelajaran dulu sebelum import User.");
        }

        return $ktp;
    }
}
