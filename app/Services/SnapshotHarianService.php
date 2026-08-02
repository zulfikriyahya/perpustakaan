<?php

namespace App\Services;

use App\Enums\RoleUser;
use App\Enums\StatusPeminjaman;
use App\Models\Buku;
use App\Models\Denda;
use App\Models\Kunjungan;
use App\Models\Peminjaman;
use App\Models\SnapshotHarian;
use App\Models\User;
use Carbon\Carbon;

/**
 * Satu sumber kebenaran perhitungan snapshot harian lintas-domain (Aturan
 * poin 3, DRY) - dipanggil ProsesCronHarianPerpustakaan (hari berjalan)
 * dan BackfillSnapshotHarian (histori). Metrik di sini adalah PROXY yang
 * sama persis semantiknya dengan yang sebelumnya dihitung live di
 * PeminjamanStatsWidget (lihat TODO: GAP-SPEC di masing-masing method) -
 * hanya waktu eksekusinya yang dipindah dari "tiap request dashboard" ke
 * "sekali per hari", BUKAN perbaikan akurasi proxy itu sendiri.
 */
class SnapshotHarianService
{
    public function catatUntukTanggal(Carbon $tanggal): SnapshotHarian
    {
        $tanggal = $tanggal->copy()->startOfDay();

        return SnapshotHarian::query()->updateOrCreate(
            ['tanggal' => $tanggal->toDateString()],
            [
                // TODO: GAP-SPEC - proxy "peminjaman baru per hari",
                // dipindahkan apa adanya dari PeminjamanStatsWidget.
                'peminjaman_baru' => Peminjaman::query()
                    ->whereDate('tanggal_pinjam', $tanggal)
                    ->count(),

                // TODO: GAP-SPEC - proxy "jatuh tempo pada tanggal tsb DAN
                // berstatus Terlambat SAAT service ini dijalankan" - untuk
                // snapshot hari berjalan (dipanggil setelah
                // PeminjamanService::prosesCronHarian() di command cron),
                // transisi status hari itu sudah tercermin. Untuk backfill
                // histori jauh ke belakang, angka ini merefleksikan status
                // TERKINI record tsb, bukan status riil pada tanggal
                // tersebut (sama seperti sebelumnya, butuh tabel histori
                // status terpisah untuk akurat penuh).
                'peminjaman_terlambat' => Peminjaman::query()
                    ->whereDate('tanggal_jatuh_tempo', $tanggal)
                    ->where('status', StatusPeminjaman::Terlambat)
                    ->count(),

                // TODO: GAP-SPEC - proxy "denda baru terbit per hari".
                'denda_baru' => Denda::query()
                    ->whereDate('created_at', $tanggal)
                    ->count(),

                // Akurat - Kunjungan.tanggal adalah histori peristiwa asli.
                'kunjungan' => Kunjungan::query()
                    ->whereDate('tanggal', $tanggal)
                    ->count(),

                // Akurat - kumulatif, Buku tidak di-hard-delete dalam alur normal.
                'total_judul_buku' => Buku::query()
                    ->whereDate('created_at', '<=', $tanggal->copy()->endOfDay())
                    ->count(),

                // TODO: GAP-SPEC - kumulatif PENDAFTARAN user, tidak
                // memperhitungkan riwayat status_suspend berubah.
                'total_anggota_aktif' => User::query()
                    ->whereNotIn('role', [RoleUser::Admin, RoleUser::Pustakawan])
                    ->whereDate('created_at', '<=', $tanggal->copy()->endOfDay())
                    ->count(),
            ],
        );
    }
}
