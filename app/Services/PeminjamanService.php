<?php

namespace App\Services;

use App\Enums\EventTypePoint;
use App\Enums\JenisTransaksi;
use App\Enums\KondisiBuku;
use App\Enums\StatusPeminjaman;
use App\Enums\TipeDenda;
use App\Models\Buku;
use App\Models\Denda;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Setting;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeminjamanService
{
    public function __construct(
        protected PointService $pointService,
        protected WhatsappService $whatsappService,
    ) {}

    public function bisaMeminjam(User $user): bool
    {
        if ($user->status_suspend) {
            return false;
        }

        $jumlahAktif = Peminjaman::query()
            ->where('user_id', $user->id)
            ->where('status', StatusPeminjaman::Aktif)
            ->count();

        $maxAktif = (int) Setting::get('max_peminjaman_aktif', 3);

        return $jumlahAktif < $maxAktif;
    }

    /**
     * @param  array<int, string>  $bukuIds
     */
    public function pinjamBuku(User $user, array $bukuIds, ?User $diprosesOleh = null): Transaksi
    {
        if (! $this->bisaMeminjam($user)) {
            throw new RuntimeException('User tidak dapat meminjam: suspend aktif atau limit peminjaman aktif tercapai.');
        }

        $lamaPeminjamanHari = (int) Setting::get('lama_peminjaman_hari', 7);

        $transaksi = DB::transaction(function () use ($user, $bukuIds, $diprosesOleh, $lamaPeminjamanHari) {
            $transaksi = Transaksi::create([
                'user_id' => $user->id,
                'jenis' => JenisTransaksi::Peminjaman,
                'diproses_oleh' => $diprosesOleh?->id,
                'tanggal' => now(),
            ]);

            foreach ($bukuIds as $bukuId) {
                $buku = Buku::query()->lockForUpdate()->findOrFail($bukuId);

                if ($buku->stok < 1) {
                    throw new RuntimeException("Stok buku '{$buku->judul}' habis.");
                }

                $buku->decrement('stok');

                $peminjaman = Peminjaman::create([
                    'transaksi_id' => $transaksi->id,
                    'user_id' => $user->id,
                    'buku_id' => $buku->id,
                    'tanggal_pinjam' => now()->toDateString(),
                    'tanggal_jatuh_tempo' => now()->addDays($lamaPeminjamanHari)->toDateString(),
                    'status' => StatusPeminjaman::Aktif,
                    'diproses_oleh' => $diprosesOleh?->id,
                ]);

                $this->pointService->catatEvent(
                    $user,
                    EventTypePoint::Peminjaman,
                    'peminjaman',
                    $peminjaman->id,
                );
            }

            return $transaksi->fresh('peminjamans.buku');
        });

        $daftarBuku = $transaksi->peminjamans->pluck('buku.judul')->implode(', ');
        $jatuhTempo = $transaksi->peminjamans->first()?->tanggal_jatuh_tempo;

        $this->whatsappService->kirimEvent(
            eventCode: 'peminjaman_aktif',
            nomorTujuan: $user->no_telepon,
            variables: ['nama' => $user->nama, 'daftar_buku' => $daftarBuku, 'jatuh_tempo' => (string) $jatuhTempo],
            referenceId: "peminjaman-{$transaksi->id}",
        );

        return $transaksi;
    }

    public function prosesPengembalian(
        Peminjaman $peminjaman,
        KondisiBuku $kondisi,
        ?string $catatan = null,
        ?User $diprosesOleh = null,
    ): Pengembalian {
        if (! in_array($peminjaman->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true)) {
            throw new RuntimeException('Peminjaman ini sudah tidak aktif/terlambat, tidak bisa diproses pengembaliannya.');
        }

        $pengembalian = DB::transaction(function () use ($peminjaman, $kondisi, $catatan, $diprosesOleh) {
            $pengembalian = Pengembalian::create([
                'peminjaman_id' => $peminjaman->id,
                'tanggal_kembali' => now()->toDateString(),
                'kondisi' => $kondisi,
                'catatan' => $catatan,
                'diproses_oleh' => $diprosesOleh?->id,
            ]);

            if ($kondisi === KondisiBuku::Hilang) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kehilangan, $this->hitungDendaKehilangan($peminjaman->buku));
                $peminjaman->update(['status' => StatusPeminjaman::Hilang]);

                $this->pointService->catatEvent(
                    $peminjaman->user,
                    EventTypePoint::Kehilangan,
                    'peminjaman',
                    $peminjaman->id,
                );

                return $pengembalian;
            }

            $hariTelat = $this->hitungHariTelat($peminjaman);
            if ($hariTelat > 0) {
                $this->tandaiDenda($peminjaman, TipeDenda::Keterlambatan, $this->hitungDendaKeterlambatan($hariTelat));
            }

            if ($kondisi === KondisiBuku::Rusak) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kerusakan, $this->hitungDendaKerusakan($peminjaman->buku));

                $this->pointService->catatEvent(
                    $peminjaman->user,
                    EventTypePoint::Kerusakan,
                    'peminjaman',
                    $peminjaman->id,
                );
            }

            $peminjaman->buku()->increment('stok');
            $peminjaman->update(['status' => StatusPeminjaman::Selesai]);

            $this->pointService->catatEvent(
                $peminjaman->user,
                EventTypePoint::Pengembalian,
                'peminjaman',
                $peminjaman->id,
            );

            return $pengembalian;
        });

        $peminjaman->refresh();
        $this->whatsappService->kirimEvent(
            eventCode: 'pengembalian_diproses',
            nomorTujuan: $peminjaman->user->no_telepon,
            variables: ['nama' => $peminjaman->user->nama, 'kondisi' => $kondisi->value],
            referenceId: "pengembalian-{$pengembalian->id}",
        );

        return $pengembalian;
    }

    /**
     * Koreksi kondisi Pengembalian yang SUDAH final (dipanggil dari Action
     * "Koreksi Kondisi" di PengembalianResource - Pustakawan/Admin, lihat
     * PengembalianPolicy). Method TERPISAH dari prosesPengembalian() supaya
     * alur normal tidak berubah (Aturan poin 9/10).
     *
     * Efek: sesuaikan stok, batalkan Denda dari kondisi lama yang sudah
     * tidak relevan, catat Denda baru sesuai kondisi baru, update status
     * Peminjaman, update record Pengembalian.
     */
    public function koreksiKondisiPengembalian(
        Pengembalian $pengembalian,
        KondisiBuku $kondisiBaru,
        ?string $catatan = null,
        ?User $diprosesOleh = null,
    ): Pengembalian {
        $peminjaman = $pengembalian->peminjaman;
        $kondisiLama = $pengembalian->kondisi;

        if ($kondisiLama === $kondisiBaru) {
            throw new RuntimeException('Kondisi baru sama dengan kondisi sebelumnya, tidak ada yang dikoreksi.');
        }

        if (! in_array($peminjaman->status, [StatusPeminjaman::Selesai, StatusPeminjaman::Hilang], true)) {
            throw new RuntimeException('Peminjaman ini belum berstatus final (Selesai/Hilang), gunakan proses pengembalian normal.');
        }

        $pengembalian = DB::transaction(function () use ($pengembalian, $peminjaman, $kondisiLama, $kondisiBaru, $catatan, $diprosesOleh) {
            // Sesuaikan stok: buku fisik dianggap kembali/hilang sesuai kondisi baru.
            if ($kondisiLama === KondisiBuku::Hilang && $kondisiBaru !== KondisiBuku::Hilang) {
                $peminjaman->buku()->increment('stok');
            } elseif ($kondisiLama !== KondisiBuku::Hilang && $kondisiBaru === KondisiBuku::Hilang) {
                $peminjaman->buku()->decrement('stok');
            }

            // Batalkan Denda dari kondisi lama yang tidak lagi berlaku.
            if ($kondisiLama === KondisiBuku::Rusak && $kondisiBaru !== KondisiBuku::Rusak) {
                $this->batalkanDenda($peminjaman, TipeDenda::Kerusakan);
            }
            if ($kondisiLama === KondisiBuku::Hilang && $kondisiBaru !== KondisiBuku::Hilang) {
                $this->batalkanDenda($peminjaman, TipeDenda::Kehilangan);
            }

            // Catat Denda baru sesuai kondisi baru.
            if ($kondisiBaru === KondisiBuku::Rusak && $kondisiLama !== KondisiBuku::Rusak) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kerusakan, $this->hitungDendaKerusakan($peminjaman->buku));
                $this->pointService->catatEvent($peminjaman->user, EventTypePoint::Kerusakan, 'peminjaman', $peminjaman->id, 'Koreksi kondisi ke rusak');
            }
            if ($kondisiBaru === KondisiBuku::Hilang && $kondisiLama !== KondisiBuku::Hilang) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kehilangan, $this->hitungDendaKehilangan($peminjaman->buku));
                $this->pointService->catatEvent($peminjaman->user, EventTypePoint::Kehilangan, 'peminjaman', $peminjaman->id, 'Koreksi kondisi ke hilang');
            }

            // TODO: GAP-SPEC - Point yang sudah tercatat dari kondisi lama
            // (mis. event Pengembalian/Kerusakan/Kehilangan sebelumnya) TIDAK
            // di-reverse saat koreksi; hanya menambah event baru sesuai
            // kondisi baru. Akumulasi point/badge/reward/punishment tidak
            // mundur otomatis - butuh keputusan terpisah jika diperlukan.

            $peminjaman->update([
                'status' => $kondisiBaru === KondisiBuku::Hilang ? StatusPeminjaman::Hilang : StatusPeminjaman::Selesai,
            ]);

            $pengembalian->update([
                'kondisi' => $kondisiBaru,
                'catatan' => $catatan ?? $pengembalian->catatan,
                'diproses_oleh' => $diprosesOleh?->id,
            ]);

            return $pengembalian->fresh();
        });

        $this->whatsappService->kirimEvent(
            eventCode: 'koreksi_kondisi_pengembalian',
            nomorTujuan: $peminjaman->user->no_telepon,
            variables: ['nama' => $peminjaman->user->nama, 'kondisi_lama' => $kondisiLama->value, 'kondisi_baru' => $kondisiBaru->value],
            referenceId: "koreksi-pengembalian-{$pengembalian->id}",
        );

        return $pengembalian;
    }

    public function laporkanHilang(Peminjaman $peminjaman): Denda
    {
        if (! in_array($peminjaman->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true)) {
            throw new RuntimeException('Peminjaman ini sudah tidak aktif/terlambat, tidak bisa dilaporkan hilang.');
        }

        $denda = DB::transaction(function () use ($peminjaman) {
            $denda = $this->tandaiDenda(
                $peminjaman,
                TipeDenda::Kehilangan,
                $this->hitungDendaKehilangan($peminjaman->buku),
            );

            $peminjaman->update(['status' => StatusPeminjaman::Hilang]);

            $this->pointService->catatEvent(
                $peminjaman->user,
                EventTypePoint::Kehilangan,
                'peminjaman',
                $peminjaman->id,
            );

            return $denda;
        });

        $this->whatsappService->kirimEvent(
            eventCode: 'denda_dibuat',
            nomorTujuan: $peminjaman->user->no_telepon,
            variables: ['nama' => $peminjaman->user->nama, 'tipe' => 'kehilangan', 'nominal' => (string) $denda->nominal],
            referenceId: "denda-{$denda->id}",
        );

        return $denda;
    }

    /**
     * @return array{reminder_h3: int, reminder_h1: int, jadi_terlambat: int}
     */
    public function prosesCronHarian(): array
    {
        $today = Carbon::today();
        $stat = ['reminder_h3' => 0, 'reminder_h1' => 0, 'jadi_terlambat' => 0];

        Peminjaman::query()
            ->where('status', StatusPeminjaman::Aktif)
            ->with('user', 'buku')
            ->chunkById(200, function ($peminjamans) use ($today, &$stat) {
                foreach ($peminjamans as $peminjaman) {
                    $jatuhTempo = Carbon::parse($peminjaman->tanggal_jatuh_tempo);

                    if ($jatuhTempo->isSameDay($today->copy()->addDays(3))) {
                        $this->whatsappService->kirimEvent(
                            eventCode: 'reminder_h3',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->buku->judul, 'jatuh_tempo' => (string) $peminjaman->tanggal_jatuh_tempo],
                            referenceId: "reminder-h3-{$peminjaman->id}-{$today->toDateString()}",
                        );
                        $stat['reminder_h3']++;
                    } elseif ($jatuhTempo->isSameDay($today->copy()->addDay())) {
                        $this->whatsappService->kirimEvent(
                            eventCode: 'reminder_h1',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->buku->judul, 'jatuh_tempo' => (string) $peminjaman->tanggal_jatuh_tempo],
                            referenceId: "reminder-h1-{$peminjaman->id}-{$today->toDateString()}",
                        );
                        $stat['reminder_h1']++;
                    } elseif ($jatuhTempo->lt($today)) {
                        $peminjaman->update(['status' => StatusPeminjaman::Terlambat]);

                        $this->whatsappService->kirimEvent(
                            eventCode: 'jadi_terlambat',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->buku->judul],
                            referenceId: "terlambat-{$peminjaman->id}-{$today->toDateString()}",
                        );
                        $stat['jadi_terlambat']++;
                    }
                }
            });

        return $stat;
    }

    protected function hitungHariTelat(Peminjaman $peminjaman): int
    {
        $jatuhTempo = Carbon::parse($peminjaman->tanggal_jatuh_tempo)->startOfDay();
        $hariIni = Carbon::today();

        if ($hariIni->lessThanOrEqualTo($jatuhTempo)) {
            return 0;
        }

        return $jatuhTempo->diffInDays($hariIni);
    }

    protected function hitungDendaKeterlambatan(int $hariTelat): float
    {
        $tarifPerHari = (float) Setting::get('tarif_denda_per_hari', 500);

        return $hariTelat * $tarifPerHari;
    }

    protected function hitungDendaKerusakan(Buku $buku): float
    {
        $persentase = (float) Setting::get('persentase_denda_kerusakan', 100);

        return round(((float) $buku->harga_ganti) * ($persentase / 100), 2);
    }

    protected function hitungDendaKehilangan(Buku $buku): float
    {
        return (float) $buku->harga_ganti;
    }

    protected function tandaiDenda(Peminjaman $peminjaman, TipeDenda $tipe, float $nominal): Denda
    {
        $denda = Denda::create([
            'peminjaman_id' => $peminjaman->id,
            'user_id' => $peminjaman->user_id,
            'tipe' => $tipe,
            'nominal' => $nominal,
            'status_lunas' => false,
        ]);

        $this->whatsappService->kirimEvent(
            eventCode: 'denda_dibuat',
            nomorTujuan: $peminjaman->user->no_telepon,
            variables: ['nama' => $peminjaman->user->nama, 'tipe' => $tipe->value, 'nominal' => (string) $nominal],
            referenceId: "denda-{$denda->id}",
        );

        return $denda;
    }

    /**
     * Batalkan Denda aktif (tipe tertentu) milik satu Peminjaman - dipanggil
     * saat koreksi kondisi Pengembalian menghilangkan alasan Denda tersebut.
     * TIDAK soft-delete (riwayat tetap auditable) - nominal dipaksa 0 dan
     * status_lunas dipaksa true (memicu DendaObserver::updated() -> cek
     * auto-unsuspend user, sudah dikonfirmasi sebagai perilaku yang diinginkan).
     */
    protected function batalkanDenda(Peminjaman $peminjaman, TipeDenda $tipe): void
    {
        $denda = Denda::query()
            ->where('peminjaman_id', $peminjaman->id)
            ->where('tipe', $tipe)
            ->latest()
            ->first();

        if (! $denda || (float) $denda->nominal === 0.0) {
            return; // tidak ada Denda aktif untuk tipe ini, atau sudah pernah dibatalkan
        }

        $sudahTerbayar = $denda->status_lunas;

        $denda->update([
            'nominal' => 0,
            'status_lunas' => true,
            'tanggal_lunas' => now(),
            'keterangan' => trim(($denda->keterangan ? $denda->keterangan . ' | ' : '')
                . ($sudahTerbayar
                    ? 'Dibatalkan otomatis (SUDAH TERBAYAR SEBELUM KOREKSI - perlu refund manual di luar sistem): koreksi kondisi Pengembalian.'
                    : 'Dibatalkan otomatis: koreksi kondisi Pengembalian.')),
        ]);

        // TODO: GAP-SPEC - jika $sudahTerbayar true, sistem ini tidak
        // menangani proses refund (uang fisik/transfer) - hanya menandai
        // audit trail. Refund di luar sistem, keputusan Admin/Pustakawan.
    }
}
