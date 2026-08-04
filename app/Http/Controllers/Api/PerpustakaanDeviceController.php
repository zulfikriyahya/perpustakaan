<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventTypePoint;
use App\Enums\JenisTransaksi;
use App\Enums\SourceKunjungan;
use App\Enums\StatusOtaFirmware;
use App\Http\Controllers\Controller;
use App\Models\DeviceLog;
use App\Models\FirmwareRelease;
use App\Models\Kunjungan;
use App\Models\Setting;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\PointService;
use App\Services\RfidResolverService;
use App\Services\WhatsappService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Endpoint untuk Attendance Machine (ESP32-C3) - kontrak persis mengikuti
 * firmware v2.3.1 (lihat internal/... referensi firmware). SETIAP perubahan
 * response shape di sini WAJIB dicek ulang terhadap parsing firmware
 * (mis. downloadRfidDb() parsing baris per baris plain text, BUKAN JSON).
 *
 * FITUR: setiap Kunjungan yang berhasil tercatat (baik lewat syncBulk()
 * maupun kirimLangsung()) membuat 1 Transaksi (jenis: kunjungan) - lihat
 * catatTransaksiKunjungan(). Ini TIDAK mengubah response/HTTP status yang
 * dikirim ke device sama sekali (kontrak firmware poin 17 Aturan tetap
 * utuh) - murni penambahan log di sisi server setelah Kunjungan berhasil
 * dibuat.
 *
 * TODO: GAP-SPEC - Transaksi hasil ini TIDAK menyimpan FK balik ke
 * Kunjungan (tabel kunjungans tidak punya kolom transaksi_id, sengaja
 * tidak ditambah migration baru - lihat diskusi terkait). Transaksi
 * murni log independen, keterangan berisi ringkasan (jam tap + device_id)
 * untuk audit manual.
 *
 * FITUR BARU (iterasi ini): setiap Kunjungan yang berhasil tercatat JUGA
 * mengirim notifikasi WhatsApp ke user bersangkutan (berlaku SEMUA role -
 * dikonfirmasi eksplisit), lewat kirimNotifikasiKunjungan() - dipanggil
 * dari prosesSatuTap() (batch) dan kirimLangsung() (real-time), satu
 * sumber kebenaran (Aturan poin 3). TIDAK mengubah response ke device.
 *
 * TODO: ASUMSI - eventCode 'kunjungan_tercatat' dan variabel
 * nama/jam_tap/device BELUM terdaftar di panel gateway zedlabs pada saat
 * penulisan kode ini (tidak ada di dokumen Template WhatsApp - Perpustakaan
 * yang tersedia). WAJIB didaftarkan manual di panel gateway dengan
 * template_code PERSIS 'kunjungan_tercatat' dan variabel dengan nama yang
 * sama, sebelum notifikasi ini benar-benar terkirim dengan isi yang benar.
 * Setting 'wa_template_kunjungan_tercatat' (lihat SettingSeeder/
 * PengaturanSistem) menyimpan template_code yang dipakai - ubah di panel
 * Pengaturan Sistem jika template_code di gateway berbeda dari default.
 *
 * PERINGATAN VOLUME (dikonfirmasi, risiko diterima sadar) - setiap tap
 * RFID sekarang mengirim 1 WA ke SEMUA role (siswa/pegawai/pustakawan/
 * admin) tanpa throttle/rate-limit tambahan. Job tetap lewat queue
 * 'whatsapp' (tidak blocking request device) - tapi volume kirim harian
 * bisa signifikan tergantung jumlah user aktif; pastikan kuota/limit di
 * sisi gateway WhatsApp mencukupi.
 */
class PerpustakaanDeviceController extends Controller
{
    public function __construct(
        protected RfidResolverService $rfidResolver,
        protected PointService $pointService,
        protected WhatsappService $whatsappService,
    ) {}

