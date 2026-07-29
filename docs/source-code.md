# SOURCE CODE - perpustakaan

## app/Console/Commands/ProsesCronHarianPerpustakaan.php
```php
<?php

namespace App\Console\Commands;

use App\Services\PeminjamanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper Artisan command untuk PeminjamanService::prosesCronHarian().
 * Logic perhitungan/transisi status TIDAK diduplikasi di sini - lihat
 * Aturan poin 3 (Prinsip DRY), seluruh logic tetap di PeminjamanService.
 */
class ProsesCronHarianPerpustakaan extends Command
{
    protected $signature = 'perpustakaan:cron-harian';

    protected $description = 'Jalankan cron harian Peminjaman: reminder H-3/H-1 dan transisi status Terlambat.';

    public function handle(PeminjamanService $peminjamanService): int
    {
        $mulai = now();

        $stat = $peminjamanService->prosesCronHarian();

        $durasiMs = now()->diffInMilliseconds($mulai);

        $this->info(sprintf(
            'Cron harian selesai: reminder_h3=%d, reminder_h1=%d, jadi_terlambat=%d (%d ms)',
            $stat['reminder_h3'],
            $stat['reminder_h1'],
            $stat['jadi_terlambat'],
            $durasiMs,
        ));

        Log::info('ProsesCronHarianPerpustakaan selesai.', [
            'reminder_h3' => $stat['reminder_h3'],
            'reminder_h1' => $stat['reminder_h1'],
            'jadi_terlambat' => $stat['jadi_terlambat'],
            'durasi_ms' => $durasiMs,
        ]);

        return self::SUCCESS;
    }
}

```
---

## app/Enums/EventTypePoint.php
```php
<?php

namespace App\Enums;

enum EventTypePoint: string
{
    case Kunjungan = 'kunjungan';
    case Peminjaman = 'peminjaman';
    case Pengembalian = 'pengembalian';
    case Kerusakan = 'kerusakan';
    case Kehilangan = 'kehilangan';
}

```
---

## app/Enums/GroupSetting.php
```php
<?php

namespace App\Enums;

enum GroupSetting: string
{
    case Peminjaman = 'peminjaman';
    case Point = 'point';
    case Notifikasi = 'notifikasi';
    case Denda = 'denda';
    case Device = 'device';
    case Whatsapp = 'whatsapp';
}

```
---

## app/Enums/JenisTransaksi.php
```php
<?php

namespace App\Enums;

enum JenisTransaksi: string
{
    case Peminjaman = 'peminjaman';
    case Kunjungan = 'kunjungan';
    case PembayaranDenda = 'pembayaran_denda';
}

```
---

## app/Enums/KondisiBuku.php
```php
<?php

namespace App\Enums;

enum KondisiBuku: string
{
    case Baik = 'baik';
    case Rusak = 'rusak';
    case Hilang = 'hilang';
}

```
---

## app/Enums/RoleUser.php
```php
<?php

namespace App\Enums;

enum RoleUser: string
{
    case Siswa = 'siswa';
    case Pegawai = 'pegawai';
    case Pustakawan = 'pustakawan';
    case Admin = 'admin';
}

```
---

## app/Enums/SourceKunjungan.php
```php
<?php

namespace App\Enums;

enum SourceKunjungan: string
{
    case Rfid = 'rfid';
    case Manual = 'manual';
}

```
---

## app/Enums/StatusPeminjaman.php
```php
<?php

namespace App\Enums;

enum StatusPeminjaman: string
{
    case Aktif = 'aktif';
    case Terlambat = 'terlambat';
    case Selesai = 'selesai';
    case Hilang = 'hilang';
}

```
---

## app/Enums/TipeDenda.php
```php
<?php

namespace App\Enums;

enum TipeDenda: string
{
    case Keterlambatan = 'keterlambatan';
    case Kerusakan = 'kerusakan';
    case Kehilangan = 'kehilangan';
}

```
---

## app/Exceptions/WhatsappGatewayException.php
```php
<?php

namespace App\Exceptions;

use Exception;

class WhatsappGatewayException extends Exception
{
    public function __construct(
        public readonly int $statusCode,
        string $pesanError,
    ) {
        parent::__construct("Gateway WhatsApp mengembalikan status {$statusCode}: {$pesanError}");
    }
}

```
---

## app/Http/Controllers/Api/PerpustakaanDeviceController.php
```php
<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventTypePoint;
use App\Enums\SourceKunjungan;
use App\Http\Controllers\Controller;
use App\Models\DeviceLog;
use App\Models\FirmwareRelease;
use App\Models\Kunjungan;
use App\Models\Setting;
use App\Services\PointService;
use App\Services\RfidResolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint untuk Attendance Machine (ESP32-C3) - kontrak persis mengikuti
 * firmware v2.3.1 (lihat internal/... referensi firmware). SETIAP perubahan
 * response shape di sini WAJIB dicek ulang terhadap parsing firmware
 * (mis. downloadRfidDb() parsing baris per baris plain text, BUKAN JSON).
 */
class PerpustakaanDeviceController extends Controller
{
    public function __construct(
        protected RfidResolverService $rfidResolver,
        protected PointService $pointService,
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
     * TODO: GAP-SPEC - hanya user dengan no_kartu_rfid berformat 10 digit
     * numeric yang akan ikut ter-generate ke daftar ini; kartu format lain
     * (mis. seeder lama 'RFID58354503') otomatis TIDAK akan muncul di device
     * karena tidak lolos filter regex di bawah - bukan bug, tapi konsekuensi
     * kontrak firmware. Data lama wajib diperbaiki ke format 10 digit.
     */
    public function rfidList(): \Illuminate\Http\Response
    {
        $ver = (int) Setting::get('rfid_db_ver', 0);

        $kartuList = \App\Models\User::query()
            ->whereNotNull('no_kartu_rfid')
            ->where('no_kartu_rfid', 'REGEXP', '^[0-9]{10}$')
            ->pluck('no_kartu_rfid');

        $body = "ver:{$ver}\n" . $kartuList->implode("\n");

        return response($body, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Firmware: nvsSyncToServer() / syncQueueFile() - POST batch.
     * Request: { "data": [ { rfid, timestamp, device_id, sync_mode: true } ] }
     * Response WAJIB: { "data": [ { rfid, timestamp, status: "ok"|"error", message? } ] }
     * karena firmware membaca field "status" per item untuk logging kegagalan
     * (appendFailedLogToSD) - status HTTP selalu 200 selama body valid JSON,
     * kegagalan per-record dilaporkan lewat "status" per item, bukan HTTP code.
     */
    public function syncBulk(Request $request): JsonResponse
    {
        $items = $request->input('data', []);
        $hasil = [];

        foreach ($items as $item) {
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
        } catch (\Illuminate\Database\QueryException $e) {
            // Race condition dengan unique index kunjungans_unik_aktif_unique.
            return response()->json(['error' => 'sudah tercatat hari ini'], 400);
        }

        $this->pointService->catatEvent(
            $user,
            EventTypePoint::Kunjungan,
            'kunjungan',
            $kunjungan->id,
        );

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
            ->sortByDesc(fn($r) => $this->normalisasiVersi($r->version))
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
        } catch (\Illuminate\Database\QueryException $e) {
            // Race condition dengan unique index kunjungans_unik_aktif_unique.
            return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'error', 'message' => 'duplikat'];
        }

        $this->pointService->catatEvent(
            $user,
            EventTypePoint::Kunjungan,
            'kunjungan',
            $kunjungan->id,
        );

        return ['rfid' => $rfid, 'timestamp' => $timestamp, 'status' => 'ok'];
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
}

```
---

## app/Http/Controllers/Controller.php
```php
<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}

```
---

## app/Http/Middleware/AuthenticateDeviceApiKey.php
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi sederhana untuk endpoint device ESP32 (bukan HMAC seperti WA
 * Gateway) - firmware mengirim header X-API-KEY statis yang sama untuk
 * seluruh device (lihat kirimLangsung/nvsSyncToServer/dst. di firmware).
 *
 * Perubahan pada key ini WAJIB dikomunikasikan ke seluruh device di lapangan
 * (harus di-reconfigure via provisioning mode) - lihat Aturan poin 17.
 */
class AuthenticateDeviceApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-KEY');
        $expected = (string) config('services.device_gateway.api_key');

        if (! $expected || ! $key || ! hash_equals($expected, $key)) {
            return response()->json(['error' => 'API key tidak valid'], 401);
        }

        return $next($request);
    }
}

```
---

## app/Jobs/KirimNotifikasiWhatsapp.php
```php
<?php

namespace App\Jobs;

use App\Exceptions\WhatsappGatewayException;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pengirim notifikasi WhatsApp - dijalankan di queue 'whatsapp' terpisah
 * agar pengiriman WA tidak blocking proses utama (Peminjaman/Denda/Point).
 * Lihat Logic Module §11 checklist dan Aturan poin 3 (Prinsip DRY).
 *
 * Job ini menerima template_code yang SUDAH di-resolve dari Setting (lihat
 * WhatsappService::kirimEvent()) - lookup Setting tetap dilakukan sinkron
 * di pemanggil supaya job tidak perlu query Setting berulang dan supaya
 * kegagalan "template belum dikonfigurasi" tetap terdeteksi segera (bukan
 * baru diketahui setelah job diproses worker).
 */
class KirimNotifikasiWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Konsisten dengan --tries=3 pada supervisor worker queue 'whatsapp'
     * (lihat conf.d/*.conf, program *-whatsapp).
     */
    public int $tries = 3;

    /**
     * Konsisten dengan --timeout=30 pada supervisor worker queue 'whatsapp'.
     * Job tidak boleh berjalan lebih lama dari timeout worker.
     */
    public int $timeout = 25;

    /**
     * Backoff singkat karena kegagalan WA umumnya transient (rate limit,
     * sesi belum ready) - lihat dok kontrak API §9 Guard Rail.
     */
    public array $backoff = [5, 15, 30];

    public function __construct(
        protected string $templateCode,
        protected string $nomorTujuan,
        protected array $variables,
        protected ?string $referenceId,
    ) {}

    public function handle(WhatsappService $whatsappService): void
    {
        try {
            $whatsappService->kirimPesan(
                templateCode: $this->templateCode,
                recipient: $this->nomorTujuan,
                variables: $this->variables,
                referenceId: $this->referenceId,
            );
        } catch (WhatsappGatewayException $e) {
            Log::error("KirimNotifikasiWhatsapp: gagal mengirim template '{$this->templateCode}' ke {$this->nomorTujuan}: {$e->getMessage()}");

            // TODO: GAP-SPEC - dilempar ulang supaya queue worker melakukan
            // retry sesuai $tries/$backoff. Jika error bersifat permanen
            // (mis. template_code tidak terhubung ke API key, lihat kontrak
            // API §2.2 kode 403), retry tidak akan membantu dan job akan
            // berakhir di failed_jobs setelah 3 percobaan - belum ada
            // pembedaan error permanen vs transient di level job ini.
            throw $e;
        }
    }

    /**
     * Dipanggil otomatis oleh queue setelah seluruh percobaan ($tries) habis.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("KirimNotifikasiWhatsapp: job gagal permanen setelah {$this->tries} percobaan. Template '{$this->templateCode}' ke {$this->nomorTujuan}: {$exception->getMessage()}");
    }
}

```
---

## app/Models/Buku.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Buku extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'judul',
        'cover',
        'penulis',
        'penerbit',
        'isbn',
        'barcode',
        'rak_id',
        'harga_ganti',
        'stok',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'harga_ganti' => 'decimal:2',
        ];
    }

    public function rak(): BelongsTo
    {
        return $this->belongsTo(Rak::class);
    }

    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class);
    }

    public function peminjamans(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }
}

```
---

## app/Models/Denda.php
```php
<?php

namespace App\Models;

use App\Enums\TipeDenda;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Denda extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'peminjaman_id',
        'user_id',
        'tipe',
        'nominal',
        'status_lunas',
        'tanggal_lunas',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tipe' => TipeDenda::class,
            'nominal' => 'decimal:2',
            'status_lunas' => 'boolean',
            'tanggal_lunas' => 'datetime',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

```
---

## app/Models/DeviceLog.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DeviceLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'device_id',
        'device_name',
        'firmware_version',
        'uptime_sec',
        'heap_free',
        'pending_records',
        'scan_today',
        'rssi',
        'sd_ok',
        'rfid_db_entries',
        'online',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'sd_ok' => 'boolean',
            'online' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }
}

```
---

## app/Models/FirmwareRelease.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FirmwareRelease extends Model
{
    use HasUuids;

    protected $fillable = [
        'version',
        'url',
        'md5',
        'aktif',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }
}

```
---

## app/Models/Kategori.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kategori extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    public function bukus(): BelongsToMany
    {
        return $this->belongsToMany(Buku::class);
    }

    public function raks(): BelongsToMany
    {
        return $this->belongsToMany(Rak::class);
    }
}

```
---

## app/Models/Kunjungan.php
```php
<?php

namespace App\Models;

use App\Enums\SourceKunjungan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kunjungan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_tap',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal' => 'date',
            'source' => SourceKunjungan::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Unik per hari per user hanya untuk baris AKTIF (deleted_at IS NULL) - dijaga
    // oleh generated column `unik_aktif` + unique index di DB (lihat migration
    // fix_unique_kunjungan_softdelete_aware). Kolom `unik_aktif` sengaja TIDAK
    // dimasukkan ke $fillable/casts karena murni computed oleh DB, jangan pernah
    // diisi manual dari Filament/kode aplikasi.
}

```
---

## app/Models/LevelBadge.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LevelBadge extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama_badge',
        'min_point',
        'max_point',
        'icon',
        'urutan',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

```
---

## app/Models/Peminjaman.php
```php
<?php

namespace App\Models;

use App\Enums\StatusPeminjaman;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Peminjaman extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'transaksi_id',
        'user_id',
        'buku_id',
        'tanggal_pinjam',
        'tanggal_jatuh_tempo',
        'status',
        'diproses_oleh',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal_pinjam' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'status' => StatusPeminjaman::class,
            'diproses_oleh' => 'integer',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function pengembalian(): HasOne
    {
        return $this->hasOne(Pengembalian::class);
    }

    public function dendas(): HasMany
    {
        return $this->hasMany(Denda::class);
    }
}

```
---

## app/Models/Pengembalian.php
```php
<?php

namespace App\Models;

use App\Enums\KondisiBuku;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengembalian extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'peminjaman_id',
        'tanggal_kembali',
        'kondisi',
        'catatan',
        'diproses_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kembali' => 'date',
            'kondisi' => KondisiBuku::class,
            'diproses_oleh' => 'integer',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}

```
---

## app/Models/Point.php
```php
<?php

namespace App\Models;

use App\Enums\EventTypePoint;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Point extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'event_type',
        'nilai',
        'ref_type',
        'ref_id',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'event_type' => EventTypePoint::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ref_type/ref_id: polymorphic manual, bukan Eloquent relation - lihat PointService.
}

```
---

## app/Models/PunishmentLog.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PunishmentLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'punishment_id',
        'tanggal_diterapkan',
        'tanggal_berakhir',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal_diterapkan' => 'datetime',
            'tanggal_berakhir' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function punishment(): BelongsTo
    {
        return $this->belongsTo(Punishment::class);
    }
}

```
---

## app/Models/Punishment.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Punishment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'deskripsi',
        'threshold_point_minus',
        'durasi_suspend_hari',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function punishmentLogs(): HasMany
    {
        return $this->hasMany(PunishmentLog::class);
    }
}

```
---

## app/Models/Rak.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rak extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama',
        'lokasi',
    ];

    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(Kategori::class);
    }

    public function bukus(): HasMany
    {
        return $this->hasMany(Buku::class);
    }
}

```
---

## app/Models/RewardLog.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'reward_id',
        'tanggal_didapat',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tanggal_didapat' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }
}

```
---

## app/Models/Reward.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reward extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'deskripsi',
        'threshold_point',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function rewardLogs(): HasMany
    {
        return $this->hasMany(RewardLog::class);
    }
}

```
---

## app/Models/Setting.php
```php
<?php

namespace App\Models;

use App\Enums\GroupSetting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'value',
        'group',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'group' => GroupSetting::class,
        ];
    }

    /**
     * Ambil nilai Setting berdasarkan key, dengan fallback default.
     * Di-cache 5 menit agar tidak query berulang di proses batch (cron, dsb).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }
}

```
---

## app/Models/Transaksi.php
```php
<?php

namespace App\Models;

use App\Enums\JenisTransaksi;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'jenis',
        'diproses_oleh',
        'tanggal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'jenis' => JenisTransaksi::class,
            'diproses_oleh' => 'integer',
            'tanggal' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function peminjamans(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }
}

```
---

## app/Models/User.php
```php
<?php

namespace App\Models;

use App\Enums\RoleUser;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements AuthenticatableContract
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'avatar',
        'nama',
        'role',
        'nisn',
        'nip',
        'kelas',
        'jabatan',
        'no_telepon',
        'no_kartu_rfid',
        'password',
        'status_suspend',
        'akumulasi_point',
        'level_badge_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'role' => RoleUser::class,
            'status_suspend' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function levelBadge(): BelongsTo
    {
        return $this->belongsTo(LevelBadge::class);
    }

    // TODO: GAP-SPEC - resolusi login multi-identifier (nisn/nip ATAU no_telepon) belum
    // diimplementasikan. Filament default hanya support satu kolom username tetap.
    // Butuh custom Login Page yang query:
    //   User::where('nisn', $login)->orWhere('nip', $login)->orWhere('no_telepon', $login)->first()
}

```
---

## app/Observers/DendaObserver.php
```php
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

```
---

## app/Observers/UserObserver.php
```php
<?php

namespace App\Observers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Menaikkan Setting 'rfid_db_ver' setiap kali kartu RFID user berubah, supaya
 * device (ESP32 Attendance Machine) bisa mendeteksi versi baru lewat
 * GET /api/perpustakaan/rfid-list/version dan mengunduh ulang daftar kartu.
 *
 * TODO: GAP-SPEC - "perubahan" didefinisikan sebagai: no_kartu_rfid diisi/diubah,
 * ATAU user dengan kartu terisi di-soft-delete/dipulihkan/dihapus permanen
 * (kartu tersebut harus hilang dari daftar aktif di device). Perubahan pada
 * kolom lain (nama, kelas, dst) TIDAK memicu bump versi.
 */
class UserObserver
{
    public function created(User $user): void
    {
        if ($user->no_kartu_rfid) {
            $this->bumpVersion();
        }
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('no_kartu_rfid')) {
            $this->bumpVersion();
        }
    }

    public function deleted(User $user): void
    {
        if ($user->no_kartu_rfid) {
            $this->bumpVersion();
        }
    }

    public function restored(User $user): void
    {
        if ($user->no_kartu_rfid) {
            $this->bumpVersion();
        }
    }

    protected function bumpVersion(): void
    {
        $current = (int) Setting::get('rfid_db_ver', 0);
        $next = $current + 1;

        Setting::query()->updateOrCreate(
            ['key' => 'rfid_db_ver'],
            ['value' => (string) $next, 'group' => \App\Enums\GroupSetting::Device]
        );

        // Setting::get() di-cache 5 menit (lihat Setting model) - hapus cache
        // supaya device langsung melihat versi baru, bukan menunggu TTL habis.
        Cache::forget('setting:rfid_db_ver');
    }
}

```
---

## app/Providers/AppServiceProvider.php
```php
<?php

namespace App\Providers;

use App\Models\Denda;
use App\Models\User;
use App\Observers\DendaObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Denda::observe(DendaObserver::class);
        User::observe(UserObserver::class);
    }
}

