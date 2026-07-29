<?php

namespace App\Observers;

use App\Models\Denda;
use App\Models\PunishmentLog;
use App\Services\WhatsappService;

class DendaObserver
{
    public function __construct(
        protected WhatsappService $whatsappService,
    ) {}

    /**
     * Setiap Denda baru dibuat -> user otomatis suspend (belum lunas apapun tipenya).
     */
    public function created(Denda $denda): void
    {
        $denda->user()->update(['status_suspend' => true]);
    }

    /**
     * Saat status_lunas berubah -> cek apakah SEMUA Denda user sudah lunas
     * DAN tidak ada PunishmentLog aktif, baru unsuspend.
     *
     * TODO: GAP-SPEC - status_suspend dipakai bersama oleh Denda dan Punishment.
     * Unsuspend hanya terjadi jika kedua syarat terpenuhi, supaya user yang masih
     * dalam masa punishment tidak ke-unsuspend keliru saat Denda-nya lunas.
     */
    public function updated(Denda $denda): void
    {
        if (! $denda->wasChanged('status_lunas') || ! $denda->status_lunas) {
            return;
        }

        $masihAdaDendaBelumLunas = Denda::query()
            ->where('user_id', $denda->user_id)
            ->where('status_lunas', false)
            ->exists();

        $masihAdaPunishmentAktif = PunishmentLog::query()
            ->where('user_id', $denda->user_id)
            ->where(function ($q) {
                $q->whereNull('tanggal_berakhir')
                    ->orWhere('tanggal_berakhir', '>', now());
            })
            ->exists();

        if (! $masihAdaDendaBelumLunas && ! $masihAdaPunishmentAktif) {
            $denda->user()->update(['status_suspend' => false]);

            // eventCode 'denda_lunas' - TODO: ASUMSI, samakan dengan Setting
            // wa_template_denda_lunas.
            $this->whatsappService->kirimEvent(
                eventCode: 'denda_lunas',
                nomorTujuan: $denda->user->no_telepon,
                variables: ['nama' => $denda->user->nama],
                referenceId: "denda-lunas-{$denda->id}",
            );
        }
    }
}