    public function ping(): JsonResponse
    {
        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Firmware: checkRfidDbVersion() - JSON { "ver": int }
     */
    public function rfidListVersion(): JsonResponse
    {
        return response()->json(['ver' => (int) Setting::get('rfid_db_ver', 0)]);
    }

    /**
     * Firmware: downloadRfidDb() - PLAIN TEXT, bukan JSON.
     * Baris pertama: "ver:<n>". Baris berikutnya: satu kartu 10-digit per baris.
     * Firmware menolak baris yang bukan persis 10 digit angka (lihat parsing
     * di downloadRfidDb: isdigit check, len == 10).
     *
     * KONTRAK BARU (v2.3.2, GAP-SPEC lama soal Content-Length ditutup) - baris
     * TERAKHIR body SEKARANG SELALU "EOF" (persis, tanpa newline trailing
     * setelahnya). Ini WAJIB ada karena server berjalan di belakang Cloudflare
     * dengan HTTP/2 - tidak ada header Content-Length yang dikirim ke device,
     * dan koneksi tidak ditutup segera setelah body selesai (keep-alive), jadi
     * device (ESP32/HTTPClient) tidak punya cara lain yang andal untuk tahu
     * transfer sudah selesai vs baru stall/putus di tengah jalan. Firmware
     * v2.3.2 ke atas menunggu baris "EOF" sebagai satu-satunya penanda sukses
     * sebelum menimpa rfid_db.txt lama dan menaikkan versi lokal (lihat
     * downloadRfidDb() di firmware) - TANPA baris ini, device v2.3.2+ akan
     * SELALU menganggap transfer gagal/terpotong dan retry terus setiap
     * siklus RFID_DB_CHECK_INTERVAL.
     *
     * Endpoint ini HANYA dipakai device Attendance Machine perpustakaan ini
     * (dikonfirmasi) - aman mengubah format body tanpa memengaruhi konsumen
     * lain.
     *
     * TODO: GAP-SPEC - hanya user dengan no_kartu_rfid berformat 10 digit
     * numeric yang akan ikut ter-generate ke daftar ini; kartu format lain
     * (mis. seeder lama 'RFID58354503') otomatis TIDAK akan muncul di device
     * karena tidak lolos filter regex di bawah - bukan bug, tapi konsekuensi
     * kontrak firmware. Data lama wajib diperbaiki ke format 10 digit.
     */
    public function rfidList(): Response
    {
        $ver = (int) Setting::get('rfid_db_ver', 0);

        $kartuList = User::query()
            ->whereNotNull('no_kartu_rfid')
            ->where('no_kartu_rfid', 'REGEXP', '^[0-9]{10}$')
            ->pluck('no_kartu_rfid');

        // BARU: baris "EOF" wajib jadi baris TERAKHIR - lihat docblock method
        // ini. implode("\n") tidak menyertakan newline trailing, sehingga
        // "EOF" persis jadi baris terakhir tanpa baris kosong setelahnya.
        $body = "ver:{$ver}\n".$kartuList->implode("\n")."\nEOF";

        return response($body, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Firmware: nvsSyncToServer() / syncQueueFile() - POST batch.
     * Request: { "data": [ { rfid, timestamp, device_id, sync_mode: true } ] }
     * Response WAJIB: { "data": [ { rfid, timestamp, status: "ok"|"error", message? } ] }
     * karena firmware membaca field "status" per item untuk logging kegagalan
     * (appendFailedLogToSD) - status HTTP selalu 200 selama body valid JSON,
     * kegagalan per-record dilaporkan lewat "status" per item, bukan HTTP code.
     *
     * KONTRAK BARU (v2.3.4, menutup celah silent data loss) - sebelumnya
     * $request->input('data', []) diam-diam jatuh ke array kosong jika body
     * bukan JSON valid/field 'data' tidak ada, lalu tetap membalas HTTP 200
     * dengan {"data":[]} - firmware lama menganggap ini SUKSES (kode 200) dan
     * MENGHAPUS file antrian, padahal NOL record tersimpan - silent data loss
     * tanpa jejak di server maupun perangkat. SEKARANG: body yang tidak
     * memuat array 'data' non-kosong yang valid akan ditolak HTTP 422 (bukan
     * 200) - firmware v2.3.4+ menangani ini sebagai kegagalan yang di-retry
     * (lihat syncQueueFileWithRetry()), TIDAK menghapus file.
     *
     * Firmware v2.3.4+ JUGA memvalidasi jumlah item di response 'data' sama
     * dengan jumlah yang dikirim SEBELUM menghapus file antrian (lihat
     * syncQueueFile()) - lapisan pertahanan kedua kalau body rusak sebagian
     * (bukan kosong total, tapi item hilang di tengah jalan).
     */
    public function syncBulk(Request $request): JsonResponse
    {
        $items = $request->input('data');

        if (! is_array($items) || count($items) === 0) {
            // BARU: body tidak valid/field 'data' tidak ada/kosong - TOLAK
            // tegas, jangan diam-diam balas 200 dengan data kosong (itu yang
            // menyebabkan device menghapus antrian tanpa satu pun record
            // benar-benar tersimpan).
            return response()->json([
                'error' => 'field "data" wajib berupa array berisi minimal 1 item',
            ], 422);
        }

        $hasil = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                // BARU: item individual yang bukan object/array valid - catat
                // sebagai error per-item, bukan diabaikan diam-diam (supaya
                // count response tetap sama dengan count request, lihat
                // validasi count di firmware).
                $hasil[] = ['rfid' => '', 'timestamp' => '', 'status' => 'error', 'message' => 'item tidak valid'];

                continue;
            }

            $rfid = (string) ($item['rfid'] ?? '');
            $timestamp = (string) ($item['timestamp'] ?? '');
            $deviceId = (string) ($item['device_id'] ?? '');

            $hasil[] = $this->prosesSatuTap($rfid, $timestamp, $deviceId);
        }

        return response()->json(['data' => $hasil]);
    }

    /**
     * Firmware: kirimLangsung() - kirim 1 tap real-time (SD tidak tersedia).
     * Firmware membaca HTTP STATUS CODE, bukan body, untuk menentukan pesan:
     * 200 = OK, 400 = duplikat ("CUKUP SEKALI!"), 404 = kartu nonaktif.
     * (403 hari libur SENGAJA tidak diimplementasikan - device sudah
     * mengunci diri sendiri di luar jam operasional per keputusan produk.)
     */
    public function kirimLangsung(Request $request): JsonResponse
    {
        $rfid = (string) $request->input('rfid', '');
        $timestamp = (string) $request->input('timestamp', '');
        $deviceId = (string) $request->input('device_id', '');

        $user = $this->rfidResolver->findByKartu($rfid);

        if (! $user) {
            return response()->json(['error' => 'kartu tidak terdaftar'], 404);
        }

        $tanggal = $this->parseTanggalDariTimestamp($timestamp);

        $duplikat = Kunjungan::query()
            ->where('user_id', $user->id)
            ->where('tanggal', $tanggal)
            ->exists();

        if ($duplikat) {
            return response()->json(['error' => 'sudah tercatat hari ini'], 400);
        }

        try {
            $kunjungan = Kunjungan::create([
                'user_id' => $user->id,
                'tanggal' => $tanggal,
                'jam_tap' => $this->parseJamDariTimestamp($timestamp),
                'source' => SourceKunjungan::Rfid,
            ]);
        } catch (QueryException $e) {
            // Race condition dengan unique index kunjungans_unik_aktif_unique.
            return response()->json(['error' => 'sudah tercatat hari ini'], 400);
        }

        $this->pointService->catatEvent(
            $user,
            EventTypePoint::Kunjungan,
            'kunjungan',
            $kunjungan->id,
        );

        $this->catatTransaksiKunjungan($user, $kunjungan, $deviceId);
        $this->kirimNotifikasiKunjungan($user, $kunjungan, $deviceId);

        return response()->json(['status' => 'ok'], 200);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        DeviceLog::query()->updateOrCreate(
            ['device_id' => (string) $request->input('device_id')],
            [
                'device_name' => $request->input('device_name'),
                'firmware_version' => $request->input('firmware'),
                'uptime_sec' => (int) $request->input('uptime_sec', 0),
                'heap_free' => (int) $request->input('heap_free', 0),
                'pending_records' => (int) $request->input('pending_records', 0),
                'scan_today' => (int) $request->input('scan_today', 0),
                'rssi' => (int) $request->input('rssi', 0),
                'sd_ok' => (bool) $request->input('sd_ok', false),
                'rfid_db_entries' => (int) $request->input('rfid_db_entries', 0),
                'online' => (bool) $request->input('online', false),
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * Firmware: fetchRemoteConfig() - hanya field yang dikirim yang akan
     * dipakai firmware (containsKey check per field), field lain diabaikan.
     */
    public function config(Request $request): JsonResponse
    {
        return response()->json([
            'sleep_start' => (int) Setting::get('device_sleep_start_hour', 18),
            'sleep_end' => (int) Setting::get('device_sleep_end_hour', 5),
            'oled_dim_start' => (int) Setting::get('device_oled_dim_start_hour', 8),
            'oled_dim_end' => (int) Setting::get('device_oled_dim_end_hour', 12),
            'sync_interval_ms' => (int) Setting::get('device_sync_interval_ms', 300000),
            'ota_check_interval_ms' => (int) Setting::get('device_ota_check_interval_ms', 30000),
        ]);
    }

    /**
     * Firmware: checkOtaUpdate() - membandingkan versi via
     * compareFirmwareVersion() (semver x.y.z). Field "update" harus true
     * HANYA jika versi rilis aktif LEBIH BARU dari yang dikirim device.
     */
    public function firmwareCheck(Request $request): JsonResponse
    {
        $versiDevice = (string) $request->input('version', '0.0.0');

        $rilisTerbaru = FirmwareRelease::query()
            ->where('aktif', true)
            ->get()
            ->sortByDesc(fn ($r) => $this->normalisasiVersi($r->version))
            ->first();

        if (! $rilisTerbaru || $this->bandingkanVersi($rilisTerbaru->version, $versiDevice) <= 0) {
            return response()->json(['update' => false]);
        }

        return response()->json([
            'update' => true,
            'version' => $rilisTerbaru->version,
            'url' => $rilisTerbaru->url,
            'md5' => $rilisTerbaru->md5,
        ]);
    }

    protected function prosesSatuTap(string $rfid, string $timestamp, string $deviceId): array
    {
        $user = $this->rfidResolver->findByKartu($rfid);

        if (! $user) {
            return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'error', 'message' => 'kartu tidak terdaftar'];
        }

        $tanggal = $this->parseTanggalDariTimestamp($timestamp);

        $duplikat = Kunjungan::query()
            ->where('user_id', $user->id)
            ->where('tanggal', $tanggal)
            ->exists();

        if ($duplikat) {
            // Bukan error sesungguhnya (device sudah kirim data valid, hanya
            // duplikat) - tetap dilaporkan "error" karena firmware hanya
            // mengenal dua status ("ok"/lainnya) untuk keputusan logging lokal.
            return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'error', 'message' => 'duplikat'];
        }

        try {
            $kunjungan = Kunjungan::create([
                'user_id' => $user->id,
                'tanggal' => $tanggal,
                'jam_tap' => $this->parseJamDariTimestamp($timestamp),
                'source' => SourceKunjungan::Rfid,
            ]);
        } catch (QueryException $e) {
            // Race condition dengan unique index kunjungans_unik_aktif_unique.
            return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'error', 'message' => 'duplikat'];
        }

        $this->pointService->catatEvent(
            $user,
            EventTypePoint::Kunjungan,
            'kunjungan',
            $kunjungan->id,
        );

        $this->catatTransaksiKunjungan($user, $kunjungan, $deviceId);
        $this->kirimNotifikasiKunjungan($user, $kunjungan, $deviceId);

        return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'ok'];
    }

