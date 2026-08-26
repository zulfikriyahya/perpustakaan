<?php

namespace App\Http\Controllers\Api;

use App\Enums\SourceKunjungan;
use App\Enums\StatusOtaFirmware;
use App\Http\Controllers\Controller;
use App\Models\DeviceLog;
use App\Models\FirmwareRelease;
use App\Models\Kunjungan;
use App\Models\Setting;
use App\Models\User;
use App\Services\KunjunganService;
use App\Services\RfidResolverService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PerpustakaanDeviceController extends Controller
{
    public function __construct(
        protected RfidResolverService $rfidResolver,
        protected KunjunganService $kunjunganService,
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

    public function rfidList(): Response
    {
        $ver = (int) Setting::get('rfid_db_ver', 0);

        $kartuList = User::query()
            ->whereNotNull('no_kartu_rfid')
            ->where('no_kartu_rfid', 'REGEXP', '^[0-9]{10}$')
            ->pluck('no_kartu_rfid');

        $body = "ver:{$ver}\n".$kartuList->implode("\n")."\nEOF";

        return response($body, 200)->header('Content-Type', 'text/plain');
    }

    public function syncBulk(Request $request): JsonResponse
    {
        $items = $request->input('data');

        if (! is_array($items) || count($items) === 0) {
            return response()->json([
                'error' => 'field "data" wajib berupa array berisi minimal 1 item',
            ], 422);
        }

        $hasil = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
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
            $this->kunjunganService->catatKunjungan(
                user: $user,
                source: SourceKunjungan::Rfid,
                sumberLabel: $deviceId,
                tanggal: $tanggal,
                jamTap: $this->parseJamDariTimestamp($timestamp),
            );
        } catch (QueryException $e) {
            return response()->json(['error' => 'sudah tercatat hari ini'], 400);
        }

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
            return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'error', 'message' => 'duplikat'];
        }

        try {
            $this->kunjunganService->catatKunjungan(
                user: $user,
                source: SourceKunjungan::Rfid,
                sumberLabel: $deviceId,
                tanggal: $tanggal,
                jamTap: $this->parseJamDariTimestamp($timestamp),
            );
        } catch (QueryException $e) {
            return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'error', 'message' => 'duplikat'];
        }

        return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'ok'];
    }

    protected function parseTanggalDariTimestamp(string $timestamp): string
    {
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