```
---

## app/Providers/Filament/DashboardPanelProvider.php
```php
<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

```
---

## app/Services/PeminjamanService.php
```php
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

    /**
     * Hari telat = 0 jika belum/tepat jatuh tempo. Sengaja ditulis tanpa
     * bergantung pada konvensi tanda diffInDays($other, false) - meski sudah
     * diverifikasi arahnya benar, bentuk ini rawan salah baca/salah refactor
     * di masa depan karena tanda hasilnya tidak eksplisit di tempat pemanggilan.
     */
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
}

```
---

## app/Services/PointService.php
```php
<?php

namespace App\Services;

use App\Enums\EventTypePoint;
use App\Models\LevelBadge;
use App\Models\Point;
use App\Models\Punishment;
use App\Models\PunishmentLog;
use App\Models\Reward;
use App\Models\RewardLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function __construct(
        protected WhatsappService $whatsappService,
    ) {}

    /**
     * Catat event Point untuk user, lalu jalankan seluruh alur otomatis:
     * update akumulasi -> cek Badge -> cek Reward -> cek Punishment.
     *
     * $refType/$refId: polymorphic manual (bukan Eloquent morph), misal
     * 'peminjaman' + $peminjaman->id.
     */
    public function catatEvent(
        User $user,
        EventTypePoint $eventType,
        ?string $refType = null,
        ?string $refId = null,
        ?string $keterangan = null,
    ): Point {
        // TODO: ASUMSI - key Setting mengikuti pola 'point_{event_type}', mis.
        // 'point_kunjungan', 'point_peminjaman', 'point_kerusakan' (boleh negatif).
        // Spec tidak menyebutkan nama key pasti.
        $nilai = (int) Setting::get("point_{$eventType->value}", 0);

        return DB::transaction(function () use ($user, $eventType, $nilai, $refType, $refId, $keterangan) {
            $point = Point::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'nilai' => $nilai,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'keterangan' => $keterangan,
            ]);

            $user->increment('akumulasi_point', $nilai);
            $user->refresh();

            $this->cekBadge($user);
            $this->cekReward($user);
            $this->cekPunishment($user);

            return $point;
        });
    }

    /**
     * Update level_badge_id user jika akumulasi_point masuk rentang badge lain.
     */
    protected function cekBadge(User $user): void
    {
        $badge = LevelBadge::query()
            ->where('min_point', '<=', $user->akumulasi_point)
            ->where(function ($q) use ($user) {
                $q->whereNull('max_point')
                    ->orWhere('max_point', '>=', $user->akumulasi_point);
            })
            ->orderByDesc('urutan')
            ->first();

        if ($badge && $badge->id !== $user->level_badge_id) {
            $user->update(['level_badge_id' => $badge->id]);

            // eventCode 'badge_naik' - TODO: ASUMSI, samakan dengan Setting
            // wa_template_badge_naik yang harus diisi Admin di panel WA Gateway.
            $this->whatsappService->kirimEvent(
                eventCode: 'badge_naik',
                nomorTujuan: $user->no_telepon,
                variables: ['nama' => $user->nama, 'badge' => $badge->nama_badge],
                referenceId: "badge-{$user->id}-{$badge->id}",
            );
        }
    }

    /**
     * Cek apakah user baru saja melewati threshold Reward yang belum pernah didapat.
     */
    protected function cekReward(User $user): void
    {
        $rewardTercapai = Reward::query()
            ->where('aktif', true)
            ->where('threshold_point', '<=', $user->akumulasi_point)
            ->whereDoesntHave('rewardLogs', fn($q) => $q->where('user_id', $user->id))
            ->get();

        foreach ($rewardTercapai as $reward) {
            $rewardLog = RewardLog::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'tanggal_didapat' => now(),
            ]);

            // eventCode 'reward_didapat' - TODO: ASUMSI, samakan dengan Setting
            // wa_template_reward_didapat.
            $this->whatsappService->kirimEvent(
                eventCode: 'reward_didapat',
                nomorTujuan: $user->no_telepon,
                variables: ['nama' => $user->nama, 'reward' => $reward->nama],
                referenceId: "reward-{$rewardLog->id}",
            );
        }
    }

    /**
     * Cek apakah user baru saja melewati threshold Punishment (point minus).
     * TODO: GAP-SPEC - overlap status_suspend dengan Denda. Suspend dari Punishment
     * TIDAK ditandai lunas/tidak seperti Denda, melainkan berdasarkan tanggal_berakhir.
     * DendaObserver sudah disesuaikan untuk ikut mengecek PunishmentLog aktif sebelum
     * unsuspend user (lihat app/Observers/DendaObserver.php).
     */
    protected function cekPunishment(User $user): void
    {
        $punishmentTercapai = Punishment::query()
            ->where('aktif', true)
            ->where('threshold_point_minus', '>=', $user->akumulasi_point)
            ->whereDoesntHave('punishmentLogs', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where(function ($q2) {
                        $q2->whereNull('tanggal_berakhir')
                            ->orWhere('tanggal_berakhir', '>', now());
                    });
            })
            ->get();

        foreach ($punishmentTercapai as $punishment) {
            $punishmentLog = PunishmentLog::create([
                'user_id' => $user->id,
                'punishment_id' => $punishment->id,
                'tanggal_diterapkan' => now(),
                'tanggal_berakhir' => $punishment->durasi_suspend_hari
                    ? now()->addDays($punishment->durasi_suspend_hari)
                    : null,
            ]);

            $user->update(['status_suspend' => true]);

            // eventCode 'punishment_diterapkan' - TODO: ASUMSI, samakan dengan
            // Setting wa_template_punishment_diterapkan.
            $this->whatsappService->kirimEvent(
                eventCode: 'punishment_diterapkan',
                nomorTujuan: $user->no_telepon,
                variables: ['nama' => $user->nama, 'alasan' => $punishment->nama],
                referenceId: "punishment-{$punishmentLog->id}",
            );
        }
    }
}

```
---

## app/Services/RfidResolverService.php
```php
<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

/**
 * Resolusi User dari input reader RFID/keyboard-wedge (tersambung ke komputer)
 * untuk konteks Peminjaman/Pengembalian, maupun dari kartu RFID yang dikirim
 * device Attendance Machine (ESP32) untuk konteks Kunjungan. Satu sumber
 * kebenaran untuk matching kartu-ke-user (Aturan poin 3) - jangan menulis ulang
 * query 'no_kartu_rfid' di tempat lain.
 */
class RfidResolverService
{
    /**
     * Cari user berdasarkan nomor kartu RFID saja (tanpa fallback NISN, tanpa
     * throw). Dipakai konteks yang tidak boleh melempar exception, mis.
     * endpoint device (respons 404/"error" per item, bukan 500).
     */
    public function findByKartu(string $kartu): ?User
    {
        return User::query()->where('no_kartu_rfid', $kartu)->first();
    }

    /**
     * @throws RuntimeException jika user tidak ditemukan dari kartu maupun NISN
     */
    public function resolveUser(string $inputKartuAtauNisn): User
    {
        $user = $this->findByKartu($inputKartuAtauNisn);

        if ($user) {
            return $user;
        }

        $user = User::query()->where('nisn', $inputKartuAtauNisn)->first();

        if ($user) {
            return $user;
        }

        throw new RuntimeException(
            "User tidak ditemukan untuk kartu/NISN '{$inputKartuAtauNisn}'. Pastikan kartu sudah didaftarkan atau gunakan NISN yang valid."
        );
    }
}

```
---

## app/Services/WhatsappService.php
```php
<?php

namespace App\Services;

use App\Enums\GroupSetting;
use App\Exceptions\WhatsappGatewayException;
use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Wrapper untuk WhatsApp Gateway (whatsapp.zedlabs.id API v1, autentikasi HMAC-SHA256).
 * Signature dihitung dari raw body bytes persis seperti yang dikirim - lihat
 * dokumen kontrak API bagian 2.1. Jangan format ulang body setelah signing.
 */
class WhatsappService
{
    protected string $baseUrl;

    protected string $apiKeyId;

    protected string $secret;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.whatsapp_gateway.base_url'), '/');
        $this->apiKeyId = (string) config('services.whatsapp_gateway.api_key_id');
        $this->secret = (string) config('services.whatsapp_gateway.secret');
        $this->timeout = (int) config('services.whatsapp_gateway.timeout', 15);
    }

    /**
     * Kirim pesan berbasis template terdaftar di panel gateway.
     * Dipanggil SINKRON oleh KirimNotifikasiWhatsapp job (bukan langsung
     * oleh Controller/Observer/Service lain) - lihat kirimEvent() di bawah.
     *
     * @param  array<string, mixed>  $variables
     * @param  array<string, mixed>|null  $media  Lihat dokumen kontrak API bagian 2.2 (jenis: dokumen|gambar|video|link|kontak)
     * @return array{job_id: string, status: string}
     *
     * @throws WhatsappGatewayException
     */
    public function kirimPesan(
        string $templateCode,
        string $recipient,
        array $variables = [],
        ?array $media = null,
        ?string $referenceId = null,
    ): array {
        $body = [
            'template_code' => $templateCode,
            'recipient' => $recipient,
            'variables' => $variables,
            'media' => $media,
        ];

        if ($referenceId !== null) {
            $body['reference_id'] = $referenceId;
        }

        // json_encode default PHP tanpa spasi tambahan - konsisten dengan body yang
        // ditandatangani. JSON_UNESCAPED_SLASHES/UNICODE agar tidak ada karakter
        // escape tak perlu yang mengubah representasi byte.
        $bodyString = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        [$status, $payload] = $this->kirimRequest('POST', '/api/v1/messages', $bodyString);

        if (! in_array($status, [200, 202], true)) {
            throw new WhatsappGatewayException($status, $payload['error'] ?? 'unknown error');
        }

        return [
            'job_id' => $payload['job_id'] ?? '',
            'status' => $payload['status'] ?? '',
        ];
    }

    /**
     * Ambil status terkini satu job (queued|processing|sent|delivered|read|failed).
     *
     * @return array{job_id: string, status: string, waktu_antre: string, waktu_kirim: string, keterangan_gagal: string}
     *
     * @throws WhatsappGatewayException
     */
    public function ambilStatus(string $jobId): array
    {
        [$status, $payload] = $this->kirimRequest('GET', "/api/v1/messages/{$jobId}", '');

        if ($status !== 200) {
            throw new WhatsappGatewayException($status, $payload['error'] ?? 'unknown error');
        }

        return $payload;
    }

    /**
     * Titik masuk TUNGGAL untuk seluruh notifikasi WA di aplikasi (Aturan
     * poin 3 - Prinsip DRY). Sejak iterasi ini, method ini TIDAK memanggil
     * gateway secara langsung - hanya me-resolve template_code dari Setting
     * (sinkron, cepat, sudah di-cache 5 menit oleh Setting::get) lalu
     * men-dispatch KirimNotifikasiWhatsapp ke queue 'whatsapp', supaya
     * Peminjaman/Denda/Point tidak menunggu request HTTP ke gateway selesai
     * (Logic Module §11 checklist: "Job/Queue terpisah untuk notifikasi WA").
     *
     * Key pola: wa_template_{event_code}, mis. 'wa_template_peminjaman_aktif'.
     *
     * TODO: ASUMSI - nama key Setting per event belum ditentukan spec, memakai pola
     * di atas. Admin wajib mengisi Setting ini + membuat/mengaitkan template_code
     * yang sesuai di panel gateway (dok bagian 4.2) sebelum notifikasi terkirim.
     *
     * Jika template belum dikonfigurasi (Setting kosong), pengiriman di-skip dan
     * dicatat sebagai warning - TIDAK di-dispatch ke queue sama sekali, supaya
     * tidak menumpuk job yang pasti gagal karena template_code kosong.
     */
    public function kirimEvent(
        string $eventCode,
        string $nomorTujuan,
        array $variables = [],
        ?string $referenceId = null,
    ): void {
        $templateCode = Setting::get("wa_template_{$eventCode}");

        if (! $templateCode) {
            Log::warning("WhatsappService: template untuk event '{$eventCode}' belum dikonfigurasi di Setting, notifikasi di-skip.");

            return;
        }

        KirimNotifikasiWhatsapp::dispatch(
            $templateCode,
            $nomorTujuan,
            $variables,
            $referenceId ?? (string) Str::uuid(),
        )->onQueue('whatsapp');
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    protected function kirimRequest(string $method, string $path, string $bodyString): array
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $bodyString, $this->secret);

        $headers = [
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->apiKeyId,
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ];

        $response = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->withBody($bodyString, 'application/json')
            ->send($method, $this->baseUrl . $path);

        return [$response->status(), $response->json() ?? []];
    }
}

```
---

## routes/api.php
```php
<?php

use App\Http\Controllers\Api\PerpustakaanDeviceController;
use Illuminate\Support\Facades\Route;

/*
 * Endpoint Attendance Machine (ESP32-C3) - path WAJIB persis sama dengan
 * apiBaseUrl + path yang dipanggil firmware (lihat firmware v2.3.1):
 *   GET  /api/perpustakaan/ping
 *   GET  /api/perpustakaan/rfid-list/version
 *   GET  /api/perpustakaan/rfid-list
 *   POST /api/perpustakaan/sync-bulk
 *   POST /api/perpustakaan            (kirimLangsung - real-time, SD tidak tersedia)
 *   POST /api/perpustakaan/heartbeat
 *   GET  /api/perpustakaan/config
 *   POST /api/perpustakaan/firmware/check
 *
 * Semua endpoint di bawah prefix ini wajib header X-API-KEY (lihat
 * AuthenticateDeviceApiKey) - firmware mengirim header ini di SETIAP request
 * termasuk GET. Perubahan path/method di sini WAJIB dicek ulang terhadap
 * firmware yang sudah terpasang di lapangan (Aturan poin 17).
 */

Route::prefix('perpustakaan')
    ->middleware('device.api.key')
    ->group(function () {
        Route::get('/ping', [PerpustakaanDeviceController::class, 'ping']);
        Route::get('/rfid-list/version', [PerpustakaanDeviceController::class, 'rfidListVersion']);
        Route::get('/rfid-list', [PerpustakaanDeviceController::class, 'rfidList']);
        Route::post('/sync-bulk', [PerpustakaanDeviceController::class, 'syncBulk']);
        Route::post('/', [PerpustakaanDeviceController::class, 'kirimLangsung']);
        Route::post('/heartbeat', [PerpustakaanDeviceController::class, 'heartbeat']);
        Route::get('/config', [PerpustakaanDeviceController::class, 'config']);
        Route::post('/firmware/check', [PerpustakaanDeviceController::class, 'firmwareCheck']);
    });

```
---

## routes/console.php
```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Cron harian Peminjaman (Logic Module §8): reminder H-3/H-1 dan transisi
 * ke Terlambat. Dijadwalkan jam 06:00 - SEBELUM jam operasional device RFID
 * default (Setting device_sleep_end_hour, default 05:00) supaya notifikasi
 * WA dan perubahan status sudah selesai saat perpustakaan mulai beroperasi.
 *
 * TODO: GAP-SPEC - jam 06:00 dipilih sebagai baseline aman (asumsi logis,
 * belum ada Setting khusus untuk jam eksekusi cron ini). Jika Admin butuh
 * jam berbeda, sebaiknya dibuat Setting terpisah (mis. 'cron_harian_jam')
 * daripada hardcode - belum diimplementasikan pada iterasi ini.
 *
 * withoutOverlapping(): mencegah eksekusi ganda jika scheduler:run tumpang
 * tindih (mis. proses sebelumnya masih jalan karena data besar).
 * onOneServer(): aman jika deployment multi-server di masa depan.
 */
Schedule::command('perpustakaan:cron-harian')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer();

```
---

## routes/web.php
```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

```
---

## config/app.php
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];

```
---

## config/auth.php
```php
<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];

```
---

## config/cache.php
```php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => env('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "storage", "octane",
    |                    "session", "failover", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'storage' => [
            'driver' => 'storage',
            'disk' => env('CACHE_STORAGE_DISK'),
            'path' => env('CACHE_STORAGE_PATH', 'framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

        'failover' => [
            'driver' => 'failover',
            'stores' => [
                'database',
                'array',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-cache-'),

    /*
    |--------------------------------------------------------------------------
    | Serializable Classes
    |--------------------------------------------------------------------------
    |
    | This value determines the classes that can be unserialized from cache
    | storage. By default, no PHP classes will be unserialized from your
    | cache to prevent gadget chain attacks if your APP_KEY is leaked.
    |
    */

    'serializable_classes' => false,

];

```
---

## config/database.php
```php
<?php

use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];

```
---

## config/filesystems.php
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

```
---

## config/logging.php
```php
<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', env('APP_NAME', 'Laravel')),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];

```
---

## config/mail.php
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

];

```
---

## config/queue.php
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis",
    |          "deferred", "background", "failover", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

        'deferred' => [
            'driver' => 'deferred',
        ],

        'background' => [
            'driver' => 'background',
        ],

        'failover' => [
            'driver' => 'failover',
            'connections' => [
                'database',
                'deferred',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];

```
---

## config/services.php
```php
<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp_gateway' => [
        'base_url' => env('WHATSAPP_GATEWAY_BASE_URL', 'https://whatsapp.zedlabs.id'),
        'api_key_id' => env('WHATSAPP_GATEWAY_API_KEY_ID'),
        'secret' => env('WHATSAPP_GATEWAY_SECRET'),
        'timeout' => env('WHATSAPP_GATEWAY_TIMEOUT', 15),
    ],

    'device_gateway' => [
        // Satu key statis untuk seluruh Attendance Machine (ESP32) - lihat
        // AuthenticateDeviceApiKey. Rotasi key wajib disertai reconfigure
        // seluruh device via provisioning mode (poin 17 Aturan).
        'api_key' => env('DEVICE_GATEWAY_API_KEY'),
    ],

];

```
---

## config/session.php
```php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option determines the default session driver that is utilized for
    | incoming requests. Laravel supports a variety of storage options to
    | persist session data. Database storage is a great default choice.
    |
    | Supported: "file", "cookie", "database", "memcached",
    |            "redis", "dynamodb", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to expire immediately when the browser is closed then you may
    | indicate that via the expire_on_close configuration option.
    |
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify that all of your session data
    | should be encrypted before it's stored. All encryption is performed
    | automatically by Laravel and you may use the session like normal.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When utilizing the "file" session driver, the session files are placed
    | on disk. The default storage location is defined here; however, you
    | are free to provide another location where they should be stored.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | When using the "database" or "redis" session drivers, you may specify a
    | connection that should be used to manage these sessions. This should
    | correspond to a connection in your database configuration options.
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table to
    | be used to store sessions. Of course, a sensible default is defined
    | for you; however, you're welcome to change this to another table.
    |
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Cache Store
    |--------------------------------------------------------------------------
    |
    | When using one of the framework's cache driven session backends, you may
    | define the cache store which should be used to store the session data
    | between requests. This must match one of your defined cache stores.
    |
    | Affects: "dynamodb", "memcached", "redis"
    |
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery
    |--------------------------------------------------------------------------
    |
    | Some session drivers must manually sweep their storage location to get
    | rid of old sessions from storage. Here are the chances that it will
    | happen on a given request. By default, the odds are 2 out of 100.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the session cookie that is created by
    | the framework. Typically, you should not need to change this value
    | since doing so does not grant a meaningful security improvement.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | The session cookie path determines the path for which the cookie will
    | be regarded as available. Typically, this will be the root path of
    | your application, but you're free to change this when necessary.
    |
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | This value determines the domain and subdomains the session cookie is
    | available to. By default, the cookie will be available to the root
    | domain without subdomains. Typically, this shouldn't be changed.
    |
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    |
    | By setting this option to true, session cookies will only be sent back
    | to the server if the browser has a HTTPS connection. This will keep
    | the cookie from being sent to you when it can't be done securely.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie and the cookie will only be accessible through
    | the HTTP protocol. It's unlikely you should disable this option.
    |
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines how your cookies behave when cross-site requests
    | take place, and can be used to mitigate CSRF attacks. By default, we
    | will set this value to "lax" to permit secure cross-site requests.
    |
    | See: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value
    |
    | Supported: "lax", "strict", "none", null
    |
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Partitioned Cookies
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will tie the cookie to the top-level site for
    | a cross-site context. Partitioned cookies are accepted by the browser
    | when flagged "secure" and the Same-Site attribute is set to "none".
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Serialization
    |--------------------------------------------------------------------------
    |
    | This value controls the serialization strategy for session data, which
    | is JSON by default. Setting this to "php" allows the storage of PHP
    | objects in the session but can make an application vulnerable to
    | "gadget chain" serialization attacks if the APP_KEY is leaked.
    |
    | Supported: "json", "php"
    |
    */

    'serialization' => 'json',

];