    /**
     * Satu sumber kebenaran pembuatan Transaksi jenis 'kunjungan' - dipanggil
     * dari prosesSatuTap() (batch) maupun kirimLangsung() (real-time),
     * jangan duplikasi query Transaksi::create() di tempat lain (Aturan
     * poin 3).
     */
    protected function catatTransaksiKunjungan(User $user, Kunjungan $kunjungan, string $deviceId): Transaksi
    {
        return Transaksi::create([
            'user_id' => $user->id,
            'jenis' => JenisTransaksi::Kunjungan,
            'diproses_oleh' => null, // otomatis oleh device, bukan staff
            'tanggal' => now(),
            'keterangan' => "Kunjungan RFID jam {$kunjungan->jam_tap} via device '{$deviceId}'.",
        ]);
    }

    /**
     * Satu sumber kebenaran notifikasi WhatsApp untuk event Kunjungan -
     * dipanggil dari prosesSatuTap() (batch) maupun kirimLangsung()
     * (real-time), jangan duplikasi pemanggilan WhatsappService di tempat
     * lain (Aturan poin 3). Berlaku SEMUA role (dikonfirmasi eksplisit) -
     * tidak ada filter berdasarkan User.role di sini.
     *
     * TODO: ASUMSI - template_code 'kunjungan_tercatat' dan variabel
     * nama/jam_tap/device belum terdaftar di dokumen Template WhatsApp
     * yang tersedia saat penulisan kode ini - WAJIB didaftarkan manual di
     * panel gateway zedlabs sebelum notifikasi ini terkirim dengan benar.
     */
    protected function kirimNotifikasiKunjungan(User $user, Kunjungan $kunjungan, string $deviceId): void
    {
        $this->whatsappService->kirimEvent(
            eventCode: 'kunjungan_tercatat',
            nomorTujuan: $user->no_telepon,
            variables: [
                'nama' => $user->nama,
                'jam_tap' => (string) $kunjungan->jam_tap,
                'device' => $deviceId,
            ],
            referenceId: "kunjungan-{$kunjungan->id}",
        );
    }

