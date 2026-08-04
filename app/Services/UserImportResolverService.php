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

/**
 * Satu sumber kebenaran (Aturan poin 3) untuk logika yang SEBELUMNYA
 * terduplikasi antara UserImporter dan sheet 'user' di
 * MasterDataRegistry - drift antara keduanya yang menyebabkan bug
 * double-hashing password ditemukan pada iterasi sebelumnya.
 *
 * Method di sini TIDAK menyimpan (save()) $user - pemanggil
 * (UserImporter::beforeSave() / closure import MasterDataRegistry)
 * yang bertanggung jawab memanggil save() setelah semua resolve
 * selesai, supaya urutan efek samping (mis. gagal di tengah karena
 * kartu bentrok) tidak meninggalkan record ter-save sebagian.
 */
class UserImportResolverService
{
    /**
     * Password diisi -> dipakai apa adanya (di-hash otomatis via cast
     * 'hashed' pada Model User saat save()). Kosong & user baru ->
     * random 12 karakter. Kosong & user existing -> tidak disentuh.
     */
    public function resolvePassword(User $user, ?string $passwordBaru): void
    {
        $passwordBaru = trim((string) $passwordBaru);

        if ($passwordBaru !== '') {
            $user->password = $passwordBaru; // di-hash otomatis via cast 'hashed'

            return;
        }

        if (! $user->exists) {
            $user->password = Str::random(12); // di-hash otomatis via cast 'hashed'
        }
    }

    /**
     * Uniqueness no_kartu_rfid dicek terhadap user lain (KECUALI
     * $user sendiri jika sudah exists). Return true jika kartu LAMA
     * dihapus (dikosongkan) - dipakai pemanggil untuk merekap jumlah
     * kartu terhapus di notifikasi.
     *
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
     * TODO: GAP-SPEC - lihat catatan algoritma resolusi (URL -> unduh,
     * path di disk 'public' -> pakai langsung, path absolut di
     * filesystem server -> salin) yang sudah dikonfirmasi sebelumnya
     * pada UserImporter. PERINGATAN KEAMANAN yang sama berlaku di sini:
     * bisa membaca file APA PUN yang bisa diakses proses PHP (path
     * traversal) dan melakukan HTTP request ke alamat mana pun (SSRF).
     * SENGAJA tidak dibatasi karena baik UserImporter (hanya
     * super_admin, lihat UserResource::authorize) maupun sheet 'user'
     * di Import Master (halaman ini HANYA bisa diakses super_admin,
     * lihat ImportExportMaster::canAccess()) sama-sama dibatasi ke role
     * setertinggi ini. JANGAN panggil method ini dari konteks yang bisa
     * diakses role lain tanpa meninjau ulang dua risiko ini.
     *
     * $identitas dipakai sebagai nama file deterministik (NISN/NIP) -
     * re-import avatar yang sama akan MENIMPA file lama, bukan menumpuk
     * file baru terus-menerus.
     *
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
     * Resolusi KelasTahunPelajaran dari 3 kolom (nama kelas, kode
     * jurusan, nama tahun pelajaran) - kontrak WAJIB 3 kolom karena
     * Kelas.nama TIDAK unik secara global (hanya unik per Jurusan).
     *
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
