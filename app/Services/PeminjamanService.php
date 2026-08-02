<?php

namespace App\Services;

use App\Enums\EventTypePoint;
use App\Enums\JenisTransaksi;
use App\Enums\KondisiBuku;
use App\Enums\StatusEksemplar;
use App\Enums\StatusPeminjaman;
use App\Enums\StatusRefund;
use App\Enums\TipeDenda;
use App\Models\Buku;
use App\Models\Denda;
use App\Models\Eksemplar;
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
     * @param  array<int, string>  $eksemplarIds
     */
    public function pinjamBuku(User $user, array $eksemplarIds, ?User $diprosesOleh = null): Transaksi
    {
        if (! $this->bisaMeminjam($user)) {
            throw new RuntimeException('User tidak dapat meminjam: suspend aktif atau limit peminjaman aktif tercapai.');
        }

        $lamaPeminjamanHari = (int) Setting::get('lama_peminjaman_hari', 7);

        $transaksi = DB::transaction(function () use ($user, $eksemplarIds, $diprosesOleh, $lamaPeminjamanHari) {
            $transaksi = Transaksi::create([
                'user_id' => $user->id,
                'jenis' => JenisTransaksi::Peminjaman,
                'diproses_oleh' => $diprosesOleh?->id,
                'tanggal' => now(),
            ]);

            foreach ($eksemplarIds as $eksemplarId) {
                $eksemplar = Eksemplar::query()->lockForUpdate()->findOrFail($eksemplarId);

                if ($eksemplar->status !== StatusEksemplar::Tersedia) {
                    throw new RuntimeException("Eksemplar barcode '{$eksemplar->barcode}' tidak tersedia (status: {$eksemplar->status->value}).");
                }

                $eksemplar->update(['status' => StatusEksemplar::Dipinjam]);

                $peminjaman = Peminjaman::create([
                    'transaksi_id' => $transaksi->id,
                    'user_id' => $user->id,
                    'eksemplar_id' => $eksemplar->id,
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

            return $transaksi->fresh('peminjamans.eksemplar.buku');
        });

        $daftarBuku = $transaksi->peminjamans->pluck('eksemplar.buku.judul')->implode(', ');
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
                $this->tandaiDenda($peminjaman, TipeDenda::Kehilangan, $this->hitungDendaKehilangan($peminjaman->eksemplar->buku));
                $peminjaman->update(['status' => StatusPeminjaman::Hilang]);
                $peminjaman->eksemplar->update(['status' => StatusEksemplar::Hilang]);

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
                $this->tandaiDenda($peminjaman, TipeDenda::Kerusakan, $this->hitungDendaKerusakan($peminjaman->eksemplar->buku));
                $peminjaman->eksemplar->update(['status' => StatusEksemplar::Rusak]);

                $this->pointService->catatEvent(
                    $peminjaman->user,
                    EventTypePoint::Kerusakan,
                    'peminjaman',
                    $peminjaman->id,
                );
            } else {
                $peminjaman->eksemplar->update(['status' => StatusEksemplar::Tersedia]);
            }

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
            $statusEksemplarBaru = match ($kondisiBaru) {
                KondisiBuku::Baik => StatusEksemplar::Tersedia,
                KondisiBuku::Rusak => StatusEksemplar::Rusak,
                KondisiBuku::Hilang => StatusEksemplar::Hilang,
            };
            $peminjaman->eksemplar->update(['status' => $statusEksemplarBaru]);

            if ($kondisiLama === KondisiBuku::Rusak && $kondisiBaru !== KondisiBuku::Rusak) {
                $this->batalkanDenda($peminjaman, TipeDenda::Kerusakan);
            }
            if ($kondisiLama === KondisiBuku::Hilang && $kondisiBaru !== KondisiBuku::Hilang) {
                $this->batalkanDenda($peminjaman, TipeDenda::Kehilangan);
            }

            if ($kondisiBaru === KondisiBuku::Rusak && $kondisiLama !== KondisiBuku::Rusak) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kerusakan, $this->hitungDendaKerusakan($peminjaman->eksemplar->buku));
                $this->pointService->catatEvent($peminjaman->user, EventTypePoint::Kerusakan, 'peminjaman', $peminjaman->id, 'Koreksi kondisi ke rusak');
            }
            if ($kondisiBaru === KondisiBuku::Hilang && $kondisiLama !== KondisiBuku::Hilang) {
                $this->tandaiDenda($peminjaman, TipeDenda::Kehilangan, $this->hitungDendaKehilangan($peminjaman->eksemplar->buku));
                $this->pointService->catatEvent($peminjaman->user, EventTypePoint::Kehilangan, 'peminjaman', $peminjaman->id, 'Koreksi kondisi ke hilang');
            }

            // TODO: GAP-SPEC - Point dari kondisi lama tidak di-reverse, sama
            // seperti perilaku sebelum perubahan ini (sudah dikonfirmasi
            // sebelumnya untuk skema Buku lama).

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

        // reference_id HARUS unik per kejadian koreksi (bukan cuma per
        // pengembalian) - kalau eksemplar yang sama dikoreksi lebih dari
        // sekali (mis. baik->rusak lalu rusak->hilang), payload variables
        // berbeda meski pengembalian_id sama. Reference_id lama yang hanya
        // memakai pengembalian->id menyebabkan gateway menolak 409 karena
        // reference_id sudah dipakai dengan payload berbeda (lihat log).
        // Menyertakan pasangan kondisi_lama->kondisi_baru + timestamp
        // presisi tinggi (dibuat SEKALI di sini, sebelum dispatch, jadi
        // tetap stabil untuk retry job yang sama) menutup celah ini tanpa
        // menambah kolom counter baru di tabel pengembalians (poin 16).
        $this->whatsappService->kirimEvent(
            eventCode: 'koreksi_kondisi_pengembalian',
            nomorTujuan: $peminjaman->user->no_telepon,
            variables: ['nama' => $peminjaman->user->nama, 'kondisi_lama' => $kondisiLama->value, 'kondisi_baru' => $kondisiBaru->value],
            referenceId: "koreksi-pengembalian-{$pengembalian->id}-{$kondisiLama->value}-{$kondisiBaru->value}-" . now()->format('YmdHisu'),
        );

        return $pengembalian;
    }

    public function laporkanHilang(Peminjaman $peminjaman): Denda
    {
        if (! in_array($peminjaman->status, [StatusPeminjaman::Aktif, StatusPeminjaman::Terlambat], true)) {
            throw new RuntimeException('Peminjaman ini sudah tidak aktif/terlambat, tidak bisa dilaporkan hilang.');
        }

        // Notifikasi 'denda_dibuat' SUDAH dikirim oleh tandaiDenda() di
        // bawah - jangan kirim ulang di sini (dulu menyebabkan reference_id
        // sama dikirim 2x dengan payload nominal berbeda -> 409 dari
        // gateway, lihat kirimEvent()/kirimPesan() §idempotency).
        $denda = DB::transaction(function () use ($peminjaman) {
            $denda = $this->tandaiDenda(
                $peminjaman,
                TipeDenda::Kehilangan,
                $this->hitungDendaKehilangan($peminjaman->eksemplar->buku),
            );

            $peminjaman->update(['status' => StatusPeminjaman::Hilang]);
            $peminjaman->eksemplar->update(['status' => StatusEksemplar::Hilang]);

            $this->pointService->catatEvent(
                $peminjaman->user,
                EventTypePoint::Kehilangan,
                'peminjaman',
                $peminjaman->id,
            );

            return $denda;
        });

        return $denda;
    }

    /**
     * BARU (iterasi ini) - kebalikan dari laporkanHilang(): dipakai KHUSUS
     * untuk Peminjaman yang jadi Hilang lewat laporkanHilang() (belum
     * pernah ada Pengembalian sama sekali). Untuk Peminjaman yang jadi
     * Hilang lewat prosesPengembalian(kondisi: Hilang), gunakan
     * koreksiKondisiPengembalian() sebagai gantinya (ada Pengembalian yang
     * bisa dikoreksi ke KondisiBuku::Baik) - caller (Filament Action) WAJIB
     * memilih method yang tepat berdasarkan ada/tidaknya $peminjaman->pengembalian.
     *
     * TODO: GAP-SPEC - Point dari event Kehilangan TIDAK direverse di sini,
     * konsisten dengan koreksiKondisiPengembalian() (perilaku yang sama
     * sudah dikonfirmasi sebelumnya untuk kasus itu).
     */
    public function bukuDitemukanKembali(Peminjaman $peminjaman): Peminjaman
    {
        if ($peminjaman->status !== StatusPeminjaman::Hilang) {
            throw new RuntimeException('Peminjaman ini tidak berstatus Hilang.');
        }

        if ($peminjaman->pengembalian) {
            throw new RuntimeException('Peminjaman ini sudah punya data Pengembalian - gunakan aksi Koreksi Kondisi di menu Pengembalian, bukan alur ini.');
        }

        $peminjaman = DB::transaction(function () use ($peminjaman) {
            $peminjaman->eksemplar->update(['status' => StatusEksemplar::Tersedia]);
            $peminjaman->update(['status' => StatusPeminjaman::Selesai]);

            // dihitung PeminjamanService - reuse batalkanDenda() (Aturan
            // poin 3), termasuk logika status_refund & notifikasi WA
            // 'denda_dibatalkan_perlu_refund' jika denda sudah lunas.
            $this->batalkanDenda($peminjaman, TipeDenda::Kehilangan);

            return $peminjaman->fresh();
        });

        // TODO: ASUMSI - eventCode 'buku_ditemukan_kembali' BARU, belum ada
        // di daftar template WhatsApp existing. Sebelum Setting
        // 'wa_template_buku_ditemukan_kembali' diisi Admin, kirimEvent()
        // akan skip otomatis (bukan error) - lihat WhatsappService::kirimEvent().
        $this->whatsappService->kirimEvent(
            eventCode: 'buku_ditemukan_kembali',
            nomorTujuan: $peminjaman->user->no_telepon,
            variables: ['nama' => $peminjaman->user->nama],
            referenceId: "buku-ditemukan-{$peminjaman->id}",
        );

        return $peminjaman;
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
            ->with('user', 'eksemplar.buku')
            ->chunkById(200, function ($peminjamans) use ($today, &$stat) {
                foreach ($peminjamans as $peminjaman) {
                    $jatuhTempo = Carbon::parse($peminjaman->tanggal_jatuh_tempo);

                    if ($jatuhTempo->isSameDay($today->copy()->addDays(3))) {
                        $this->whatsappService->kirimEvent(
                            eventCode: 'reminder_h3',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->eksemplar->buku->judul, 'jatuh_tempo' => (string) $peminjaman->tanggal_jatuh_tempo],
                            referenceId: "reminder-h3-{$peminjaman->id}-{$today->toDateString()}",
                        );
                        $stat['reminder_h3']++;
                    } elseif ($jatuhTempo->isSameDay($today->copy()->addDay())) {
                        $this->whatsappService->kirimEvent(
                            eventCode: 'reminder_h1',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->eksemplar->buku->judul, 'jatuh_tempo' => (string) $peminjaman->tanggal_jatuh_tempo],
                            referenceId: "reminder-h1-{$peminjaman->id}-{$today->toDateString()}",
                        );
                        $stat['reminder_h1']++;
                    } elseif ($jatuhTempo->lt($today)) {
                        $peminjaman->update(['status' => StatusPeminjaman::Terlambat]);

                        $this->whatsappService->kirimEvent(
                            eventCode: 'jadi_terlambat',
                            nomorTujuan: $peminjaman->user->no_telepon,
                            variables: ['nama' => $peminjaman->user->nama, 'buku' => $peminjaman->eksemplar->buku->judul],
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
     * TODO: GAP-SPEC - sebelumnya method ini TIDAK PERNAH men-set
     * status_refund maupun mengirim notifikasi 'denda_dibatalkan_perlu_refund'
     * walau komentar keterangan Denda sudah menyebut "perlu refund manual".
     * Diperbaiki (dikonfirmasi): notifikasi WA dikirim KE USER (bukan
     * Admin/Pustakawan) hanya jika denda yang dibatalkan SUDAH TERBAYAR
     * sebelum koreksi - kalau belum terbayar, tidak ada uang yang perlu
     * direfund sehingga tidak perlu status_refund maupun notifikasi.
     * Nominal ASLI ditangkap sebelum di-nol-kan supaya user tahu jumlah
     * yang dibatalkan/perlu direfund.
     */
    protected function batalkanDenda(Peminjaman $peminjaman, TipeDenda $tipe): void
    {
        $denda = Denda::query()
            ->where('peminjaman_id', $peminjaman->id)
            ->where('tipe', $tipe)
            ->latest()
            ->first();

        if (! $denda || (float) $denda->nominal === 0.0) {
            return;
        }

        $sudahTerbayar = $denda->status_lunas;
        $nominalAsli = $denda->nominal; // ditangkap sebelum di-nol-kan, dipakai untuk notifikasi WA

        $denda->update([
            'nominal' => 0,
            'status_lunas' => true,
            'tanggal_lunas' => now(),
            'status_refund' => $sudahTerbayar ? StatusRefund::PerluRefund : $denda->status_refund,
            'keterangan' => trim(($denda->keterangan ? $denda->keterangan . ' | ' : '')
                . ($sudahTerbayar
                    ? 'Dibatalkan otomatis (SUDAH TERBAYAR SEBELUM KOREKSI - perlu refund manual di luar sistem): koreksi kondisi Pengembalian.'
                    : 'Dibatalkan otomatis: koreksi kondisi Pengembalian.')),
        ]);

        if ($sudahTerbayar) {
            // dikirim ke user (dikonfirmasi) - referenceId stabil per denda
            // supaya tidak dobel kirim jika batalkanDenda() ter-trigger ulang
            // untuk denda yang sama (idempotency window gateway §9).
            $this->whatsappService->kirimEvent(
                eventCode: 'denda_dibatalkan_perlu_refund',
                nomorTujuan: $peminjaman->user->no_telepon,
                variables: [
                    'nama' => $peminjaman->user->nama,
                    'tipe' => $tipe->value,
                    'nominal' => (string) $nominalAsli,
                ],
                referenceId: "denda-dibatalkan-refund-{$denda->id}",
            );
        }
    }
}