```
---

## database/factories/BukuFactory.php
```php
<?php

namespace Database\Factories;

use App\Models\Rak;
use Illuminate\Database\Eloquent\Factories\Factory;

class BukuFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'judul' => fake()->sentence(3),
            'cover' => fake()->word(),
            'penulis' => fake()->name(),
            'penerbit' => fake()->company(),
            'isbn' => fake()->unique()->isbn13(),
            'barcode' => fake()->unique()->ean13(),
            'rak_id' => Rak::factory(),
            'harga_ganti' => fake()->randomFloat(2, 0, 500000),
            'stok' => fake()->numberBetween(0, 20),
            'deskripsi' => fake()->text(),
        ];
    }
}

```
---

## database/factories/DendaFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\TipeDenda;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DendaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $statusLunas = fake()->boolean();

        return [
            'peminjaman_id' => Peminjaman::factory(),
            'user_id' => User::factory(),
            'tipe' => fake()->randomElement(TipeDenda::cases()),
            'nominal' => fake()->randomFloat(2, 5000, 500000),
            'status_lunas' => $statusLunas,
            'tanggal_lunas' => $statusLunas ? fake()->dateTime() : null,
            'keterangan' => fake()->text(),
        ];
    }
}

```
---

## database/factories/KategoriFactory.php
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class KategoriFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->word(),
            'deskripsi' => fake()->text(),
        ];
    }
}

```
---

## database/factories/KunjunganFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\SourceKunjungan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KunjunganFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tanggal' => fake()->date(),
            'jam_tap' => fake()->time(),
            'source' => fake()->randomElement(SourceKunjungan::cases()),
        ];
    }
}

```
---

## database/factories/LevelBadgeFactory.php
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LevelBadgeFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // TODO: GAP-SPEC - min_point dijamin < max_point (asumsi logis; sebelumnya di-random independen dan bisa terbalik)
        $min = fake()->numberBetween(0, 5000);

        return [
            'nama_badge' => fake()->word(),
            'min_point' => $min,
            'max_point' => $min + fake()->numberBetween(100, 5000),
            'icon' => fake()->word(),
            'urutan' => fake()->numberBetween(0, 10),
        ];
    }
}

```
---

## database/factories/PeminjamanFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\StatusPeminjaman;
use App\Models\Buku;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeminjamanFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'transaksi_id' => Transaksi::factory(),
            'user_id' => User::factory(),
            'buku_id' => Buku::factory(),
            'tanggal_pinjam' => fake()->date(),
            'tanggal_jatuh_tempo' => fake()->date(),
            'status' => fake()->randomElement(StatusPeminjaman::cases()),
            'diproses_oleh' => User::factory(),
        ];
    }
}

```
---

## database/factories/PengembalianFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\KondisiBuku;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PengembalianFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'peminjaman_id' => Peminjaman::factory(),
            'tanggal_kembali' => fake()->date(),
            'kondisi' => fake()->randomElement(KondisiBuku::cases()),
            'catatan' => fake()->text(),
            'diproses_oleh' => User::factory(),
        ];
    }
}

```
---

## database/factories/PointFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\EventTypePoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_type' => fake()->randomElement(EventTypePoint::cases()),
            'nilai' => fake()->numberBetween(-100, 100),
            'ref_type' => fake()->randomElement(['peminjaman', 'pengembalian', 'kunjungan']),
            'ref_id' => fake()->uuid(),
            'keterangan' => fake()->word(),
        ];
    }
}

```
---

## database/factories/PunishmentFactory.php
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PunishmentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->word(),
            'deskripsi' => fake()->text(),
            'threshold_point_minus' => fake()->numberBetween(-10000, 10000),
            'durasi_suspend_hari' => fake()->numberBetween(-10000, 10000),
            'aktif' => fake()->boolean(),
        ];
    }
}

```
---

## database/factories/PunishmentLogFactory.php
```php
<?php

namespace Database\Factories;

use App\Models\Punishment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PunishmentLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'punishment_id' => Punishment::factory(),
            'tanggal_diterapkan' => fake()->dateTime(),
            // TODO: GAP-SPEC - null jika punishment masih aktif/belum berakhir (asumsi logis)
            'tanggal_berakhir' => fake()->boolean(70) ? fake()->dateTime() : null,
        ];
    }
}

```
---

## database/factories/RakFactory.php
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RakFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->word(),
            'lokasi' => fake()->word(),
        ];
    }
}

```
---

## database/factories/RewardFactory.php
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RewardFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->word(),
            'deskripsi' => fake()->text(),
            'threshold_point' => fake()->numberBetween(-10000, 10000),
            'aktif' => fake()->boolean(),
        ];
    }
}

```
---

## database/factories/RewardLogFactory.php
```php
<?php

namespace Database\Factories;

use App\Models\Reward;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RewardLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reward_id' => Reward::factory(),
            'tanggal_didapat' => fake()->dateTime(),
        ];
    }
}

```
---

## database/factories/SettingFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\GroupSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'value' => fake()->text(),
            'group' => fake()->randomElement(GroupSetting::cases()),
            'keterangan' => fake()->word(),
        ];
    }
}

```
---

## database/factories/TransaksiFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\JenisTransaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaksiFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'jenis' => fake()->randomElement(JenisTransaksi::cases()),
            'diproses_oleh' => User::factory(),
            'tanggal' => fake()->dateTime(),
            'keterangan' => fake()->text(),
        ];
    }
}

```
---

## database/factories/UserFactory.php
```php
<?php

namespace Database\Factories;

use App\Enums\RoleUser;
use App\Models\LevelBadge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'avatar' => fake()->word(),
            'nama' => fake()->name(),
            'role' => fake()->randomElement(RoleUser::cases()),
            'nisn' => fake()->unique()->numerify('NISN######'),
            'nip' => fake()->unique()->numerify('NIP##########'),
            'kelas' => fake()->word(),
            'jabatan' => fake()->word(),
            'no_telepon' => fake()->unique()->numerify('628##########'),
            'no_kartu_rfid' => fake()->unique()->numerify('########'),
            'password' => Hash::make('password'),
            'status_suspend' => fake()->boolean(),
            'akumulasi_point' => fake()->numberBetween(-10000, 10000),
            'level_badge_id' => LevelBadge::factory(),
        ];
    }
}

```
---

## database/migrations/0001_01_01_000001_create_cache_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->bigInteger('expiration')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};

```
---

## database/migrations/0001_01_01_000002_create_jobs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedSmallInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('connection');
            $table->string('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();

            $table->index(['connection', 'queue', 'failed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};

```
---

## database/migrations/2026_07_29_180455_create_users_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('avatar')->nullable();
            $table->string('nama');
            $table->enum('role', ['siswa', 'pegawai', 'pustakawan', 'admin'])->default('siswa');
            $table->string('nis')->nullable()->unique();
            $table->string('nip')->nullable()->unique();
            $table->string('kelas')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('no_telepon')->unique();
            $table->string('no_kartu_rfid')->nullable()->unique();
            // Nullable: user yang hanya pernah login via OTP WhatsApp tidak wajib punya password.
            $table->string('password')->nullable();
            $table->boolean('status_suspend')->default(false);
            $table->integer('akumulasi_point')->default(0);
            // FK ke level_badges ditambahkan di migration terpisah (lihat add_level_badge_fk_to_users_table)
            // karena level_badges dibuat belakangan dalam urutan file.
            $table->uuid('level_badge_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

```
---

## database/migrations/2026_07_29_180456_create_kategoris_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategoris', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategoris');
    }
};

```
---

## database/migrations/2026_07_29_180457_create_raks_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->string('lokasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raks');
    }
};

```
---

## database/migrations/2026_07_29_180458_create_bukus_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bukus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('judul');
            $table->string('cover')->nullable();
            $table->string('penulis')->nullable();
            $table->string('penerbit')->nullable();
            $table->string('isbn')->nullable()->unique();
            $table->string('barcode')->unique();
            $table->foreignUuid('rak_id')->nullable()->constrained('raks');
            $table->decimal('harga_ganti', 10, 2)->default(0);
            $table->integer('stok')->default(1);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukus');
    }
};

```
---

## database/migrations/2026_07_29_180459_create_transaksis_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('jenis', ['peminjaman', 'kunjungan', 'pembayaran_denda'])->default('peminjaman');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users');
            $table->dateTime('tanggal');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};

```
---

## database/migrations/2026_07_29_180500_create_peminjamans_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('peminjamans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaksi_id')->constrained('transaksis');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('buku_id')->constrained('bukus');
            $table->date('tanggal_pinjam');
            $table->date('tanggal_jatuh_tempo');
            $table->enum('status', ['aktif', 'terlambat', 'selesai', 'hilang'])->default('aktif');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjamans');
    }
};

```
---

## database/migrations/2026_07_29_180501_create_pengembalians_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengembalians', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peminjaman_id')->constrained('peminjamans');
            $table->date('tanggal_kembali');
            $table->enum('kondisi', ['baik', 'rusak', 'hilang'])->default('baik');
            $table->text('catatan')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengembalians');
    }
};

```
---

## database/migrations/2026_07_29_180502_create_dendas_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dendas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('peminjaman_id')->constrained('peminjamans');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('tipe', ['keterlambatan', 'kerusakan', 'kehilangan']);
            $table->decimal('nominal', 10, 2);
            $table->boolean('status_lunas')->default(false);
            $table->dateTime('tanggal_lunas')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dendas');
    }
};

```
---

## database/migrations/2026_07_29_180503_create_points_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('event_type', ['kunjungan', 'peminjaman', 'pengembalian', 'kerusakan', 'kehilangan']);
            $table->integer('nilai');
            // ref_type/ref_id: polymorphic manual, BUKAN Eloquent morph — lihat PointService
            $table->string('ref_type')->nullable();
            $table->uuid('ref_id')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points');
    }
};

```
---

## database/migrations/2026_07_29_180504_create_level_badges_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_badge');
            $table->integer('min_point');
            $table->integer('max_point')->nullable();
            $table->string('icon')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_badges');
    }
};

```
---

## database/migrations/2026_07_29_180505_create_rewards_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->integer('threshold_point');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};

```
---

## database/migrations/2026_07_29_180506_create_reward_logs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('reward_id')->constrained('rewards');
            $table->dateTime('tanggal_didapat');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_logs');
    }
};

```
---

## database/migrations/2026_07_29_180507_create_punishments_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('punishments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->integer('threshold_point_minus');
            $table->integer('durasi_suspend_hari')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punishments');
    }
};

```
---

## database/migrations/2026_07_29_180508_create_punishment_logs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('punishment_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('punishment_id')->constrained('punishments');
            $table->dateTime('tanggal_diterapkan');
            $table->dateTime('tanggal_berakhir')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punishment_logs');
    }
};

```
---

## database/migrations/2026_07_29_180509_create_kunjungans_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kunjungans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->date('tanggal');
            $table->time('jam_tap');
            $table->enum('source', ['rfid', 'manual'])->default('rfid');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungans');
    }
};

```
---

## database/migrations/2026_07_29_180510_create_settings_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('group', ['peminjaman', 'point', 'notifikasi', 'denda', 'device', 'whatsapp'])->default('peminjaman');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

```
---

## database/migrations/2026_07_29_180511_create_buku_kategori_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buku_kategori', function (Blueprint $table) {
            $table->foreignUuid('buku_id')->constrained('bukus')->cascadeOnDelete();
            $table->foreignUuid('kategori_id')->constrained('kategoris')->cascadeOnDelete();
            $table->primary(['buku_id', 'kategori_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buku_kategori');
    }
};

```
---

## database/migrations/2026_07_29_180512_create_kategori_rak_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_rak', function (Blueprint $table) {
            $table->foreignUuid('kategori_id')->constrained('kategoris')->cascadeOnDelete();
            $table->foreignUuid('rak_id')->constrained('raks')->cascadeOnDelete();
            $table->primary(['kategori_id', 'rak_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_rak');
    }
};

```
---

## database/migrations/2026_07_29_181943_add_level_badge_fk_to_users_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('level_badge_id')->references('id')->on('level_badges');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['level_badge_id']);
        });
    }
};

```
---

## database/migrations/2026_07_30_000001_add_unique_user_tanggal_to_kunjungans_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            // Lapisan kedua di DB selain validasi unik-per-hari di device (lihat Logic
            // Module bagian 6). SoftDeletes tidak diikutsertakan di index ini secara
            // sengaja - lihat TODO: GAP-SPEC di bawah.
            $table->unique(['user_id', 'tanggal'], 'kunjungans_user_tanggal_unique');
        });
    }

    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropUnique('kunjungans_user_tanggal_unique');
        });
    }
};

```
---

## database/migrations/2026_07_30_000002_fix_unique_kunjungan_softdelete_aware.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MariaDB mewajibkan index yang meng-cover kolom FK (user_id) selalu ada.
        // Index unique lama adalah satu-satunya index yang mencakup user_id, jadi
        // tambahkan index biasa dulu untuk user_id sebelum index lama di-drop,
        // supaya FK constraint tetap punya index pendukung.
        Schema::table('kunjungans', function ($table) {
            $table->index('user_id', 'kunjungans_user_id_index');
        });

        Schema::table('kunjungans', function ($table) {
            $table->dropUnique('kunjungans_user_tanggal_unique');
        });

        // Generated column: bernilai 'user_id-tanggal' HANYA jika baris aktif
        // (deleted_at IS NULL), NULL jika sudah di-soft-delete. MariaDB
        // memperbolehkan banyak NULL pada unique index, sehingga baris yang
        // sudah di-soft-delete tidak lagi memblokir insert baru dengan
        // kombinasi user_id+tanggal yang sama.
        // Verified: MariaDB 11.8.6 mendukung generated column STORED + unique index.
        DB::statement("
            ALTER TABLE kunjungans
            ADD COLUMN unik_aktif VARCHAR(300)
                GENERATED ALWAYS AS (
                    CASE WHEN deleted_at IS NULL
                        THEN CONCAT(user_id, '-', tanggal)
                        ELSE NULL
                    END
                ) STORED
        ");

        DB::statement("
            ALTER TABLE kunjungans
            ADD UNIQUE INDEX kunjungans_unik_aktif_unique (unik_aktif)
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE kunjungans DROP INDEX kunjungans_unik_aktif_unique');
        DB::statement('ALTER TABLE kunjungans DROP COLUMN unik_aktif');

        Schema::table('kunjungans', function ($table) {
            $table->unique(['user_id', 'tanggal'], 'kunjungans_user_tanggal_unique');
        });

        Schema::table('kunjungans', function ($table) {
            $table->dropIndex('kunjungans_user_id_index');
        });
    }
};

```
---

## database/migrations/2026_07_30_000003_rename_nis_to_nisn_in_users_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('nis', 'nisn');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('nisn', 'nis');
        });
    }
};

```
---

## database/migrations/2026_07_30_000004_create_device_logs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // device_id dari firmware: MAC-based (ESP32_XXXX) atau nama custom jika diisi saat provisioning.
            $table->string('device_id')->unique();
            $table->string('device_name')->nullable();
            $table->string('firmware_version')->nullable();
            $table->unsignedBigInteger('uptime_sec')->default(0);
            $table->unsignedBigInteger('heap_free')->default(0);
            $table->unsignedInteger('pending_records')->default(0);
            $table->unsignedInteger('scan_today')->default(0);
            $table->integer('rssi')->default(0);
            $table->boolean('sd_ok')->default(false);
            $table->unsignedInteger('rfid_db_entries')->default(0);
            $table->boolean('online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_logs');
    }
};

```
---

## database/migrations/2026_07_30_000005_create_firmware_releases_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firmware_releases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('version')->unique(); // format semver: x.y.z, dibandingkan dengan compareFirmwareVersion() di firmware
            $table->string('url'); // URL binary .bin, wajib https, wajib bisa diverifikasi lewat X-API-KEY yang sama
            $table->string('md5')->nullable();
            $table->boolean('aktif')->default(true);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmware_releases');
    }
};

```
---

## database/seeders/DatabaseSeeder.php
```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SettingSeeder::class);

        User::factory()->create([
            'nama' => 'Admin Perpustakaan',
            'role' => 'admin',
            'no_telepon' => '628123456789',
            'password' => Hash::make('password'),
        ]);
    }
}

```
---

## database/seeders/SettingSeeder.php
```php
<?php

namespace Database\Seeders;

use App\Enums\GroupSetting;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Baseline Setting agar aplikasi tidak diam-diam berjalan dengan default
 * hardcode di kode (Setting::get($key, $default)). Nilai berkategori
 * "bisnis" (bukan teknis/device) ditandai TODO: ASUMSI - wajib direview
 * Admin lewat panel sebelum dianggap final, terutama nilai Point yang
 * menentukan kecepatan naik Badge dan pemicu Punishment.
 *
 * SENGAJA TIDAK menyeed wa_template_* - template_code terkait belum dibuat
 * di panel WhatsApp Gateway (dok kontrak API §4.2). Sampai template dibuat
 * manual dan key ini diisi, WhatsappService::kirimEvent() akan skip dengan
 * Log::warning (by design), notifikasi WA tidak terkirim.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // --- Kategori A: teknis/device - konsisten dengan default firmware ESP32 ---
            ['key' => 'rfid_db_ver', 'value' => '0', 'group' => GroupSetting::Device, 'keterangan' => 'Versi daftar kartu RFID aktif, dinaikkan otomatis oleh UserObserver.'],
            ['key' => 'device_sleep_start_hour', 'value' => '18', 'group' => GroupSetting::Device, 'keterangan' => 'Jam mulai device deep sleep (0-23).'],
            ['key' => 'device_sleep_end_hour', 'value' => '5', 'group' => GroupSetting::Device, 'keterangan' => 'Jam device bangun dari deep sleep (0-23).'],
            ['key' => 'device_oled_dim_start_hour', 'value' => '8', 'group' => GroupSetting::Device, 'keterangan' => 'Jam mulai OLED device dimatikan sementara (0-23).'],
            ['key' => 'device_oled_dim_end_hour', 'value' => '12', 'group' => GroupSetting::Device, 'keterangan' => 'Jam OLED device kembali menyala (0-23).'],
            ['key' => 'device_sync_interval_ms', 'value' => '300000', 'group' => GroupSetting::Device, 'keterangan' => 'Interval sinkronisasi data offline device ke server (ms).'],
            ['key' => 'device_ota_check_interval_ms', 'value' => '30000', 'group' => GroupSetting::Device, 'keterangan' => 'Interval device mengecek update firmware (ms).'],

            // --- Kategori B.1: aturan peminjaman & denda ---
            // TODO: ASUMSI - baseline dari default fallback di PeminjamanService, wajib direview Admin.
            ['key' => 'max_peminjaman_aktif', 'value' => '3', 'group' => GroupSetting::Peminjaman, 'keterangan' => 'TODO: ASUMSI - maksimal jumlah Peminjaman berstatus aktif per user.'],
            ['key' => 'lama_peminjaman_hari', 'value' => '7', 'group' => GroupSetting::Peminjaman, 'keterangan' => 'TODO: ASUMSI - masa pinjam dalam hari sejak tanggal_pinjam.'],
            ['key' => 'tarif_denda_per_hari', 'value' => '500', 'group' => GroupSetting::Denda, 'keterangan' => 'TODO: ASUMSI - tarif denda keterlambatan per hari (rupiah).'],
            ['key' => 'persentase_denda_kerusakan', 'value' => '100', 'group' => GroupSetting::Denda, 'keterangan' => 'TODO: ASUMSI - persentase dari Buku.harga_ganti untuk denda kerusakan.'],

            // --- Kategori B.2: nilai Point per event ---
            // TODO: ASUMSI - angka belum ditentukan spec, dipilih sebagai baseline awal
            // supaya sistem Badge/Reward/Punishment tidak mati total (default kode = 0).
            // Kerusakan/Kehilangan sengaja negatif sesuai Logic Module §4.
            ['key' => 'point_kunjungan', 'value' => '1', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point per kunjungan (tap RFID).'],
            ['key' => 'point_peminjaman', 'value' => '2', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point per buku dipinjam.'],
            ['key' => 'point_pengembalian', 'value' => '3', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point per pengembalian kondisi baik/tepat waktu.'],
            ['key' => 'point_kerusakan', 'value' => '-10', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point (negatif) saat buku dikembalikan rusak.'],
            ['key' => 'point_kehilangan', 'value' => '-20', 'group' => GroupSetting::Point, 'keterangan' => 'TODO: ASUMSI - point (negatif) saat buku dilaporkan/berstatus hilang.'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'keterangan' => $setting['keterangan'],
                ]
            );
        }
    }
}

