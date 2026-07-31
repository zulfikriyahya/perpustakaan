<?php

namespace App\Services;

use App\Enums\StatusAkademik;
use App\Enums\StatusRiwayatKelas;
use App\Models\KelasTahunPelajaran;
use App\Models\RiwayatKelasSiswa;
use App\Models\TahunPelajaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Satu sumber kebenaran untuk seluruh perpindahan status akademik siswa
 * (assignment awal, kenaikan kelas, tinggal kelas, lulus, keluar).
 * Resource/Action HANYA memanggil method di sini (Aturan poin 3, DRY).
 */
class KenaikanKelasService
{
    /**
     * Assignment awal / pindah kelas manual (bukan proses kenaikan massal).
     * Menutup RiwayatKelasSiswa aktif sebelumnya (jika ada) dengan status
     * 'keluar' (dianggap pindah kelas manual, bukan kenaikan reguler),
     * lalu buka riwayat baru di KTP tujuan.
     */
    public function assignKelas(User $user, KelasTahunPelajaran $ktpTujuan): void
    {
        DB::transaction(function () use ($user, $ktpTujuan) {
            $this->tutupRiwayatAktif($user, StatusRiwayatKelas::Keluar);

            RiwayatKelasSiswa::query()->create([
                'user_id' => $user->id,
                'kelas_tahun_pelajaran_id' => $ktpTujuan->id,
                'status' => StatusRiwayatKelas::Aktif,
                'tanggal_mulai' => now()->toDateString(),
            ]);

            $user->update([
                'kelas_tahun_pelajaran_id' => $ktpTujuan->id,
                'status_akademik' => StatusAkademik::Aktif,
            ]);
        });
    }

    /**
     * Proses kenaikan kelas massal dari satu KTP asal.
     *
     * @param  array<string, string>  $keputusan  [user_id => 'naik'|'tinggal'|'lulus'|'keluar']
     * @throws RuntimeException jika Tahun Pelajaran aktif tidak valid atau KTP tujuan tidak ditemukan
     */
    public function prosesKenaikan(KelasTahunPelajaran $ktpAsal, array $keputusan): array
    {
        $tahunAktif = TahunPelajaran::aktif();

        if (! $tahunAktif) {
            throw new RuntimeException('Tidak ada Tahun Pelajaran aktif. Aktifkan Tahun Pelajaran tujuan terlebih dahulu.');
        }

        if ($tahunAktif->id === $ktpAsal->tahun_pelajaran_id) {
            throw new RuntimeException('Tahun Pelajaran aktif masih sama dengan Tahun Pelajaran asal. Aktifkan Tahun Pelajaran berikutnya terlebih dahulu.');
        }

        $gagal = [];

        DB::transaction(function () use ($ktpAsal, $keputusan, $tahunAktif, &$gagal) {
            $kelasAsal = $ktpAsal->kelas;

            foreach ($keputusan as $userId => $status) {
                $user = User::query()->find($userId);

                if (! $user) {
                    continue;
                }

                $statusEnum = StatusRiwayatKelas::from($status);

                try {
                    match ($statusEnum) {
                        StatusRiwayatKelas::Naik => $this->prosesNaik($user, $kelasAsal, $tahunAktif),
                        StatusRiwayatKelas::Tinggal => $this->prosesTinggal($user, $kelasAsal, $tahunAktif),
                        StatusRiwayatKelas::Lulus => $this->prosesLulus($user),
                        StatusRiwayatKelas::Keluar => $this->prosesKeluar($user),
                        default => throw new RuntimeException("Status tidak valid: {$status}"),
                    };
                } catch (RuntimeException $e) {
                    $gagal[$user->nama] = $e->getMessage();
                }
            }
        });

        return $gagal;
    }

    protected function prosesNaik(User $user, \App\Models\Kelas $kelasAsal, TahunPelajaran $tahunTujuan): void
    {
        $kelasTujuan = \App\Models\Kelas::query()
            ->where('tingkat', $kelasAsal->tingkat + 1)
            ->where('jurusan_id', $kelasAsal->jurusan_id)
            ->first();

        if (! $kelasTujuan) {
            throw new RuntimeException('Kelas tingkat+1 dengan jurusan sama belum ada - buat Kelas tujuan terlebih dahulu.');
        }

        $ktpTujuan = KelasTahunPelajaran::query()
            ->where('kelas_id', $kelasTujuan->id)
            ->where('tahun_pelajaran_id', $tahunTujuan->id)
            ->first();

        if (! $ktpTujuan) {
            throw new RuntimeException("KTP tujuan ({$kelasTujuan->nama} - {$tahunTujuan->nama}) belum dibuat.");
        }

        $this->pindahKe($user, $ktpTujuan, StatusRiwayatKelas::Naik);
    }

    protected function prosesTinggal(User $user, \App\Models\Kelas $kelasAsal, TahunPelajaran $tahunTujuan): void
    {
        $ktpTujuan = KelasTahunPelajaran::query()
            ->where('kelas_id', $kelasAsal->id)
            ->where('tahun_pelajaran_id', $tahunTujuan->id)
            ->first();

        if (! $ktpTujuan) {
            throw new RuntimeException("KTP tinggal kelas ({$kelasAsal->nama} - {$tahunTujuan->nama}) belum dibuat.");
        }

        $this->pindahKe($user, $ktpTujuan, StatusRiwayatKelas::Tinggal);
    }

    protected function prosesLulus(User $user): void
    {
        $this->tutupRiwayatAktif($user, StatusRiwayatKelas::Lulus);

        $user->update([
            'kelas_tahun_pelajaran_id' => null,
            'status_akademik' => StatusAkademik::Lulus,
        ]);
        // Akun TETAP aktif normal (dikonfirmasi Aturan) - tidak ada
        // perubahan status_suspend/canAccessPanel di sini.
    }

    protected function prosesKeluar(User $user): void
    {
        $this->tutupRiwayatAktif($user, StatusRiwayatKelas::Keluar);

        $user->update([
            'kelas_tahun_pelajaran_id' => null,
            'status_akademik' => StatusAkademik::Keluar,
        ]);
    }

    protected function pindahKe(User $user, KelasTahunPelajaran $ktpTujuan, StatusRiwayatKelas $statusPenutup): void
    {
        $this->tutupRiwayatAktif($user, $statusPenutup);

        RiwayatKelasSiswa::query()->create([
            'user_id' => $user->id,
            'kelas_tahun_pelajaran_id' => $ktpTujuan->id,
            'status' => StatusRiwayatKelas::Aktif,
            'tanggal_mulai' => now()->toDateString(),
        ]);

        $user->update(['kelas_tahun_pelajaran_id' => $ktpTujuan->id]);
    }

    protected function tutupRiwayatAktif(User $user, StatusRiwayatKelas $statusBaru): void
    {
        RiwayatKelasSiswa::query()
            ->where('user_id', $user->id)
            ->where('status', StatusRiwayatKelas::Aktif)
            ->update([
                'status' => $statusBaru,
                'tanggal_selesai' => now()->toDateString(),
            ]);
    }
}