    protected function parseTanggalDariTimestamp(string $timestamp): string
    {
        // Firmware format: "Y-m-d H:i:s"
        return substr($timestamp, 0, 10) ?: now()->toDateString();
    }

    protected function parseJamDariTimestamp(string $timestamp): string
    {
        return substr($timestamp, 11) ?: now()->toTimeString();
    }

    protected function normalisasiVersi(string $v): int
    {
        sscanf($v, '%d.%d.%d', $maj, $min, $pat);

        return ((int) $maj * 1000000) + ((int) $min * 1000) + (int) $pat;
    }

    protected function bandingkanVersi(string $a, string $b): int
    {
        return $this->normalisasiVersi($a) <=> $this->normalisasiVersi($b);
    }

    /**
     * Kontrak BARU: firmware lapor hasil OTA setelah proses update/reboot.
     * Request: { "device_id": string, "version": string, "status": "success"|"failed","error"?: string }
     * Response selalu { "status": "ok" } dengan HTTP 200 selama device_id
     * terisi - device tidak perlu retry berdasarkan response ini (best
     * effort logging, bukan bagian kritis alur OTA).
     *
     * Jika status "success", firmware_version di DeviceLog ikut
     * diperbarui ke versi baru (device sudah berhasil boot versi
     * tersebut). Jika "failed", firmware_version TIDAK diubah (device
     * masih menjalankan versi lama) - hanya ota_error yang dicatat.
     */
    public function firmwareReport(Request $request): JsonResponse
    {
        $deviceId = (string) $request->input('device_id');

        if ($deviceId === '') {
            return response()->json(['error' => 'device_id wajib diisi'], 422);
        }

        $status = StatusOtaFirmware::tryFrom((string) $request->input('status'));

        if (! $status) {
            return response()->json(['error' => 'status harus "success" atau "failed"'], 422);
        }

        $update = [
            'ota_status' => $status,
            'ota_error' => $status === StatusOtaFirmware::Gagal ? (string) $request->input('error', '') : null,
            'ota_reported_at' => now(),
        ];

        if ($status === StatusOtaFirmware::Sukses && $request->filled('version')) {
            $update['firmware_version'] = (string) $request->input('version');
        }

        DeviceLog::query()->updateOrCreate(
            ['device_id' => $deviceId],
            $update
        );

        return response()->json(['status' => 'ok']);
    }
}