```
---

## bootstrap/app.php
```php
<?php

use App\Http\Middleware\AuthenticateDeviceApiKey;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'device.api.key' => AuthenticateDeviceApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );
    })->create();

```
---

## bootstrap/cache/packages.php
```php
<?php return array (
  'anourvalar/eloquent-serialize' => 
  array (
    'aliases' => 
    array (
      'EloquentSerialize' => 'AnourValar\\EloquentSerialize\\Facades\\EloquentSerializeFacade',
    ),
  ),
  'blade-ui-kit/blade-heroicons' => 
  array (
    'providers' => 
    array (
      0 => 'BladeUI\\Heroicons\\BladeHeroiconsServiceProvider',
    ),
  ),
  'blade-ui-kit/blade-icons' => 
  array (
    'providers' => 
    array (
      0 => 'BladeUI\\Icons\\BladeIconsServiceProvider',
    ),
  ),
  'filament/actions' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Actions\\ActionsServiceProvider',
    ),
  ),
  'filament/filament' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\FilamentServiceProvider',
    ),
  ),
  'filament/forms' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Forms\\FormsServiceProvider',
    ),
  ),
  'filament/infolists' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Infolists\\InfolistsServiceProvider',
    ),
  ),
  'filament/notifications' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Notifications\\NotificationsServiceProvider',
    ),
  ),
  'filament/query-builder' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\QueryBuilder\\QueryBuilderServiceProvider',
    ),
  ),
  'filament/schemas' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Schemas\\SchemasServiceProvider',
    ),
  ),
  'filament/support' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Support\\SupportServiceProvider',
    ),
  ),
  'filament/tables' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Tables\\TablesServiceProvider',
    ),
  ),
  'filament/widgets' => 
  array (
    'providers' => 
    array (
      0 => 'Filament\\Widgets\\WidgetsServiceProvider',
    ),
  ),
  'kirschbaum-development/eloquent-power-joins' => 
  array (
    'providers' => 
    array (
      0 => 'Kirschbaum\\PowerJoins\\PowerJoinsServiceProvider',
    ),
  ),
  'laravel-shift/blueprint' => 
  array (
    'providers' => 
    array (
      0 => 'Blueprint\\BlueprintServiceProvider',
    ),
  ),
  'laravel/pail' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Pail\\PailServiceProvider',
    ),
  ),
  'laravel/pao' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Pao\\Laravel\\ServiceProvider',
    ),
  ),
  'laravel/tinker' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Tinker\\TinkerServiceProvider',
    ),
  ),
  'livewire/livewire' => 
  array (
    'aliases' => 
    array (
      'Livewire' => 'Livewire\\Livewire',
    ),
    'providers' => 
    array (
      0 => 'Livewire\\LivewireServiceProvider',
    ),
  ),
  'nesbot/carbon' => 
  array (
    'providers' => 
    array (
      0 => 'Carbon\\Laravel\\ServiceProvider',
    ),
  ),
  'nunomaduro/collision' => 
  array (
    'providers' => 
    array (
      0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    ),
  ),
  'nunomaduro/termwind' => 
  array (
    'providers' => 
    array (
      0 => 'Termwind\\Laravel\\TermwindServiceProvider',
    ),
  ),
  'ryangjchandler/blade-capture-directive' => 
  array (
    'aliases' => 
    array (
      'BladeCaptureDirective' => 'RyanChandler\\BladeCaptureDirective\\Facades\\BladeCaptureDirective',
    ),
    'providers' => 
    array (
      0 => 'RyanChandler\\BladeCaptureDirective\\BladeCaptureDirectiveServiceProvider',
    ),
  ),
);
```
---

## bootstrap/cache/services.php
```php
<?php return array (
  'providers' => 
  array (
    0 => 'Illuminate\\Auth\\AuthServiceProvider',
    1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    2 => 'Illuminate\\Bus\\BusServiceProvider',
    3 => 'Illuminate\\Cache\\CacheServiceProvider',
    4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
    6 => 'Illuminate\\Cookie\\CookieServiceProvider',
    7 => 'Illuminate\\Database\\DatabaseServiceProvider',
    8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
    9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
    10 => 'Illuminate\\Image\\ImageServiceProvider',
    11 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
    12 => 'Illuminate\\Hashing\\HashServiceProvider',
    13 => 'Illuminate\\Mail\\MailServiceProvider',
    14 => 'Illuminate\\Notifications\\NotificationServiceProvider',
    15 => 'Illuminate\\Pagination\\PaginationServiceProvider',
    16 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
    17 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
    18 => 'Illuminate\\Queue\\QueueServiceProvider',
    19 => 'Illuminate\\Redis\\RedisServiceProvider',
    20 => 'Illuminate\\Session\\SessionServiceProvider',
    21 => 'Illuminate\\Translation\\TranslationServiceProvider',
    22 => 'Illuminate\\Validation\\ValidationServiceProvider',
    23 => 'Illuminate\\View\\ViewServiceProvider',
    24 => 'BladeUI\\Heroicons\\BladeHeroiconsServiceProvider',
    25 => 'BladeUI\\Icons\\BladeIconsServiceProvider',
    26 => 'Filament\\Actions\\ActionsServiceProvider',
    27 => 'Filament\\FilamentServiceProvider',
    28 => 'Filament\\Forms\\FormsServiceProvider',
    29 => 'Filament\\Infolists\\InfolistsServiceProvider',
    30 => 'Filament\\Notifications\\NotificationsServiceProvider',
    31 => 'Filament\\QueryBuilder\\QueryBuilderServiceProvider',
    32 => 'Filament\\Schemas\\SchemasServiceProvider',
    33 => 'Filament\\Support\\SupportServiceProvider',
    34 => 'Filament\\Tables\\TablesServiceProvider',
    35 => 'Filament\\Widgets\\WidgetsServiceProvider',
    36 => 'Kirschbaum\\PowerJoins\\PowerJoinsServiceProvider',
    37 => 'Blueprint\\BlueprintServiceProvider',
    38 => 'Laravel\\Pail\\PailServiceProvider',
    39 => 'Laravel\\Pao\\Laravel\\ServiceProvider',
    40 => 'Laravel\\Tinker\\TinkerServiceProvider',
    41 => 'Livewire\\LivewireServiceProvider',
    42 => 'Carbon\\Laravel\\ServiceProvider',
    43 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    44 => 'Termwind\\Laravel\\TermwindServiceProvider',
    45 => 'RyanChandler\\BladeCaptureDirective\\BladeCaptureDirectiveServiceProvider',
    46 => 'App\\Providers\\AppServiceProvider',
    47 => 'App\\Providers\\Filament\\DashboardPanelProvider',
  ),
  'eager' => 
  array (
    0 => 'Illuminate\\Auth\\AuthServiceProvider',
    1 => 'Illuminate\\Cookie\\CookieServiceProvider',
    2 => 'Illuminate\\Database\\DatabaseServiceProvider',
    3 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
    4 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
    5 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
    6 => 'Illuminate\\Notifications\\NotificationServiceProvider',
    7 => 'Illuminate\\Pagination\\PaginationServiceProvider',
    8 => 'Illuminate\\Session\\SessionServiceProvider',
    9 => 'Illuminate\\View\\ViewServiceProvider',
    10 => 'BladeUI\\Heroicons\\BladeHeroiconsServiceProvider',
    11 => 'BladeUI\\Icons\\BladeIconsServiceProvider',
    12 => 'Filament\\Actions\\ActionsServiceProvider',
    13 => 'Filament\\FilamentServiceProvider',
    14 => 'Filament\\Forms\\FormsServiceProvider',
    15 => 'Filament\\Infolists\\InfolistsServiceProvider',
    16 => 'Filament\\Notifications\\NotificationsServiceProvider',
    17 => 'Filament\\QueryBuilder\\QueryBuilderServiceProvider',
    18 => 'Filament\\Schemas\\SchemasServiceProvider',
    19 => 'Filament\\Support\\SupportServiceProvider',
    20 => 'Filament\\Tables\\TablesServiceProvider',
    21 => 'Filament\\Widgets\\WidgetsServiceProvider',
    22 => 'Kirschbaum\\PowerJoins\\PowerJoinsServiceProvider',
    23 => 'Laravel\\Pail\\PailServiceProvider',
    24 => 'Laravel\\Pao\\Laravel\\ServiceProvider',
    25 => 'Livewire\\LivewireServiceProvider',
    26 => 'Carbon\\Laravel\\ServiceProvider',
    27 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    28 => 'Termwind\\Laravel\\TermwindServiceProvider',
    29 => 'RyanChandler\\BladeCaptureDirective\\BladeCaptureDirectiveServiceProvider',
    30 => 'App\\Providers\\AppServiceProvider',
    31 => 'App\\Providers\\Filament\\DashboardPanelProvider',
  ),
  'deferred' => 
  array (
    'Illuminate\\Broadcasting\\BroadcastManager' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Contracts\\Broadcasting\\Factory' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Contracts\\Broadcasting\\Broadcaster' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Bus\\Dispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Contracts\\Bus\\Dispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Contracts\\Bus\\QueueingDispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Bus\\BatchRepository' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Bus\\DatabaseBatchRepository' => 'Illuminate\\Bus\\BusServiceProvider',
    'cache' => 'Illuminate\\Cache\\CacheServiceProvider',
    'cache.store' => 'Illuminate\\Cache\\CacheServiceProvider',
    'cache.psr6' => 'Illuminate\\Cache\\CacheServiceProvider',
    'memcached.connector' => 'Illuminate\\Cache\\CacheServiceProvider',
    'Illuminate\\Cache\\RateLimiter' => 'Illuminate\\Cache\\CacheServiceProvider',
    'Illuminate\\Foundation\\Console\\AboutCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\ClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\ForgetCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ClearCompiledCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Auth\\Console\\ClearResetsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigShowCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\DbCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\MonitorCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\PruneCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\ShowCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\TableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\WipeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\DownCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnvironmentCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnvironmentDecryptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnvironmentEncryptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Concurrency\\Console\\InvokeSerializedClosureCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\KeyGenerateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\OptimizeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\OptimizeClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\PackageDiscoverCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\PruneStaleTagsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ListFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\FlushFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ForgetFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ListenCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\MonitorCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\PauseCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\PruneBatchesCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\PruneFailedJobsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RestartCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ResumeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RetryCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RetryBatchCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\WorkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ReloadCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\DumpCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Seeds\\SeedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleFinishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleRunCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleClearCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleTestCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleWorkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleInterruptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\SchedulePauseCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleResumeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\ShowModelCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\StorageLinkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\StorageUnlinkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\UpCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ViewCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ViewClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ApiInstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\CacheTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\CastMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ChannelListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ChannelMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ClassMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ComponentMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConsoleMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Routing\\Console\\ControllerMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\DevCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\DevListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\DocsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnumMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventGenerateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ExceptionMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Factories\\FactoryMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\InterfaceMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\JobMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\JobMiddlewareMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\LangPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ListenerMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\MailMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Routing\\Console\\MiddlewareMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ModelMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\NotificationMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Notifications\\Console\\NotificationTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ObserverMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\PolicyMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ProviderMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\FailedTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\TableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\BatchesTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RequestMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ResourceMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RuleMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ScopeMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Seeds\\SeederMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Session\\Console\\SessionTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ServeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\StubPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\TestMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\TraitMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\VendorPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ViewMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migrator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migration.repository' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migration.creator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Migrations\\Migrator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\MigrateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\FreshCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\InstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\RefreshCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\ResetCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\RollbackCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\StatusCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\MigrateMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'composer' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Concurrency\\ConcurrencyManager' => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
    'image' => 'Illuminate\\Image\\ImageServiceProvider',
    'hash' => 'Illuminate\\Hashing\\HashServiceProvider',
    'hash.driver' => 'Illuminate\\Hashing\\HashServiceProvider',
    'mail.manager' => 'Illuminate\\Mail\\MailServiceProvider',
    'mailer' => 'Illuminate\\Mail\\MailServiceProvider',
    'Illuminate\\Mail\\Markdown' => 'Illuminate\\Mail\\MailServiceProvider',
    'auth.password' => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
    'auth.password.broker' => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
    'Illuminate\\Contracts\\Pipeline\\Hub' => 'Illuminate\\Pipeline\\PipelineServiceProvider',
    'pipeline' => 'Illuminate\\Pipeline\\PipelineServiceProvider',
    'queue' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.connection' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.failer' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.listener' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.routes' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.worker' => 'Illuminate\\Queue\\QueueServiceProvider',
    'redis' => 'Illuminate\\Redis\\RedisServiceProvider',
    'redis.connection' => 'Illuminate\\Redis\\RedisServiceProvider',
    'translator' => 'Illuminate\\Translation\\TranslationServiceProvider',
    'translation.loader' => 'Illuminate\\Translation\\TranslationServiceProvider',
    'validator' => 'Illuminate\\Validation\\ValidationServiceProvider',
    'validation.presence' => 'Illuminate\\Validation\\ValidationServiceProvider',
    'Illuminate\\Contracts\\Validation\\UncompromisedVerifier' => 'Illuminate\\Validation\\ValidationServiceProvider',
    'command.blueprint.build' => 'Blueprint\\BlueprintServiceProvider',
    'command.blueprint.erase' => 'Blueprint\\BlueprintServiceProvider',
    'command.blueprint.trace' => 'Blueprint\\BlueprintServiceProvider',
    'command.blueprint.new' => 'Blueprint\\BlueprintServiceProvider',
    'command.blueprint.init' => 'Blueprint\\BlueprintServiceProvider',
    'Blueprint\\Blueprint' => 'Blueprint\\BlueprintServiceProvider',
    'command.tinker' => 'Laravel\\Tinker\\TinkerServiceProvider',
  ),
  'when' => 
  array (
    'Illuminate\\Broadcasting\\BroadcastServiceProvider' => 
    array (
    ),
    'Illuminate\\Bus\\BusServiceProvider' => 
    array (
    ),
    'Illuminate\\Cache\\CacheServiceProvider' => 
    array (
    ),
    'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider' => 
    array (
    ),
    'Illuminate\\Concurrency\\ConcurrencyServiceProvider' => 
    array (
    ),
    'Illuminate\\Image\\ImageServiceProvider' => 
    array (
    ),
    'Illuminate\\Hashing\\HashServiceProvider' => 
    array (
    ),
    'Illuminate\\Mail\\MailServiceProvider' => 
    array (
    ),
    'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider' => 
    array (
    ),
    'Illuminate\\Pipeline\\PipelineServiceProvider' => 
    array (
    ),
    'Illuminate\\Queue\\QueueServiceProvider' => 
    array (
    ),
    'Illuminate\\Redis\\RedisServiceProvider' => 
    array (
    ),
    'Illuminate\\Translation\\TranslationServiceProvider' => 
    array (
    ),
    'Illuminate\\Validation\\ValidationServiceProvider' => 
    array (
    ),
    'Blueprint\\BlueprintServiceProvider' => 
    array (
    ),
    'Laravel\\Tinker\\TinkerServiceProvider' => 
    array (
    ),
  ),
);
```
---

## bootstrap/providers.php
```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\DashboardPanelProvider::class,
];

```
---

## resources/css/app.css
```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';
}

```
---

## resources/js/app.js
```js
//

```
---

## resources/views/welcome.blade.php
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @fonts

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */ @layer properties{@supports (((-webkit-hyphens:none)) and (not (margin-trim:inline))) or ((-moz-orient:inline) and (not (color:rgb(from red r g b)))){*,:before,:after,::backdrop{--tw-translate-x:0;--tw-translate-y:0;--tw-translate-z:0;--tw-rotate-x:initial;--tw-rotate-y:initial;--tw-rotate-z:initial;--tw-skew-x:initial;--tw-skew-y:initial;--tw-space-x-reverse:0;--tw-border-style:solid;--tw-leading:initial;--tw-font-weight:initial;--tw-tracking:initial;--tw-shadow:0 0 #0000;--tw-shadow-color:initial;--tw-shadow-alpha:100%;--tw-inset-shadow:0 0 #0000;--tw-inset-shadow-color:initial;--tw-inset-shadow-alpha:100%;--tw-ring-color:initial;--tw-ring-shadow:0 0 #0000;--tw-inset-ring-color:initial;--tw-inset-ring-shadow:0 0 #0000;--tw-ring-inset:initial;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-offset-shadow:0 0 #0000;--tw-blur:initial;--tw-brightness:initial;--tw-contrast:initial;--tw-grayscale:initial;--tw-hue-rotate:initial;--tw-invert:initial;--tw-opacity:initial;--tw-saturate:initial;--tw-sepia:initial;--tw-drop-shadow:initial;--tw-drop-shadow-color:initial;--tw-drop-shadow-alpha:100%;--tw-drop-shadow-size:initial;--tw-duration:initial;--tw-ease:initial;--tw-content:""}}}@layer theme{:root,:host{--font-sans:"Instrument Sans", ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";--font-serif:ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;--font-mono:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;--color-red-50:oklch(97.1% .013 17.38);--color-red-100:oklch(93.6% .032 17.717);--color-red-200:oklch(88.5% .062 18.334);--color-red-300:oklch(80.8% .114 19.571);--color-red-400:oklch(70.4% .191 22.216);--color-red-500:oklch(63.7% .237 25.331);--color-red-600:oklch(57.7% .245 27.325);--color-red-700:oklch(50.5% .213 27.518);--color-red-800:oklch(44.4% .177 26.899);--color-red-900:oklch(39.6% .141 25.723);--color-red-950:oklch(25.8% .092 26.042);--color-orange-50:oklch(98% .016 73.684);--color-orange-100:oklch(95.4% .038 75.164);--color-orange-200:oklch(90.1% .076 70.697);--color-orange-300:oklch(83.7% .128 66.29);--color-orange-400:oklch(75% .183 55.934);--color-orange-500:oklch(70.5% .213 47.604);--color-orange-600:oklch(64.6% .222 41.116);--color-orange-700:oklch(55.3% .195 38.402);--color-orange-800:oklch(47% .157 37.304);--color-orange-900:oklch(40.8% .123 38.172);--color-orange-950:oklch(26.6% .079 36.259);--color-amber-50:oklch(98.7% .022 95.277);--color-amber-100:oklch(96.2% .059 95.617);--color-amber-200:oklch(92.4% .12 95.746);--color-amber-300:oklch(87.9% .169 91.605);--color-amber-400:oklch(82.8% .189 84.429);--color-amber-500:oklch(76.9% .188 70.08);--color-amber-600:oklch(66.6% .179 58.318);--color-amber-700:oklch(55.5% .163 48.998);--color-amber-800:oklch(47.3% .137 46.201);--color-amber-900:oklch(41.4% .112 45.904);--color-amber-950:oklch(27.9% .077 45.635);--color-yellow-50:oklch(98.7% .026 102.212);--color-yellow-100:oklch(97.3% .071 103.193);--color-yellow-200:oklch(94.5% .129 101.54);--color-yellow-300:oklch(90.5% .182 98.111);--color-yellow-400:oklch(85.2% .199 91.936);--color-yellow-500:oklch(79.5% .184 86.047);--color-yellow-600:oklch(68.1% .162 75.834);--color-yellow-700:oklch(55.4% .135 66.442);--color-yellow-800:oklch(47.6% .114 61.907);--color-yellow-900:oklch(42.1% .095 57.708);--color-yellow-950:oklch(28.6% .066 53.813);--color-lime-50:oklch(98.6% .031 120.757);--color-lime-100:oklch(96.7% .067 122.328);--color-lime-200:oklch(93.8% .127 124.321);--color-lime-300:oklch(89.7% .196 126.665);--color-lime-400:oklch(84.1% .238 128.85);--color-lime-500:oklch(76.8% .233 130.85);--color-lime-600:oklch(64.8% .2 131.684);--color-lime-700:oklch(53.2% .157 131.589);--color-lime-800:oklch(45.3% .124 130.933);--color-lime-900:oklch(40.5% .101 131.063);--color-lime-950:oklch(27.4% .072 132.109);--color-green-50:oklch(98.2% .018 155.826);--color-green-100:oklch(96.2% .044 156.743);--color-green-200:oklch(92.5% .084 155.995);--color-green-300:oklch(87.1% .15 154.449);--color-green-400:oklch(79.2% .209 151.711);--color-green-500:oklch(72.3% .219 149.579);--color-green-600:oklch(62.7% .194 149.214);--color-green-700:oklch(52.7% .154 150.069);--color-green-800:oklch(44.8% .119 151.328);--color-green-900:oklch(39.3% .095 152.535);--color-green-950:oklch(26.6% .065 152.934);--color-emerald-50:oklch(97.9% .021 166.113);--color-emerald-100:oklch(95% .052 163.051);--color-emerald-200:oklch(90.5% .093 164.15);--color-emerald-300:oklch(84.5% .143 164.978);--color-emerald-400:oklch(76.5% .177 163.223);--color-emerald-500:oklch(69.6% .17 162.48);--color-emerald-600:oklch(59.6% .145 163.225);--color-emerald-700:oklch(50.8% .118 165.612);--color-emerald-800:oklch(43.2% .095 166.913);--color-emerald-900:oklch(37.8% .077 168.94);--color-emerald-950:oklch(26.2% .051 172.552);--color-teal-50:oklch(98.4% .014 180.72);--color-teal-100:oklch(95.3% .051 180.801);--color-teal-200:oklch(91% .096 180.426);--color-teal-300:oklch(85.5% .138 181.071);--color-teal-400:oklch(77.7% .152 181.912);--color-teal-500:oklch(70.4% .14 182.503);--color-teal-600:oklch(60% .118 184.704);--color-teal-700:oklch(51.1% .096 186.391);--color-teal-800:oklch(43.7% .078 188.216);--color-teal-900:oklch(38.6% .063 188.416);--color-teal-950:oklch(27.7% .046 192.524);--color-cyan-50:oklch(98.4% .019 200.873);--color-cyan-100:oklch(95.6% .045 203.388);--color-cyan-200:oklch(91.7% .08 205.041);--color-cyan-300:oklch(86.5% .127 207.078);--color-cyan-400:oklch(78.9% .154 211.53);--color-cyan-500:oklch(71.5% .143 215.221);--color-cyan-600:oklch(60.9% .126 221.723);--color-cyan-700:oklch(52% .105 223.128);--color-cyan-800:oklch(45% .085 224.283);--color-cyan-900:oklch(39.8% .07 227.392);--color-cyan-950:oklch(30.2% .056 229.695);--color-sky-50:oklch(97.7% .013 236.62);--color-sky-100:oklch(95.1% .026 236.824);--color-sky-200:oklch(90.1% .058 230.902);--color-sky-300:oklch(82.8% .111 230.318);--color-sky-400:oklch(74.6% .16 232.661);--color-sky-500:oklch(68.5% .169 237.323);--color-sky-600:oklch(58.8% .158 241.966);--color-sky-700:oklch(50% .134 242.749);--color-sky-800:oklch(44.3% .11 240.79);--color-sky-900:oklch(39.1% .09 240.876);--color-sky-950:oklch(29.3% .066 243.157);--color-blue-50:oklch(97% .014 254.604);--color-blue-100:oklch(93.2% .032 255.585);--color-blue-200:oklch(88.2% .059 254.128);--color-blue-300:oklch(80.9% .105 251.813);--color-blue-400:oklch(70.7% .165 254.624);--color-blue-500:oklch(62.3% .214 259.815);--color-blue-600:oklch(54.6% .245 262.881);--color-blue-700:oklch(48.8% .243 264.376);--color-blue-800:oklch(42.4% .199 265.638);--color-blue-900:oklch(37.9% .146 265.522);--color-blue-950:oklch(28.2% .091 267.935);--color-indigo-50:oklch(96.2% .018 272.314);--color-indigo-100:oklch(93% .034 272.788);--color-indigo-200:oklch(87% .065 274.039);--color-indigo-300:oklch(78.5% .115 274.713);--color-indigo-400:oklch(67.3% .182 276.935);--color-indigo-500:oklch(58.5% .233 277.117);--color-indigo-600:oklch(51.1% .262 276.966);--color-indigo-700:oklch(45.7% .24 277.023);--color-indigo-800:oklch(39.8% .195 277.366);--color-indigo-900:oklch(35.9% .144 278.697);--color-indigo-950:oklch(25.7% .09 281.288);--color-violet-50:oklch(96.9% .016 293.756);--color-violet-100:oklch(94.3% .029 294.588);--color-violet-200:oklch(89.4% .057 293.283);--color-violet-300:oklch(81.1% .111 293.571);--color-violet-400:oklch(70.2% .183 293.541);--color-violet-500:oklch(60.6% .25 292.717);--color-violet-600:oklch(54.1% .281 293.009);--color-violet-700:oklch(49.1% .27 292.581);--color-violet-800:oklch(43.2% .232 292.759);--color-violet-900:oklch(38% .189 293.745);--color-violet-950:oklch(28.3% .141 291.089);--color-purple-50:oklch(97.7% .014 308.299);--color-purple-100:oklch(94.6% .033 307.174);--color-purple-200:oklch(90.2% .063 306.703);--color-purple-300:oklch(82.7% .119 306.383);--color-purple-400:oklch(71.4% .203 305.504);--color-purple-500:oklch(62.7% .265 303.9);--color-purple-600:oklch(55.8% .288 302.321);--color-purple-700:oklch(49.6% .265 301.924);--color-purple-800:oklch(43.8% .218 303.724);--color-purple-900:oklch(38.1% .176 304.987);--color-purple-950:oklch(29.1% .149 302.717);--color-fuchsia-50:oklch(97.7% .017 320.058);--color-fuchsia-100:oklch(95.2% .037 318.852);--color-fuchsia-200:oklch(90.3% .076 319.62);--color-fuchsia-300:oklch(83.3% .145 321.434);--color-fuchsia-400:oklch(74% .238 322.16);--color-fuchsia-500:oklch(66.7% .295 322.15);--color-fuchsia-600:oklch(59.1% .293 322.896);--color-fuchsia-700:oklch(51.8% .253 323.949);--color-fuchsia-800:oklch(45.2% .211 324.591);--color-fuchsia-900:oklch(40.1% .17 325.612);--color-fuchsia-950:oklch(29.3% .136 325.661);--color-pink-50:oklch(97.1% .014 343.198);--color-pink-100:oklch(94.8% .028 342.258);--color-pink-200:oklch(89.9% .061 343.231);--color-pink-300:oklch(82.3% .12 346.018);--color-pink-400:oklch(71.8% .202 349.761);--color-pink-500:oklch(65.6% .241 354.308);--color-pink-600:oklch(59.2% .249 .584);--color-pink-700:oklch(52.5% .223 3.958);--color-pink-800:oklch(45.9% .187 3.815);--color-pink-900:oklch(40.8% .153 2.432);--color-pink-950:oklch(28.4% .109 3.907);--color-rose-50:oklch(96.9% .015 12.422);--color-rose-100:oklch(94.1% .03 12.58);--color-rose-200:oklch(89.2% .058 10.001);--color-rose-300:oklch(81% .117 11.638);--color-rose-400:oklch(71.2% .194 13.428);--color-rose-500:oklch(64.5% .246 16.439);--color-rose-600:oklch(58.6% .253 17.585);--color-rose-700:oklch(51.4% .222 16.935);--color-rose-800:oklch(45.5% .188 13.697);--color-rose-900:oklch(41% .159 10.272);--color-rose-950:oklch(27.1% .105 12.094);--color-slate-50:oklch(98.4% .003 247.858);--color-slate-100:oklch(96.8% .007 247.896);--color-slate-200:oklch(92.9% .013 255.508);--color-slate-300:oklch(86.9% .022 252.894);--color-slate-400:oklch(70.4% .04 256.788);--color-slate-500:oklch(55.4% .046 257.417);--color-slate-600:oklch(44.6% .043 257.281);--color-slate-700:oklch(37.2% .044 257.287);--color-slate-800:oklch(27.9% .041 260.031);--color-slate-900:oklch(20.8% .042 265.755);--color-slate-950:oklch(12.9% .042 264.695);--color-gray-50:oklch(98.5% .002 247.839);--color-gray-100:oklch(96.7% .003 264.542);--color-gray-200:oklch(92.8% .006 264.531);--color-gray-300:oklch(87.2% .01 258.338);--color-gray-400:oklch(70.7% .022 261.325);--color-gray-500:oklch(55.1% .027 264.364);--color-gray-600:oklch(44.6% .03 256.802);--color-gray-700:oklch(37.3% .034 259.733);--color-gray-800:oklch(27.8% .033 256.848);--color-gray-900:oklch(21% .034 264.665);--color-gray-950:oklch(13% .028 261.692);--color-zinc-50:oklch(98.5% 0 0);--color-zinc-100:oklch(96.7% .001 286.375);--color-zinc-200:oklch(92% .004 286.32);--color-zinc-300:oklch(87.1% .006 286.286);--color-zinc-400:oklch(70.5% .015 286.067);--color-zinc-500:oklch(55.2% .016 285.938);--color-zinc-600:oklch(44.2% .017 285.786);--color-zinc-700:oklch(37% .013 285.805);--color-zinc-800:oklch(27.4% .006 286.033);--color-zinc-900:oklch(21% .006 285.885);--color-zinc-950:oklch(14.1% .005 285.823);--color-neutral-50:oklch(98.5% 0 0);--color-neutral-100:oklch(97% 0 0);--color-neutral-200:oklch(92.2% 0 0);--color-neutral-300:oklch(87% 0 0);--color-neutral-400:oklch(70.8% 0 0);--color-neutral-500:oklch(55.6% 0 0);--color-neutral-600:oklch(43.9% 0 0);--color-neutral-700:oklch(37.1% 0 0);--color-neutral-800:oklch(26.9% 0 0);--color-neutral-900:oklch(20.5% 0 0);--color-neutral-950:oklch(14.5% 0 0);--color-stone-50:oklch(98.5% .001 106.423);--color-stone-100:oklch(97% .001 106.424);--color-stone-200:oklch(92.3% .003 48.717);--color-stone-300:oklch(86.9% .005 56.366);--color-stone-400:oklch(70.9% .01 56.259);--color-stone-500:oklch(55.3% .013 58.071);--color-stone-600:oklch(44.4% .011 73.639);--color-stone-700:oklch(37.4% .01 67.558);--color-stone-800:oklch(26.8% .007 34.298);--color-stone-900:oklch(21.6% .006 56.043);--color-stone-950:oklch(14.7% .004 49.25);--color-black:#000;--color-white:#fff;--spacing:.25rem;--breakpoint-sm:40rem;--breakpoint-md:48rem;--breakpoint-lg:64rem;--breakpoint-xl:80rem;--breakpoint-2xl:96rem;--container-3xs:16rem;--container-2xs:18rem;--container-xs:20rem;--container-sm:24rem;--container-md:28rem;--container-lg:32rem;--container-xl:36rem;--container-2xl:42rem;--container-3xl:48rem;--container-4xl:56rem;--container-5xl:64rem;--container-6xl:72rem;--container-7xl:80rem;--text-xs:.75rem;--text-xs--line-height:calc(1 / .75);--text-sm:.875rem;--text-sm--line-height:calc(1.25 / .875);--text-base:1rem;--text-base--line-height: 1.5 ;--text-lg:1.125rem;--text-lg--line-height:calc(1.75 / 1.125);--text-xl:1.25rem;--text-xl--line-height:calc(1.75 / 1.25);--text-2xl:1.5rem;--text-2xl--line-height:calc(2 / 1.5);--text-3xl:1.875rem;--text-3xl--line-height: 1.2 ;--text-4xl:2.25rem;--text-4xl--line-height:calc(2.5 / 2.25);--text-5xl:3rem;--text-5xl--line-height:1;--text-6xl:3.75rem;--text-6xl--line-height:1;--text-7xl:4.5rem;--text-7xl--line-height:1;--text-8xl:6rem;--text-8xl--line-height:1;--text-9xl:8rem;--text-9xl--line-height:1;--font-weight-thin:100;--font-weight-extralight:200;--font-weight-light:300;--font-weight-normal:400;--font-weight-medium:500;--font-weight-semibold:600;--font-weight-bold:700;--font-weight-extrabold:800;--font-weight-black:900;--tracking-tighter:-.05em;--tracking-tight:-.025em;--tracking-normal:0em;--tracking-wide:.025em;--tracking-wider:.05em;--tracking-widest:.1em;--leading-tight:1.25;--leading-snug:1.375;--leading-normal:1.5;--leading-relaxed:1.625;--leading-loose:2;--radius-xs:.125rem;--radius-sm:.25rem;--radius-md:.375rem;--radius-lg:.5rem;--radius-xl:.75rem;--radius-2xl:1rem;--radius-3xl:1.5rem;--radius-4xl:2rem;--shadow-2xs:0 1px #0000000d;--shadow-xs:0 1px 2px 0 #0000000d;--shadow-sm:0 1px 3px 0 #0000001a, 0 1px 2px -1px #0000001a;--shadow-md:0 4px 6px -1px #0000001a, 0 2px 4px -2px #0000001a;--shadow-lg:0 10px 15px -3px #0000001a, 0 4px 6px -4px #0000001a;--shadow-xl:0 20px 25px -5px #0000001a, 0 8px 10px -6px #0000001a;--shadow-2xl:0 25px 50px -12px #00000040;--inset-shadow-2xs:inset 0 1px #0000000d;--inset-shadow-xs:inset 0 1px 1px #0000000d;--inset-shadow-sm:inset 0 2px 4px #0000000d;--drop-shadow-xs:0 1px 1px #0000000d;--drop-shadow-sm:0 1px 2px #00000026;--drop-shadow-md:0 3px 3px #0000001f;--drop-shadow-lg:0 4px 4px #00000026;--drop-shadow-xl:0 9px 7px #0000001a;--drop-shadow-2xl:0 25px 25px #00000026;--ease-in:cubic-bezier(.4, 0, 1, 1);--ease-out:cubic-bezier(0, 0, .2, 1);--ease-in-out:cubic-bezier(.4, 0, .2, 1);--animate-spin:spin 1s linear infinite;--animate-ping:ping 1s cubic-bezier(0, 0, .2, 1) infinite;--animate-pulse:pulse 2s cubic-bezier(.4, 0, .6, 1) infinite;--animate-bounce:bounce 1s infinite;--blur-xs:4px;--blur-sm:8px;--blur-md:12px;--blur-lg:16px;--blur-xl:24px;--blur-2xl:40px;--blur-3xl:64px;--perspective-dramatic:100px;--perspective-near:300px;--perspective-normal:500px;--perspective-midrange:800px;--perspective-distant:1200px;--aspect-video:16 / 9;--default-transition-duration:.15s;--default-transition-timing-function:cubic-bezier(.4, 0, .2, 1);--default-font-family:var(--font-sans);--default-mono-font-family:var(--font-mono)}}@layer base{*,:after,:before,::backdrop{box-sizing:border-box;border:0 solid;margin:0;padding:0}::file-selector-button{box-sizing:border-box;border:0 solid;margin:0;padding:0}html,:host{-webkit-text-size-adjust:100%;tab-size:4;line-height:1.5;font-family:var(--default-font-family,ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji");font-feature-settings:var(--default-font-feature-settings,normal);font-variation-settings:var(--default-font-variation-settings,normal);-webkit-tap-highlight-color:transparent}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;-webkit-text-decoration:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,samp,pre{font-family:var(--default-mono-font-family,ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace);font-feature-settings:var(--default-mono-font-feature-settings,normal);font-variation-settings:var(--default-mono-font-variation-settings,normal);font-size:1em}small{font-size:80%}sub,sup{vertical-align:baseline;font-size:75%;line-height:0;position:relative}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}:-moz-focusring{outline:auto}progress{vertical-align:baseline}summary{display:list-item}ol,ul,menu{list-style:none}img,svg,video,canvas,audio,iframe,embed,object{vertical-align:middle;display:block}img,video{max-width:100%;height:auto}button,input,select,optgroup,textarea{font:inherit;font-feature-settings:inherit;font-variation-settings:inherit;letter-spacing:inherit;color:inherit;opacity:1;background-color:#0000;border-radius:0}::file-selector-button{font:inherit;font-feature-settings:inherit;font-variation-settings:inherit;letter-spacing:inherit;color:inherit;opacity:1;background-color:#0000;border-radius:0}:where(select:is([multiple],[size])) optgroup{font-weight:bolder}:where(select:is([multiple],[size])) optgroup option{padding-inline-start:20px}::file-selector-button{margin-inline-end:4px}::placeholder{opacity:1}@supports (not ((-webkit-appearance:-apple-pay-button))) or (contain-intrinsic-size:1px){::placeholder{color:currentColor}@supports (color:color-mix(in lab,red,red)){::placeholder{color:color-mix(in oklab,currentcolor 50%,transparent)}}}textarea{resize:vertical}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-date-and-time-value{min-height:1lh;text-align:inherit}::-webkit-datetime-edit{display:inline-flex}::-webkit-datetime-edit-fields-wrapper{padding:0}::-webkit-datetime-edit{padding-block:0}::-webkit-datetime-edit-year-field{padding-block:0}::-webkit-datetime-edit-month-field{padding-block:0}::-webkit-datetime-edit-day-field{padding-block:0}::-webkit-datetime-edit-hour-field{padding-block:0}::-webkit-datetime-edit-minute-field{padding-block:0}::-webkit-datetime-edit-second-field{padding-block:0}::-webkit-datetime-edit-millisecond-field{padding-block:0}::-webkit-datetime-edit-meridiem-field{padding-block:0}::-webkit-calendar-picker-indicator{line-height:1}:-moz-ui-invalid{box-shadow:none}button,input:where([type=button],[type=reset],[type=submit]){appearance:button}::file-selector-button{appearance:button}::-webkit-inner-spin-button{height:auto}::-webkit-outer-spin-button{height:auto}[hidden]:where(:not([hidden=until-found])){display:none!important}}@layer components;@layer utilities{.absolute{position:absolute}.fixed{position:fixed}.relative{position:relative}.static{position:static}.inset-0{inset:calc(var(--spacing) * 0)}.start{inset-inline-start:var(--spacing)}.top-0{top:calc(var(--spacing) * 0)}.right-0{right:calc(var(--spacing) * 0)}.container{width:100%}@media(min-width:40rem){.container{max-width:40rem}}@media(min-width:48rem){.container{max-width:48rem}}@media(min-width:64rem){.container{max-width:64rem}}@media(min-width:80rem){.container{max-width:80rem}}@media(min-width:96rem){.container{max-width:96rem}}.mx-auto{margin-inline:auto}.-mt-\[6\.6rem\]{margin-top:-6.6rem}.-mt-px{margin-top:-1px}.mt-2{margin-top:calc(var(--spacing) * 2)}.mt-4{margin-top:calc(var(--spacing) * 4)}.mt-6{margin-top:calc(var(--spacing) * 6)}.mt-8{margin-top:calc(var(--spacing) * 8)}.mr-2{margin-right:calc(var(--spacing) * 2)}.-mb-px{margin-bottom:-1px}.mb-1{margin-bottom:calc(var(--spacing) * 1)}.mb-2{margin-bottom:calc(var(--spacing) * 2)}.mb-4{margin-bottom:calc(var(--spacing) * 4)}.mb-6{margin-bottom:calc(var(--spacing) * 6)}.-ml-8{margin-left:calc(var(--spacing) * -8)}.-ml-px{margin-left:-1px}.ml-1{margin-left:calc(var(--spacing) * 1)}.ml-2{margin-left:calc(var(--spacing) * 2)}.ml-4{margin-left:calc(var(--spacing) * 4)}.ml-12{margin-left:calc(var(--spacing) * 12)}.contents{display:contents}.flex{display:flex}.grid{display:grid}.hidden{display:none}.inline-block{display:inline-block}.inline-flex{display:inline-flex}.table{display:table}.aspect-\[335\/364\]{aspect-ratio:335/364}.h-1{height:calc(var(--spacing) * 1)}.h-1\.5{height:calc(var(--spacing) * 1.5)}.h-2{height:calc(var(--spacing) * 2)}.h-2\.5{height:calc(var(--spacing) * 2.5)}.h-3{height:calc(var(--spacing) * 3)}.h-3\.5{height:calc(var(--spacing) * 3.5)}.h-5{height:calc(var(--spacing) * 5)}.h-8{height:calc(var(--spacing) * 8)}.h-14{height:calc(var(--spacing) * 14)}.h-14\.5{height:calc(var(--spacing) * 14.5)}.h-16{height:calc(var(--spacing) * 16)}.min-h-screen{min-height:100vh}.w-1{width:calc(var(--spacing) * 1)}.w-1\.5{width:calc(var(--spacing) * 1.5)}.w-2{width:calc(var(--spacing) * 2)}.w-2\.5{width:calc(var(--spacing) * 2.5)}.w-3{width:calc(var(--spacing) * 3)}.w-3\.5{width:calc(var(--spacing) * 3.5)}.w-5{width:calc(var(--spacing) * 5)}.w-8{width:calc(var(--spacing) * 8)}.w-\[438px\]{width:438px}.w-auto{width:auto}.w-full{width:100%}.max-w-6xl{max-width:var(--container-6xl)}.max-w-\[335px\]{max-width:335px}.max-w-none{max-width:none}.max-w-xl{max-width:var(--container-xl)}.flex-1{flex:1}.shrink-0{flex-shrink:0}.translate-y-0{--tw-translate-y:calc(var(--spacing) * 0);translate:var(--tw-translate-x) var(--tw-translate-y)}.transform{transform:var(--tw-rotate-x,) var(--tw-rotate-y,) var(--tw-rotate-z,) var(--tw-skew-x,) var(--tw-skew-y,)}.cursor-default{cursor:default}.cursor-not-allowed{cursor:not-allowed}.grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}.flex-col{flex-direction:column}.flex-col-reverse{flex-direction:column-reverse}.items-center{align-items:center}.justify-between{justify-content:space-between}.justify-center{justify-content:center}.justify-end{justify-content:flex-end}.justify-items-center{justify-items:center}.gap-2{gap:calc(var(--spacing) * 2)}.gap-3{gap:calc(var(--spacing) * 3)}.gap-4{gap:calc(var(--spacing) * 4)}:where(.space-x-1>:not(:last-child)){--tw-space-x-reverse:0;margin-inline-start:calc(calc(var(--spacing) * 1) * var(--tw-space-x-reverse));margin-inline-end:calc(calc(var(--spacing) * 1) * calc(1 - var(--tw-space-x-reverse)))}.overflow-hidden{overflow:hidden}.rounded-full{border-radius:3.40282e38px}.rounded-md{border-radius:var(--radius-md)}.rounded-sm{border-radius:var(--radius-sm)}.rounded-t-lg{border-top-left-radius:var(--radius-lg);border-top-right-radius:var(--radius-lg)}.rounded-l-md{border-top-left-radius:var(--radius-md);border-bottom-left-radius:var(--radius-md)}.rounded-r-md{border-top-right-radius:var(--radius-md);border-bottom-right-radius:var(--radius-md)}.rounded-br-lg{border-bottom-right-radius:var(--radius-lg)}.rounded-bl-lg{border-bottom-left-radius:var(--radius-lg)}.border{border-style:var(--tw-border-style);border-width:1px}.border-t{border-top-style:var(--tw-border-style);border-top-width:1px}.border-r{border-right-style:var(--tw-border-style);border-right-width:1px}.border-\[\#19140035\]{border-color:#19140035}.border-\[\#e3e3e0\]{border-color:#e3e3e0}.border-black{border-color:var(--color-black)}.border-gray-200{border-color:var(--color-gray-200)}.border-gray-300{border-color:var(--color-gray-300)}.border-gray-400{border-color:var(--color-gray-400)}.border-transparent{border-color:#0000}.bg-\[\#1b1b18\]{background-color:#1b1b18}.bg-\[\#FDFDFC\]{background-color:#fdfdfc}.bg-\[\#dbdbd7\]{background-color:#dbdbd7}.bg-\[\#fff2f2\]{background-color:#fff2f2}.bg-gray-100{background-color:var(--color-gray-100)}.bg-gray-200{background-color:var(--color-gray-200)}.bg-white{background-color:var(--color-white)}.p-6{padding:calc(var(--spacing) * 6)}.px-2{padding-inline:calc(var(--spacing) * 2)}.px-4{padding-inline:calc(var(--spacing) * 4)}.px-5{padding-inline:calc(var(--spacing) * 5)}.px-6{padding-inline:calc(var(--spacing) * 6)}.py-1{padding-block:calc(var(--spacing) * 1)}.py-1\.5{padding-block:calc(var(--spacing) * 1.5)}.py-2{padding-block:calc(var(--spacing) * 2)}.py-4{padding-block:calc(var(--spacing) * 4)}.pt-8{padding-top:calc(var(--spacing) * 8)}.pb-6{padding-bottom:calc(var(--spacing) * 6)}.pb-12{padding-bottom:calc(var(--spacing) * 12)}.text-center{text-align:center}.text-lg{font-size:var(--text-lg);line-height:var(--tw-leading,var(--text-lg--line-height))}.text-sm{font-size:var(--text-sm);line-height:var(--tw-leading,var(--text-sm--line-height))}.text-\[13px\]{font-size:13px}.leading-5{--tw-leading:calc(var(--spacing) * 5);line-height:calc(var(--spacing) * 5)}.leading-7{--tw-leading:calc(var(--spacing) * 7);line-height:calc(var(--spacing) * 7)}.leading-\[20px\]{--tw-leading:20px;line-height:20px}.leading-normal{--tw-leading:var(--leading-normal);line-height:var(--leading-normal)}.font-medium{--tw-font-weight:var(--font-weight-medium);font-weight:var(--font-weight-medium)}.font-semibold{--tw-font-weight:var(--font-weight-semibold);font-weight:var(--font-weight-semibold)}.tracking-wider{--tw-tracking:var(--tracking-wider);letter-spacing:var(--tracking-wider)}.text-\[\#1B1B18\],.text-\[\#1b1b18\]{color:#1b1b18}.text-\[\#706f6c\]{color:#706f6c}.text-\[\#F3BEC7\]{color:#f3bec7}.text-\[\#F8B803\]{color:#f8b803}.text-\[\#F53003\],.text-\[\#f53003\]{color:#f53003}.text-gray-200{color:var(--color-gray-200)}.text-gray-300{color:var(--color-gray-300)}.text-gray-400{color:var(--color-gray-400)}.text-gray-500{color:var(--color-gray-500)}.text-gray-600{color:var(--color-gray-600)}.text-gray-700{color:var(--color-gray-700)}.text-gray-800{color:var(--color-gray-800)}.text-gray-900{color:var(--color-gray-900)}.text-white{color:var(--color-white)}.uppercase{text-transform:uppercase}.underline{text-decoration-line:underline}.underline-offset-4{text-underline-offset:4px}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.opacity-100{opacity:1}.mix-blend-color{mix-blend-mode:color}.mix-blend-darken{mix-blend-mode:darken}.mix-blend-hard-light{mix-blend-mode:hard-light}.mix-blend-multiply{mix-blend-mode:multiply}.shadow{--tw-shadow:0 1px 3px 0 var(--tw-shadow-color,#0000001a), 0 1px 2px -1px var(--tw-shadow-color,#0000001a);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.shadow-\[0px_0px_1px_0px_rgba\(0\,0\,0\,0\.03\)\,0px_1px_2px_0px_rgba\(0\,0\,0\,0\.06\)\]{--tw-shadow:0px 0px 1px 0px var(--tw-shadow-color,#00000008), 0px 1px 2px 0px var(--tw-shadow-color,#0000000f);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.shadow-\[inset_0px_0px_0px_1px_rgba\(26\,26\,0\,0\.16\)\]{--tw-shadow:inset 0px 0px 0px 1px var(--tw-shadow-color,#1a1a0029);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.shadow-sm{--tw-shadow:0 1px 3px 0 var(--tw-shadow-color,#0000001a), 0 1px 2px -1px var(--tw-shadow-color,#0000001a);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.ring-gray-300{--tw-ring-color:var(--color-gray-300)}.filter{filter:var(--tw-blur,) var(--tw-brightness,) var(--tw-contrast,) var(--tw-grayscale,) var(--tw-hue-rotate,) var(--tw-invert,) var(--tw-saturate,) var(--tw-sepia,) var(--tw-drop-shadow,)}.transition{transition-property:color,background-color,border-color,outline-color,text-decoration-color,fill,stroke,--tw-gradient-from,--tw-gradient-via,--tw-gradient-to,opacity,box-shadow,transform,translate,scale,rotate,filter,-webkit-backdrop-filter,backdrop-filter,display,content-visibility,overlay,pointer-events;transition-timing-function:var(--tw-ease,var(--default-transition-timing-function));transition-duration:var(--tw-duration,var(--default-transition-duration))}.transition-all{transition-property:all;transition-timing-function:var(--tw-ease,var(--default-transition-timing-function));transition-duration:var(--tw-duration,var(--default-transition-duration))}.transition-opacity{transition-property:opacity;transition-timing-function:var(--tw-ease,var(--default-transition-timing-function));transition-duration:var(--tw-duration,var(--default-transition-duration))}.delay-200{transition-delay:.2s}.delay-300{transition-delay:.3s}.delay-400{transition-delay:.4s}.duration-150{--tw-duration:.15s;transition-duration:.15s}.duration-750{--tw-duration:.75s;transition-duration:.75s}.ease-in-out{--tw-ease:var(--ease-in-out);transition-timing-function:var(--ease-in-out)}.\[--stroke-color\:\#1B1B18\]{--stroke-color:#1b1b18}.not-has-\[nav\]\:hidden:not(:has(:is(nav))){display:none}.before\:absolute:before{content:var(--tw-content);position:absolute}.before\:top-0:before{content:var(--tw-content);top:calc(var(--spacing) * 0)}.before\:top-1\/2:before{content:var(--tw-content);top:50%}.before\:bottom-0:before{content:var(--tw-content);bottom:calc(var(--spacing) * 0)}.before\:bottom-1\/2:before{content:var(--tw-content);bottom:50%}.before\:left-\[0\.4rem\]:before{content:var(--tw-content);left:.4rem}.before\:border-l:before{content:var(--tw-content);border-left-style:var(--tw-border-style);border-left-width:1px}.before\:border-\[\#e3e3e0\]:before{content:var(--tw-content);border-color:#e3e3e0}@media(hover:hover){.hover\:border-\[\#1915014a\]:hover{border-color:#1915014a}.hover\:border-\[\#19140035\]:hover{border-color:#19140035}.hover\:border-black:hover{border-color:var(--color-black)}.hover\:bg-black:hover{background-color:var(--color-black)}.hover\:bg-gray-100:hover{background-color:var(--color-gray-100)}.hover\:text-gray-400:hover{color:var(--color-gray-400)}.hover\:text-gray-700:hover{color:var(--color-gray-700)}}.focus\:border-blue-300:focus{border-color:var(--color-blue-300)}.focus\:ring:focus{--tw-ring-shadow:var(--tw-ring-inset,) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color,currentcolor);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.focus\:outline-none:focus{--tw-outline-style:none;outline-style:none}.active\:bg-gray-100:active{background-color:var(--color-gray-100)}.active\:text-gray-500:active{color:var(--color-gray-500)}.active\:text-gray-700:active{color:var(--color-gray-700)}.active\:text-gray-800:active{color:var(--color-gray-800)}@media(min-width:40rem){.sm\:flex{display:flex}.sm\:hidden{display:none}.sm\:flex-1{flex:1}.sm\:items-center{align-items:center}.sm\:justify-between{justify-content:space-between}.sm\:justify-start{justify-content:flex-start}.sm\:gap-2{gap:calc(var(--spacing) * 2)}.sm\:px-6{padding-inline:calc(var(--spacing) * 6)}.sm\:pt-0{padding-top:calc(var(--spacing) * 0)}}@media(min-width:64rem){.lg\:mt-10{margin-top:calc(var(--spacing) * 10)}.lg\:mb-0{margin-bottom:calc(var(--spacing) * 0)}.lg\:mb-6{margin-bottom:calc(var(--spacing) * 6)}.lg\:-ml-px{margin-left:-1px}.lg\:ml-0{margin-left:calc(var(--spacing) * 0)}.lg\:block{display:block}.lg\:aspect-auto{aspect-ratio:auto}.lg\:w-\[438px\]{width:438px}.lg\:max-w-4xl{max-width:var(--container-4xl)}.lg\:grow{flex-grow:1}.lg\:flex-row{flex-direction:row}.lg\:justify-center{justify-content:center}.lg\:rounded-t-none{border-top-left-radius:0;border-top-right-radius:0}.lg\:rounded-tl-lg{border-top-left-radius:var(--radius-lg)}.lg\:rounded-r-lg{border-top-right-radius:var(--radius-lg);border-bottom-right-radius:var(--radius-lg)}.lg\:rounded-br-none{border-bottom-right-radius:0}.lg\:p-8{padding:calc(var(--spacing) * 8)}.lg\:p-20{padding:calc(var(--spacing) * 20)}.lg\:px-8{padding-inline:calc(var(--spacing) * 8)}.lg\:pb-10{padding-bottom:calc(var(--spacing) * 10)}}.rtl\:flex-row-reverse:where(:dir(rtl),[dir=rtl],[dir=rtl] *){flex-direction:row-reverse}@media(prefers-color-scheme:dark){.dark\:border-\[\#3E3E3A\]{border-color:#3e3e3a}.dark\:border-\[\#eeeeec\]{border-color:#eeeeec}.dark\:border-gray-600{border-color:var(--color-gray-600)}.dark\:bg-\[\#0a0a0a\]{background-color:#0a0a0a}.dark\:bg-\[\#1D0002\]{background-color:#1d0002}.dark\:bg-\[\#3E3E3A\]{background-color:#3e3e3a}.dark\:bg-\[\#161615\]{background-color:#161615}.dark\:bg-\[\#eeeeec\]{background-color:#eeeeec}.dark\:bg-gray-700{background-color:var(--color-gray-700)}.dark\:bg-gray-800{background-color:var(--color-gray-800)}.dark\:bg-gray-900{background-color:var(--color-gray-900)}.dark\:text-\[\#1C1C1A\]{color:#1c1c1a}.dark\:text-\[\#4B0600\]{color:#4b0600}.dark\:text-\[\#391800\]{color:#391800}.dark\:text-\[\#733000\]{color:#733000}.dark\:text-\[\#A1A09A\]{color:#a1a09a}.dark\:text-\[\#EDEDEC\]{color:#ededec}.dark\:text-\[\#F61500\]{color:#f61500}.dark\:text-\[\#FF4433\]{color:#f43}.dark\:text-black{color:var(--color-black)}.dark\:text-gray-200{color:var(--color-gray-200)}.dark\:text-gray-300{color:var(--color-gray-300)}.dark\:text-gray-400{color:var(--color-gray-400)}.dark\:text-gray-600{color:var(--color-gray-600)}.dark\:mix-blend-hard-light{mix-blend-mode:hard-light}.dark\:mix-blend-normal{mix-blend-mode:normal}.dark\:shadow-\[inset_0px_0px_0px_1px_\#fffaed2d\]{--tw-shadow:inset 0px 0px 0px 1px var(--tw-shadow-color,#fffaed2d);box-shadow:var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow)}.dark\:\[--stroke-color\:\#FF750F\]{--stroke-color:#ff750f}.dark\:before\:border-\[\#3E3E3A\]:before{content:var(--tw-content);border-color:#3e3e3a}@media(hover:hover){.dark\:hover\:border-\[\#3E3E3A\]:hover{border-color:#3e3e3a}.dark\:hover\:border-\[\#62605b\]:hover{border-color:#62605b}.dark\:hover\:border-white:hover{border-color:var(--color-white)}.dark\:hover\:bg-gray-900:hover{background-color:var(--color-gray-900)}.dark\:hover\:bg-white:hover{background-color:var(--color-white)}.dark\:hover\:text-gray-200:hover{color:var(--color-gray-200)}.dark\:hover\:text-gray-300:hover{color:var(--color-gray-300)}}.dark\:focus\:border-blue-700:focus{border-color:var(--color-blue-700)}.dark\:focus\:border-blue-800:focus{border-color:var(--color-blue-800)}.dark\:active\:bg-gray-700:active{background-color:var(--color-gray-700)}.dark\:active\:text-gray-300:active{color:var(--color-gray-300)}}@starting-style{.starting\:opacity-0{opacity:0}}@media(prefers-reduced-motion:no-preference){@starting-style{.motion-safe\:starting\:-translate-x-\[26px\]{--tw-translate-x: -26px ;translate:var(--tw-translate-x) var(--tw-translate-y)}}@starting-style{.motion-safe\:starting\:-translate-x-\[51px\]{--tw-translate-x: -51px ;translate:var(--tw-translate-x) var(--tw-translate-y)}}@starting-style{.motion-safe\:starting\:-translate-x-\[78px\]{--tw-translate-x: -78px ;translate:var(--tw-translate-x) var(--tw-translate-y)}}@starting-style{.motion-safe\:starting\:-translate-x-\[102px\]{--tw-translate-x: -102px ;translate:var(--tw-translate-x) var(--tw-translate-y)}}@starting-style{.motion-safe\:starting\:translate-y-6{--tw-translate-y:calc(var(--spacing) * 6);translate:var(--tw-translate-x) var(--tw-translate-y)}}}}@property --tw-translate-x{syntax:"*";inherits:false;initial-value:0}@property --tw-translate-y{syntax:"*";inherits:false;initial-value:0}@property --tw-translate-z{syntax:"*";inherits:false;initial-value:0}@property --tw-rotate-x{syntax:"*";inherits:false}@property --tw-rotate-y{syntax:"*";inherits:false}@property --tw-rotate-z{syntax:"*";inherits:false}@property --tw-skew-x{syntax:"*";inherits:false}@property --tw-skew-y{syntax:"*";inherits:false}@property --tw-space-x-reverse{syntax:"*";inherits:false;initial-value:0}@property --tw-border-style{syntax:"*";inherits:false;initial-value:solid}@property --tw-leading{syntax:"*";inherits:false}@property --tw-font-weight{syntax:"*";inherits:false}@property --tw-tracking{syntax:"*";inherits:false}@property --tw-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-shadow-color{syntax:"*";inherits:false}@property --tw-shadow-alpha{syntax:"<percentage>";inherits:false;initial-value:100%}@property --tw-inset-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-inset-shadow-color{syntax:"*";inherits:false}@property --tw-inset-shadow-alpha{syntax:"<percentage>";inherits:false;initial-value:100%}@property --tw-ring-color{syntax:"*";inherits:false}@property --tw-ring-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-inset-ring-color{syntax:"*";inherits:false}@property --tw-inset-ring-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-ring-inset{syntax:"*";inherits:false}@property --tw-ring-offset-width{syntax:"<length>";inherits:false;initial-value:0}@property --tw-ring-offset-color{syntax:"*";inherits:false;initial-value:#fff}@property --tw-ring-offset-shadow{syntax:"*";inherits:false;initial-value:0 0 #0000}@property --tw-blur{syntax:"*";inherits:false}@property --tw-brightness{syntax:"*";inherits:false}@property --tw-contrast{syntax:"*";inherits:false}@property --tw-grayscale{syntax:"*";inherits:false}@property --tw-hue-rotate{syntax:"*";inherits:false}@property --tw-invert{syntax:"*";inherits:false}@property --tw-opacity{syntax:"*";inherits:false}@property --tw-saturate{syntax:"*";inherits:false}@property --tw-sepia{syntax:"*";inherits:false}@property --tw-drop-shadow{syntax:"*";inherits:false}@property --tw-drop-shadow-color{syntax:"*";inherits:false}@property --tw-drop-shadow-alpha{syntax:"<percentage>";inherits:false;initial-value:100%}@property --tw-drop-shadow-size{syntax:"*";inherits:false}@property --tw-duration{syntax:"*";inherits:false}@property --tw-ease{syntax:"*";inherits:false}@property --tw-content{syntax:"*";inherits:false;initial-value:""}@keyframes spin{to{transform:rotate(360deg)}}@keyframes ping{75%,to{opacity:0;transform:scale(2)}}@keyframes pulse{50%{opacity:.5}}@keyframes bounce{0%,to{animation-timing-function:cubic-bezier(.8,0,1,1);transform:translateY(-25%)}50%{animation-timing-function:cubic-bezier(0,0,.2,1);transform:none}}
            </style>
        @endif
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                        >
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>
        <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
            <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row">
                <div class="text-[13px] leading-[20px] flex-1 p-6 pb-6 lg:p-20 lg:pb-10 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-bl-lg rounded-br-lg lg:rounded-tl-lg lg:rounded-br-none">
                    <h1 class="mb-1 font-medium">Let's get started</h1>
                    <p class="mb-2 text-[#706f6c] dark:text-[#A1A09A]">With so many options available to you,<br /> we suggest you start with the following:</p>
                    <ul class="flex flex-col mb-4 lg:mb-6">
                        <li class="flex items-center gap-4 py-2 relative before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A] before:top-1/2 before:bottom-0 before:left-[0.4rem] before:absolute">
                            <span class="relative py-1 bg-white dark:bg-[#161615]">
                                <span class="flex items-center justify-center rounded-full bg-[#FDFDFC] dark:bg-[#161615] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] w-3.5 h-3.5 border dark:border-[#3E3E3A] border-[#e3e3e0]">
                                    <span class="rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A] w-1.5 h-1.5"></span>
                                </span>
                            </span>
                            <span>
                                Read the
                                <a href="https://laravel.com/docs" target="_blank" class="inline-flex items-center space-x-1 font-medium underline underline-offset-4 text-[#f53003] dark:text-[#FF4433] ml-1">
                                    <span>Documentation</span>
                                    <svg
                                        width="10"
                                        height="11"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-2.5 h-2.5"
                                    >
                                        <path
                                            d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                            stroke="currentColor"
                                            stroke-linecap="square"
                                        />
                                    </svg>
                                </a>
                            </span>
                        </li>
                        <li class="flex items-center gap-4 py-2 relative before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A] before:bottom-1/2 before:top-0 before:left-[0.4rem] before:absolute">
                            <span class="relative py-1 bg-white dark:bg-[#161615]">
                                <span class="flex items-center justify-center rounded-full bg-[#FDFDFC] dark:bg-[#161615] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] w-3.5 h-3.5 border dark:border-[#3E3E3A] border-[#e3e3e0]">
                                    <span class="rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A] w-1.5 h-1.5"></span>
                                </span>
                            </span>
                            <span>
                                Watch video tutorials at
                                <a href="https://laracasts.com" target="_blank" class="inline-flex items-center space-x-1 font-medium underline underline-offset-4 text-[#f53003] dark:text-[#FF4433] ml-1">
                                    <span>Laracasts</span>
                                    <svg
                                        width="10"
                                        height="11"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-2.5 h-2.5"
                                    >
                                        <path
                                            d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                            stroke="currentColor"
                                            stroke-linecap="square"
                                        />
                                    </svg>
                                </a>
                            </span>
                        </li>
                    </ul>
                    <ul class="flex gap-3 text-sm leading-normal">
                        <li>
                            <a href="https://cloud.laravel.com" target="_blank" class="inline-block dark:bg-[#eeeeec] dark:border-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white dark:hover:border-white hover:bg-black hover:border-black px-5 py-1.5 bg-[#1b1b18] rounded-sm border border-black text-white text-sm leading-normal">
                                Deploy now
                            </a>
                        </li>
                    </ul>

                    <p class="mt-6 lg:mt-10 text-[#706f6c] dark:text-[#A1A09A]">
                        v{{ app()->version() }}
                        <a href="https://github.com/laravel/framework/blob/13.x/CHANGELOG.md" target="_blank" class="inline-flex items-center space-x-1 font-medium underline underline-offset-4 text-[#f53003] dark:text-[#FF4433] ml-1">
                            <span>View changelog</span>
                            <svg
                                width="10"
                                height="11"
                                viewBox="0 0 10 11"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-2.5 h-2.5"
                            >
                                <path
                                    d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                    stroke="currentColor"
                                    stroke-linecap="square"
                                />
                            </svg>
                        </a>
                    </p>
                </div>
                <div class="bg-[#fff2f2] dark:bg-[#1D0002] relative lg:-ml-px -mb-px lg:mb-0 rounded-t-lg lg:rounded-t-none lg:rounded-r-lg aspect-[335/364] lg:aspect-auto w-full lg:w-[438px] shrink-0 overflow-hidden">
                    {{-- Laravel Logo --}}
                    <svg class="w-full text-[#F53003] dark:text-[#F61500] transition-all translate-y-0 opacity-100 max-w-none duration-750 starting:opacity-0 motion-safe:starting:translate-y-6" viewBox="0 0 438 104" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.2036 -3H0V102.197H49.5189V86.7187H17.2036V-3Z" fill="currentColor" />
                        <path d="M110.256 41.6337C108.061 38.1275 104.945 35.3731 100.905 33.3681C96.8667 31.3647 92.8016 30.3618 88.7131 30.3618C83.4247 30.3618 78.5885 31.3389 74.201 33.2923C69.8111 35.2456 66.0474 37.928 62.9059 41.3333C59.7643 44.7401 57.3198 48.6726 55.5754 53.1293C53.8287 57.589 52.9572 62.274 52.9572 67.1813C52.9572 72.1925 53.8287 76.8995 55.5754 81.3069C57.3191 85.7173 59.7636 89.6241 62.9059 93.0293C66.0474 96.4361 69.8119 99.1155 74.201 101.069C78.5885 103.022 83.4247 103.999 88.7131 103.999C92.8016 103.999 96.8667 102.997 100.905 100.994C104.945 98.9911 108.061 96.2359 110.256 92.7282V102.195H126.563V32.1642H110.256V41.6337ZM108.76 75.7472C107.762 78.4531 106.366 80.8078 104.572 82.8112C102.776 84.8161 100.606 86.4183 98.0637 87.6206C95.5202 88.823 92.7004 89.4238 89.6103 89.4238C86.5178 89.4238 83.7252 88.823 81.2324 87.6206C78.7388 86.4183 76.5949 84.8161 74.7998 82.8112C73.004 80.8078 71.6319 78.4531 70.6856 75.7472C69.7356 73.0421 69.2644 70.1868 69.2644 67.1821C69.2644 64.1758 69.7356 61.3205 70.6856 58.6154C71.6319 55.9102 73.004 53.5571 74.7998 51.5522C76.5949 49.5495 78.738 47.9451 81.2324 46.7427C83.7252 45.5404 86.5178 44.9396 89.6103 44.9396C92.7012 44.9396 95.5202 45.5404 98.0637 46.7427C100.606 47.9451 102.776 49.5487 104.572 51.5522C106.367 53.5571 107.762 55.9102 108.76 58.6154C109.756 61.3205 110.256 64.1758 110.256 67.1821C110.256 70.1868 109.756 73.0421 108.76 75.7472Z" fill="currentColor" />
                        <path d="M242.805 41.6337C240.611 38.1275 237.494 35.3731 233.455 33.3681C229.416 31.3647 225.351 30.3618 221.262 30.3618C215.974 30.3618 211.138 31.3389 206.75 33.2923C202.36 35.2456 198.597 37.928 195.455 41.3333C192.314 44.7401 189.869 48.6726 188.125 53.1293C186.378 57.589 185.507 62.274 185.507 67.1813C185.507 72.1925 186.378 76.8995 188.125 81.3069C189.868 85.7173 192.313 89.6241 195.455 93.0293C198.597 96.4361 202.361 99.1155 206.75 101.069C211.138 103.022 215.974 103.999 221.262 103.999C225.351 103.999 229.416 102.997 233.455 100.994C237.494 98.9911 240.611 96.2359 242.805 92.7282V102.195H259.112V32.1642H242.805V41.6337ZM241.31 75.7472C240.312 78.4531 238.916 80.8078 237.122 82.8112C235.326 84.8161 233.156 86.4183 230.614 87.6206C228.07 88.823 225.251 89.4238 222.16 89.4238C219.068 89.4238 216.275 88.823 213.782 87.6206C211.289 86.4183 209.145 84.8161 207.35 82.8112C205.554 80.8078 204.182 78.4531 203.236 75.7472C202.286 73.0421 201.814 70.1868 201.814 67.1821C201.814 64.1758 202.286 61.3205 203.236 58.6154C204.182 55.9102 205.554 53.5571 207.35 51.5522C209.145 49.5495 211.288 47.9451 213.782 46.7427C216.275 45.5404 219.068 44.9396 222.16 44.9396C225.251 44.9396 228.07 45.5404 230.614 46.7427C233.156 47.9451 235.326 49.5487 237.122 51.5522C238.917 53.5571 240.312 55.9102 241.31 58.6154C242.306 61.3205 242.806 64.1758 242.806 67.1821C242.805 70.1868 242.305 73.0421 241.31 75.7472Z" fill="currentColor" />
                        <path d="M438 -3H421.694V102.197H438V-3Z" fill="currentColor" />
                        <path d="M139.43 102.197H155.735V48.2834H183.712V32.1665H139.43V102.197Z" fill="currentColor" />
                        <path d="M324.49 32.1665L303.995 85.794L283.498 32.1665H266.983L293.748 102.197H314.242L341.006 32.1665H324.49Z" fill="currentColor" />
                        <path d="M376.571 30.3656C356.603 30.3656 340.797 46.8497 340.797 67.1828C340.797 89.6597 356.094 104 378.661 104C391.29 104 399.354 99.1488 409.206 88.5848L398.189 80.0226C398.183 80.031 389.874 90.9895 377.468 90.9895C363.048 90.9895 356.977 79.3111 356.977 73.269H411.075C413.917 50.1328 398.775 30.3656 376.571 30.3656ZM357.02 61.0967C357.145 59.7487 359.023 43.3761 376.442 43.3761C393.861 43.3761 395.978 59.7464 396.099 61.0967H357.02Z" fill="currentColor" />
                    </svg>

                    {{-- 13 --}}
                    <svg class="w-[438px] max-w-none relative -mt-[6.6rem] -ml-8 lg:ml-0 [--stroke-color:#1B1B18] dark:[--stroke-color:#FF750F]" viewBox="0 0 440 392" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g class="mix-blend-darken dark:mix-blend-normal transition-all delay-300 opacity-100 duration-750 starting:opacity-0 text-[#1B1B18] dark:text-black">
                            <mask id="path-1-mask" maskUnits="userSpaceOnUse" x="-0.328613" y="103" width="338" height="299" fill="black">
                                <rect fill="white" x="-0.328613" y="103" width="338" height="299"/>
                                <path d="M234.936 400.8C204.136 400.8 178.936 392.4 159.336 375.6C140.136 358.8 130.536 337 130.536 310.2H200.736C200.736 318.2 203.736 324.8 209.736 330C215.736 335.2 223.736 337.8 233.736 337.8C243.336 337.8 251.136 335 257.136 329.4C263.536 323.8 266.736 316.6 266.736 307.8C266.736 299.8 263.936 293.2 258.336 288C252.736 282.8 245.536 280.2 236.736 280.2H199.536V218.4H236.736C243.536 218.4 249.336 216 254.136 211.2C258.936 206.4 261.336 200.4 261.336 193.2C261.336 184.8 258.736 178.2 253.536 173.4C248.336 168.6 241.736 166.2 233.736 166.2C226.536 166.2 220.336 168.4 215.136 172.8C210.336 177.2 207.936 182.8 207.936 189.6H141.336C141.336 164.8 150.136 144.6 167.736 129C185.336 113 207.936 105 235.536 105C263.136 105 285.536 112.2 302.736 126.6C320.336 141 329.136 160 329.136 183.6C329.136 200.8 324.536 214.8 315.336 225.6C306.136 236 294.336 243.2 279.936 247.2C297.136 252 310.736 260.2 320.736 271.8C331.136 283.4 336.336 298 336.336 315.6C336.336 340.4 326.936 360.8 308.136 376.8C289.336 392.8 264.936 400.8 234.936 400.8Z"/>
                                <path d="M26.8714 167.6H1.67139V105.2H94.6714V400.2H26.8714V167.6Z"/>
                            </mask>
                            <path d="M234.936 400.8C204.136 400.8 178.936 392.4 159.336 375.6C140.136 358.8 130.536 337 130.536 310.2H200.736C200.736 318.2 203.736 324.8 209.736 330C215.736 335.2 223.736 337.8 233.736 337.8C243.336 337.8 251.136 335 257.136 329.4C263.536 323.8 266.736 316.6 266.736 307.8C266.736 299.8 263.936 293.2 258.336 288C252.736 282.8 245.536 280.2 236.736 280.2H199.536V218.4H236.736C243.536 218.4 249.336 216 254.136 211.2C258.936 206.4 261.336 200.4 261.336 193.2C261.336 184.8 258.736 178.2 253.536 173.4C248.336 168.6 241.736 166.2 233.736 166.2C226.536 166.2 220.336 168.4 215.136 172.8C210.336 177.2 207.936 182.8 207.936 189.6H141.336C141.336 164.8 150.136 144.6 167.736 129C185.336 113 207.936 105 235.536 105C263.136 105 285.536 112.2 302.736 126.6C320.336 141 329.136 160 329.136 183.6C329.136 200.8 324.536 214.8 315.336 225.6C306.136 236 294.336 243.2 279.936 247.2C297.136 252 310.736 260.2 320.736 271.8C331.136 283.4 336.336 298 336.336 315.6C336.336 340.4 326.936 360.8 308.136 376.8C289.336 392.8 264.936 400.8 234.936 400.8Z" fill="currentColor"/>
                            <path d="M26.8714 167.6H1.67139V105.2H94.6714V400.2H26.8714V167.6Z" fill="currentColor"/>
                            <path d="M234.936 400.8C204.136 400.8 178.936 392.4 159.336 375.6C140.136 358.8 130.536 337 130.536 310.2H200.736C200.736 318.2 203.736 324.8 209.736 330C215.736 335.2 223.736 337.8 233.736 337.8C243.336 337.8 251.136 335 257.136 329.4C263.536 323.8 266.736 316.6 266.736 307.8C266.736 299.8 263.936 293.2 258.336 288C252.736 282.8 245.536 280.2 236.736 280.2H199.536V218.4H236.736C243.536 218.4 249.336 216 254.136 211.2C258.936 206.4 261.336 200.4 261.336 193.2C261.336 184.8 258.736 178.2 253.536 173.4C248.336 168.6 241.736 166.2 233.736 166.2C226.536 166.2 220.336 168.4 215.136 172.8C210.336 177.2 207.936 182.8 207.936 189.6H141.336C141.336 164.8 150.136 144.6 167.736 129C185.336 113 207.936 105 235.536 105C263.136 105 285.536 112.2 302.736 126.6C320.336 141 329.136 160 329.136 183.6C329.136 200.8 324.536 214.8 315.336 225.6C306.136 236 294.336 243.2 279.936 247.2C297.136 252 310.736 260.2 320.736 271.8C331.136 283.4 336.336 298 336.336 315.6C336.336 340.4 326.936 360.8 308.136 376.8C289.336 392.8 264.936 400.8 234.936 400.8Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-1-mask)"/>
                            <path d="M26.8714 167.6H1.67139V105.2H94.6714V400.2H26.8714V167.6Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-1-mask)"/>
                        </g>

                        <g class="transition-all delay-400 opacity-100 duration-750 starting:opacity-0 motion-safe:starting:-translate-x-[26px] text-[#F3BEC7] dark:text-[#4B0600]">
                            <mask id="path-2-mask" maskUnits="userSpaceOnUse" x="25.3357" y="103" width="338" height="299" fill="black">
                                <rect fill="white" x="25.3357" y="103" width="338" height="299"/>
                                <path d="M260.6 400.8C229.8 400.8 204.6 392.4 185 375.6C165.8 358.8 156.2 337 156.2 310.2H226.4C226.4 318.2 229.4 324.8 235.4 330C241.4 335.2 249.4 337.8 259.4 337.8C269 337.8 276.8 335 282.8 329.4C289.2 323.8 292.4 316.6 292.4 307.8C292.4 299.8 289.6 293.2 284 288C278.4 282.8 271.2 280.2 262.4 280.2H225.2V218.4H262.4C269.2 218.4 275 216 279.8 211.2C284.6 206.4 287 200.4 287 193.2C287 184.8 284.4 178.2 279.2 173.4C274 168.6 267.4 166.2 259.4 166.2C252.2 166.2 246 168.4 240.8 172.8C236 177.2 233.6 182.8 233.6 189.6H167C167 164.8 175.8 144.6 193.4 129C211 113 233.6 105 261.2 105C288.8 105 311.2 112.2 328.4 126.6C346 141 354.8 160 354.8 183.6C354.8 200.8 350.2 214.8 341 225.6C331.8 236 320 243.2 305.6 247.2C322.8 252 336.4 260.2 346.4 271.8C356.8 283.4 362 298 362 315.6C362 340.4 352.6 360.8 333.8 376.8C315 392.8 290.6 400.8 260.6 400.8Z"/>
                                <path d="M52.5357 167.6H27.3357V105.2H120.336V400.2H52.5357V167.6Z"/>
                            </mask>
                            <path d="M260.6 400.8C229.8 400.8 204.6 392.4 185 375.6C165.8 358.8 156.2 337 156.2 310.2H226.4C226.4 318.2 229.4 324.8 235.4 330C241.4 335.2 249.4 337.8 259.4 337.8C269 337.8 276.8 335 282.8 329.4C289.2 323.8 292.4 316.6 292.4 307.8C292.4 299.8 289.6 293.2 284 288C278.4 282.8 271.2 280.2 262.4 280.2H225.2V218.4H262.4C269.2 218.4 275 216 279.8 211.2C284.6 206.4 287 200.4 287 193.2C287 184.8 284.4 178.2 279.2 173.4C274 168.6 267.4 166.2 259.4 166.2C252.2 166.2 246 168.4 240.8 172.8C236 177.2 233.6 182.8 233.6 189.6H167C167 164.8 175.8 144.6 193.4 129C211 113 233.6 105 261.2 105C288.8 105 311.2 112.2 328.4 126.6C346 141 354.8 160 354.8 183.6C354.8 200.8 350.2 214.8 341 225.6C331.8 236 320 243.2 305.6 247.2C322.8 252 336.4 260.2 346.4 271.8C356.8 283.4 362 298 362 315.6C362 340.4 352.6 360.8 333.8 376.8C315 392.8 290.6 400.8 260.6 400.8Z" fill="currentColor"/>
                            <path d="M52.5357 167.6H27.3357V105.2H120.336V400.2H52.5357V167.6Z" fill="currentColor"/>
                            <path d="M260.6 400.8C229.8 400.8 204.6 392.4 185 375.6C165.8 358.8 156.2 337 156.2 310.2H226.4C226.4 318.2 229.4 324.8 235.4 330C241.4 335.2 249.4 337.8 259.4 337.8C269 337.8 276.8 335 282.8 329.4C289.2 323.8 292.4 316.6 292.4 307.8C292.4 299.8 289.6 293.2 284 288C278.4 282.8 271.2 280.2 262.4 280.2H225.2V218.4H262.4C269.2 218.4 275 216 279.8 211.2C284.6 206.4 287 200.4 287 193.2C287 184.8 284.4 178.2 279.2 173.4C274 168.6 267.4 166.2 259.4 166.2C252.2 166.2 246 168.4 240.8 172.8C236 177.2 233.6 182.8 233.6 189.6H167C167 164.8 175.8 144.6 193.4 129C211 113 233.6 105 261.2 105C288.8 105 311.2 112.2 328.4 126.6C346 141 354.8 160 354.8 183.6C354.8 200.8 350.2 214.8 341 225.6C331.8 236 320 243.2 305.6 247.2C322.8 252 336.4 260.2 346.4 271.8C356.8 283.4 362 298 362 315.6C362 340.4 352.6 360.8 333.8 376.8C315 392.8 290.6 400.8 260.6 400.8Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-2-mask)"/>
                            <path d="M52.5357 167.6H27.3357V105.2H120.336V400.2H52.5357V167.6Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-2-mask)"/>
                        </g>
                        
                        <g class="mix-blend-color dark:mix-blend-hard-light transition-all delay-400 opacity-100 duration-750 starting:opacity-0 motion-safe:starting:-translate-x-[51px] text-[#F8B803] dark:text-[#391800]">
                            <mask id="path-3-mask" maskUnits="userSpaceOnUse" x="51" y="103" width="338" height="299" fill="black">
                                <rect fill="white" x="51" y="103" width="338" height="299"/>
                                <path d="M286.264 400.8C255.464 400.8 230.264 392.4 210.664 375.6C191.464 358.8 181.864 337 181.864 310.2H252.064C252.064 318.2 255.064 324.8 261.064 330C267.064 335.2 275.064 337.8 285.064 337.8C294.664 337.8 302.464 335 308.464 329.4C314.864 323.8 318.064 316.6 318.064 307.8C318.064 299.8 315.264 293.2 309.664 288C304.064 282.8 296.864 280.2 288.064 280.2H250.864V218.4H288.064C294.864 218.4 300.664 216 305.464 211.2C310.264 206.4 312.664 200.4 312.664 193.2C312.664 184.8 310.064 178.2 304.864 173.4C299.664 168.6 293.064 166.2 285.064 166.2C277.864 166.2 271.664 168.4 266.464 172.8C261.664 177.2 259.264 182.8 259.264 189.6H192.664C192.664 164.8 201.464 144.6 219.064 129C236.664 113 259.264 105 286.864 105C314.464 105 336.864 112.2 354.064 126.6C371.664 141 380.464 160 380.464 183.6C380.464 200.8 375.864 214.8 366.664 225.6C357.464 236 345.664 243.2 331.264 247.2C348.464 252 362.064 260.2 372.064 271.8C382.464 283.4 387.664 298 387.664 315.6C387.664 340.4 378.264 360.8 359.464 376.8C340.664 392.8 316.264 400.8 286.264 400.8Z"/>
                                <path d="M78.2 167.6H53V105.2H146V400.2H78.2V167.6Z"/>
                            </mask>
                            <path d="M286.264 400.8C255.464 400.8 230.264 392.4 210.664 375.6C191.464 358.8 181.864 337 181.864 310.2H252.064C252.064 318.2 255.064 324.8 261.064 330C267.064 335.2 275.064 337.8 285.064 337.8C294.664 337.8 302.464 335 308.464 329.4C314.864 323.8 318.064 316.6 318.064 307.8C318.064 299.8 315.264 293.2 309.664 288C304.064 282.8 296.864 280.2 288.064 280.2H250.864V218.4H288.064C294.864 218.4 300.664 216 305.464 211.2C310.264 206.4 312.664 200.4 312.664 193.2C312.664 184.8 310.064 178.2 304.864 173.4C299.664 168.6 293.064 166.2 285.064 166.2C277.864 166.2 271.664 168.4 266.464 172.8C261.664 177.2 259.264 182.8 259.264 189.6H192.664C192.664 164.8 201.464 144.6 219.064 129C236.664 113 259.264 105 286.864 105C314.464 105 336.864 112.2 354.064 126.6C371.664 141 380.464 160 380.464 183.6C380.464 200.8 375.864 214.8 366.664 225.6C357.464 236 345.664 243.2 331.264 247.2C348.464 252 362.064 260.2 372.064 271.8C382.464 283.4 387.664 298 387.664 315.6C387.664 340.4 378.264 360.8 359.464 376.8C340.664 392.8 316.264 400.8 286.264 400.8Z" fill="currentColor"/>
                            <path d="M78.2 167.6H53V105.2H146V400.2H78.2V167.6Z" fill="currentColor"/>
                            <path d="M286.264 400.8C255.464 400.8 230.264 392.4 210.664 375.6C191.464 358.8 181.864 337 181.864 310.2H252.064C252.064 318.2 255.064 324.8 261.064 330C267.064 335.2 275.064 337.8 285.064 337.8C294.664 337.8 302.464 335 308.464 329.4C314.864 323.8 318.064 316.6 318.064 307.8C318.064 299.8 315.264 293.2 309.664 288C304.064 282.8 296.864 280.2 288.064 280.2H250.864V218.4H288.064C294.864 218.4 300.664 216 305.464 211.2C310.264 206.4 312.664 200.4 312.664 193.2C312.664 184.8 310.064 178.2 304.864 173.4C299.664 168.6 293.064 166.2 285.064 166.2C277.864 166.2 271.664 168.4 266.464 172.8C261.664 177.2 259.264 182.8 259.264 189.6H192.664C192.664 164.8 201.464 144.6 219.064 129C236.664 113 259.264 105 286.864 105C314.464 105 336.864 112.2 354.064 126.6C371.664 141 380.464 160 380.464 183.6C380.464 200.8 375.864 214.8 366.664 225.6C357.464 236 345.664 243.2 331.264 247.2C348.464 252 362.064 260.2 372.064 271.8C382.464 283.4 387.664 298 387.664 315.6C387.664 340.4 378.264 360.8 359.464 376.8C340.664 392.8 316.264 400.8 286.264 400.8Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-3-mask)"/>
                            <path d="M78.2 167.6H53V105.2H146V400.2H78.2V167.6Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-3-mask)"/>
                        </g>
                        
                        <g class="mix-blend-multiply dark:mix-blend-normal transition-all delay-400 opacity-100 duration-750 starting:opacity-0 motion-safe:starting:-translate-x-[78px] text-[#F3BEC7] dark:text-[#733000]">
                            <mask id="path-4-mask" maskUnits="userSpaceOnUse" x="76.6643" y="103" width="338" height="299" fill="black">
                                <rect fill="white" x="76.6643" y="103" width="338" height="299"/>
                                <path d="M311.929 400.8C281.129 400.8 255.929 392.4 236.329 375.6C217.129 358.8 207.529 337 207.529 310.2H277.729C277.729 318.2 280.729 324.8 286.729 330C292.729 335.2 300.729 337.8 310.729 337.8C320.329 337.8 328.129 335 334.129 329.4C340.529 323.8 343.729 316.6 343.729 307.8C343.729 299.8 340.929 293.2 335.329 288C329.729 282.8 322.529 280.2 313.729 280.2H276.529V218.4H313.729C320.529 218.4 326.329 216 331.129 211.2C335.929 206.4 338.329 200.4 338.329 193.2C338.329 184.8 335.729 178.2 330.529 173.4C325.329 168.6 318.729 166.2 310.729 166.2C303.529 166.2 297.329 168.4 292.129 172.8C287.329 177.2 284.929 182.8 284.929 189.6H218.329C218.329 164.8 227.129 144.6 244.729 129C262.329 113 284.929 105 312.529 105C340.129 105 362.529 112.2 379.729 126.6C397.329 141 406.129 160 406.129 183.6C406.129 200.8 401.529 214.8 392.329 225.6C383.129 236 371.329 243.2 356.929 247.2C374.129 252 387.729 260.2 397.729 271.8C408.129 283.4 413.329 298 413.329 315.6C413.329 340.4 403.929 360.8 385.129 376.8C366.329 392.8 341.929 400.8 311.929 400.8Z"/>
                                <path d="M103.864 167.6H78.6643V105.2H171.664V400.2H103.864V167.6Z"/>
                            </mask>
                            <path d="M311.929 400.8C281.129 400.8 255.929 392.4 236.329 375.6C217.129 358.8 207.529 337 207.529 310.2H277.729C277.729 318.2 280.729 324.8 286.729 330C292.729 335.2 300.729 337.8 310.729 337.8C320.329 337.8 328.129 335 334.129 329.4C340.529 323.8 343.729 316.6 343.729 307.8C343.729 299.8 340.929 293.2 335.329 288C329.729 282.8 322.529 280.2 313.729 280.2H276.529V218.4H313.729C320.529 218.4 326.329 216 331.129 211.2C335.929 206.4 338.329 200.4 338.329 193.2C338.329 184.8 335.729 178.2 330.529 173.4C325.329 168.6 318.729 166.2 310.729 166.2C303.529 166.2 297.329 168.4 292.129 172.8C287.329 177.2 284.929 182.8 284.929 189.6H218.329C218.329 164.8 227.129 144.6 244.729 129C262.329 113 284.929 105 312.529 105C340.129 105 362.529 112.2 379.729 126.6C397.329 141 406.129 160 406.129 183.6C406.129 200.8 401.529 214.8 392.329 225.6C383.129 236 371.329 243.2 356.929 247.2C374.129 252 387.729 260.2 397.729 271.8C408.129 283.4 413.329 298 413.329 315.6C413.329 340.4 403.929 360.8 385.129 376.8C366.329 392.8 341.929 400.8 311.929 400.8Z" fill="currentColor"/>
                            <path d="M103.864 167.6H78.6643V105.2H171.664V400.2H103.864V167.6Z" fill="currentColor"/>
                            <path d="M311.929 400.8C281.129 400.8 255.929 392.4 236.329 375.6C217.129 358.8 207.529 337 207.529 310.2H277.729C277.729 318.2 280.729 324.8 286.729 330C292.729 335.2 300.729 337.8 310.729 337.8C320.329 337.8 328.129 335 334.129 329.4C340.529 323.8 343.729 316.6 343.729 307.8C343.729 299.8 340.929 293.2 335.329 288C329.729 282.8 322.529 280.2 313.729 280.2H276.529V218.4H313.729C320.529 218.4 326.329 216 331.129 211.2C335.929 206.4 338.329 200.4 338.329 193.2C338.329 184.8 335.729 178.2 330.529 173.4C325.329 168.6 318.729 166.2 310.729 166.2C303.529 166.2 297.329 168.4 292.129 172.8C287.329 177.2 284.929 182.8 284.929 189.6H218.329C218.329 164.8 227.129 144.6 244.729 129C262.329 113 284.929 105 312.529 105C340.129 105 362.529 112.2 379.729 126.6C397.329 141 406.129 160 406.129 183.6C406.129 200.8 401.529 214.8 392.329 225.6C383.129 236 371.329 243.2 356.929 247.2C374.129 252 387.729 260.2 397.729 271.8C408.129 283.4 413.329 298 413.329 315.6C413.329 340.4 403.929 360.8 385.129 376.8C366.329 392.8 341.929 400.8 311.929 400.8Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-4-mask)"/>
                            <path d="M103.864 167.6H78.6643V105.2H171.664V400.2H103.864V167.6Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-4-mask)"/>
                        </g>
                        
                        <g class="mix-blend-hard-light transition-all delay-400 opacity-100 duration-750 starting:opacity-0 motion-safe:starting:-translate-x-[102px] text-[#F3BEC7] dark:text-[#4B0600]">
                            <mask id="path-5-mask" maskUnits="userSpaceOnUse" x="102.329" y="103" width="338" height="299" fill="black">
                                <rect fill="white" x="102.329" y="103" width="338" height="299"/>
                                <path d="M337.593 400.8C306.793 400.8 281.593 392.4 261.993 375.6C242.793 358.8 233.193 337 233.193 310.2H303.393C303.393 318.2 306.393 324.8 312.393 330C318.393 335.2 326.393 337.8 336.393 337.8C345.993 337.8 353.793 335 359.793 329.4C366.193 323.8 369.393 316.6 369.393 307.8C369.393 299.8 366.593 293.2 360.993 288C355.393 282.8 348.193 280.2 339.393 280.2H302.193V218.4H339.393C346.193 218.4 351.993 216 356.793 211.2C361.593 206.4 363.993 200.4 363.993 193.2C363.993 184.8 361.393 178.2 356.193 173.4C350.993 168.6 344.393 166.2 336.393 166.2C329.193 166.2 322.993 168.4 317.793 172.8C312.993 177.2 310.593 182.8 310.593 189.6H243.993C243.993 164.8 252.793 144.6 270.393 129C287.993 113 310.593 105 338.193 105C365.793 105 388.193 112.2 405.393 126.6C422.993 141 431.793 160 431.793 183.6C431.793 200.8 427.193 214.8 417.993 225.6C408.793 236 396.993 243.2 382.593 247.2C399.793 252 413.393 260.2 423.393 271.8C433.793 283.4 438.993 298 438.993 315.6C438.993 340.4 429.593 360.8 410.793 376.8C391.993 392.8 367.593 400.8 337.593 400.8Z"/>
                                <path d="M129.529 167.6H104.329V105.2H197.329V400.2H129.529V167.6Z"/>
                            </mask>
                            <path d="M337.593 400.8C306.793 400.8 281.593 392.4 261.993 375.6C242.793 358.8 233.193 337 233.193 310.2H303.393C303.393 318.2 306.393 324.8 312.393 330C318.393 335.2 326.393 337.8 336.393 337.8C345.993 337.8 353.793 335 359.793 329.4C366.193 323.8 369.393 316.6 369.393 307.8C369.393 299.8 366.593 293.2 360.993 288C355.393 282.8 348.193 280.2 339.393 280.2H302.193V218.4H339.393C346.193 218.4 351.993 216 356.793 211.2C361.593 206.4 363.993 200.4 363.993 193.2C363.993 184.8 361.393 178.2 356.193 173.4C350.993 168.6 344.393 166.2 336.393 166.2C329.193 166.2 322.993 168.4 317.793 172.8C312.993 177.2 310.593 182.8 310.593 189.6H243.993C243.993 164.8 252.793 144.6 270.393 129C287.993 113 310.593 105 338.193 105C365.793 105 388.193 112.2 405.393 126.6C422.993 141 431.793 160 431.793 183.6C431.793 200.8 427.193 214.8 417.993 225.6C408.793 236 396.993 243.2 382.593 247.2C399.793 252 413.393 260.2 423.393 271.8C433.793 283.4 438.993 298 438.993 315.6C438.993 340.4 429.593 360.8 410.793 376.8C391.993 392.8 367.593 400.8 337.593 400.8Z" fill="currentColor"/>
                            <path d="M129.529 167.6H104.329V105.2H197.329V400.2H129.529V167.6Z" fill="currentColor"/>
                            <path d="M337.593 400.8C306.793 400.8 281.593 392.4 261.993 375.6C242.793 358.8 233.193 337 233.193 310.2H303.393C303.393 318.2 306.393 324.8 312.393 330C318.393 335.2 326.393 337.8 336.393 337.8C345.993 337.8 353.793 335 359.793 329.4C366.193 323.8 369.393 316.6 369.393 307.8C369.393 299.8 366.593 293.2 360.993 288C355.393 282.8 348.193 280.2 339.393 280.2H302.193V218.4H339.393C346.193 218.4 351.993 216 356.793 211.2C361.593 206.4 363.993 200.4 363.993 193.2C363.993 184.8 361.393 178.2 356.193 173.4C350.993 168.6 344.393 166.2 336.393 166.2C329.193 166.2 322.993 168.4 317.793 172.8C312.993 177.2 310.593 182.8 310.593 189.6H243.993C243.993 164.8 252.793 144.6 270.393 129C287.993 113 310.593 105 338.193 105C365.793 105 388.193 112.2 405.393 126.6C422.993 141 431.793 160 431.793 183.6C431.793 200.8 427.193 214.8 417.993 225.6C408.793 236 396.993 243.2 382.593 247.2C399.793 252 413.393 260.2 423.393 271.8C433.793 283.4 438.993 298 438.993 315.6C438.993 340.4 429.593 360.8 410.793 376.8C391.993 392.8 367.593 400.8 337.593 400.8Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-5-mask)"/>
                            <path d="M129.529 167.6H104.329V105.2H197.329V400.2H129.529V167.6Z" stroke="var(--stroke-color)" stroke-width="2.4" mask="url(#path-5-mask)"/>
                        </g>
                    </svg>
                    <div class="absolute inset-0 rounded-t-lg lg:rounded-t-none lg:rounded-r-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"></div>
                </div>
            </main>
        </div>

        @if (Route::has('login'))
            <div class="h-14.5 hidden lg:block"></div>
        @endif
    </body>
</html>

```
---
