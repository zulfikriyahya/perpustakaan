# Kontrak API Lengkap - WhatsApp Service Gateway

**Base URL API Eksternal:** `https://whatsapp.zedlabs.id/api/v1`
**Base URL Panel/Dashboard:** `https://whatsapp.zedlabs.id`

Dokumen ini disusun langsung dari source code (`internal/router`, `internal/api`, `internal/apikey`, `internal/auth`, `internal/session`, `internal/tenant`, `internal/template`, `internal/sender`, `internal/media`, `internal/guard`, `internal/logging`, `internal/backup`, `internal/profil`), termasuk endpoint yang belum tercakup pada dokumen versi sebelumnya (job antrian batal/stuck, backup/restore, profil pengguna, identitas & logo instansi, endpoint superadmin tambahan).

---

## 1. Ringkasan Otentikasi

| Jenis endpoint | Middleware | Header wajib |
|---|---|---|
| API Eksternal (`/api/v1/...`) | `hmac.Middleware` | `X-API-Key`, `X-Signature`, `X-Timestamp` |
| Panel publik (`/auth/...`) | tidak ada | - |
| Panel terproteksi (`/instansi/...`, `/profil/...`) | `AuthGuard` (+ `TenantIsolation` untuk `/instansi/...`) | `Authorization: Bearer <jwt>` |
| Panel superadmin (`/superadmin/...`) | `AuthGuard` + `RequireSuperadmin` | `Authorization: Bearer <jwt>` (role = `superadmin`) |
| Health check | tidak ada | - |

Format error seragam untuk seluruh endpoint:
```json
{ "error": "pesan error" }
```

**Catatan isolasi tenant:** untuk seluruh endpoint di bawah prefix `/instansi/{instansi_id}/...`, jika pengguna yang login bukan `superadmin` dan `instansi_id` pada path tidak sama dengan `instansi_id` di klaim JWT-nya, request ditolak `403` dengan pesan "tidak diizinkan mengakses data instansi lain" - berlaku sebelum handler endpoint manapun dieksekusi. Superadmin dapat mengakses `instansi_id` manapun tanpa dibatasi ini.

---

## 2. API Eksternal (HMAC) - Untuk Integrasi Aplikasi Luar

Bagian ini yang relevan bagi Anda yang mengintegrasikan sistem eksternal ke gateway.

### 2.1 Cara Otentikasi HMAC

Setiap request ke `/api/v1/*` wajib menyertakan tiga header berikut:

| Header | Isi |
|---|---|
| `X-API-Key` | `key_id` publik yang diberikan admin instansi saat membuat API key |
| `X-Signature` | HMAC-SHA256 dari **body mentah** (raw bytes, sebelum di-parse), di-hex-encode, menggunakan `secret` sebagai kunci |
| `X-Timestamp` | Unix timestamp (detik) saat request dibuat, toleransi ±5 menit dari waktu server |

Aturan verifikasi di server (`internal/hmac`):
- Signature dihitung dari **body persis seperti yang dikirim** (tidak ada normalisasi JSON di sisi server), sehingga sisi klien harus menandatangani string body yang sama persis dengan yang dikirim di request.
- Timestamp yang selisihnya lebih dari 5 menit (baik maju maupun mundur) akan ditolak.
- Setiap signature yang berhasil diverifikasi dicatat sebagai "sudah dipakai" pada tabel internal - signature yang sama tidak bisa dipakai dua kali walau timestamp masih dalam toleransi (anti-replay ditegakkan constraint unik di database, bukan sekadar validasi timestamp).
- API key harus berstatus `aktif`. Jika sudah `nonaktif` (dicabut) atau sedang `auto-pause` oleh guard rail, request ditolak.

**Contoh implementasi (pseudo-code Node.js):**
```javascript
const crypto = require('crypto');

const body = JSON.stringify({
  template_code: "perpustakaan_masuk",
  recipient: "6281234567890",
  variables: { nama_siswa: "Ahmad Fauzi", jam: "07:15" },
  media: null,
  reference_id: "APP-INTERNAL-12345"
});

const timestamp = Math.floor(Date.now() / 1000).toString();
const signature = crypto
  .createHmac('sha256', SECRET)
  .update(body)
  .digest('hex');

fetch('https://whatsapp.zedlabs.id/api/v1/messages', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': KEY_ID,
    'X-Signature': signature,
    'X-Timestamp': timestamp
  },
  body: body
});
```

**Contoh implementasi (pseudo-code PHP):**
```php
$body = json_encode([
    'template_code' => 'perpustakaan_masuk',
    'recipient' => '6281234567890',
    'variables' => ['nama_siswa' => 'Ahmad Fauzi', 'jam' => '07:15'],
    'media' => null,
    'reference_id' => 'APP-INTERNAL-12345',
]);

$timestamp = (string) time();
$signature = hash_hmac('sha256', $body, $secret);

$ch = curl_init('https://whatsapp.zedlabs.id/api/v1/messages');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "X-API-Key: $keyId",
    "X-Signature: $signature",
    "X-Timestamp: $timestamp",
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
```

**Poin penting integrasi:**
- Jangan format ulang/pretty-print JSON setelah menghitung signature - kirim persis string yang sudah ditandatangani.
- Sinkronkan jam server aplikasi Anda (NTP) karena toleransi timestamp hanya ±5 menit.
- Simpan `secret` dengan aman; tidak bisa diambil ulang setelah dibuat (hanya bisa regenerasi baru).
- Signature bersifat sekali pakai; jangan retry dengan signature yang sama - hitung ulang signature baru (timestamp baru) setiap kali retry.

### 2.2 `POST /api/v1/messages`

**Deskripsi:** Mengantre pesan baru untuk dikirim.

**Request Body**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `template_code` | string | ya | Harus terdaftar dan terkait ke `key_id` pemanggil |
| `recipient` | string | ya | Nomor tujuan, format bebas (08xx, 62xx, +62xx) - dinormalisasi ke E.164 |
| `variables` | object | ya (tergantung template) | Wajib mencakup seluruh `variabel_wajib` template |
| `media` | object/null | tidak | Lihat sub-tabel media |
| `reference_id` | string | tidak | Untuk idempotency, window 24 jam |

Sub-objek `media` (jika tidak null):

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `jenis` | string | ya | `dokumen` \| `gambar` \| `video` \| `link` \| `kontak` |
| `url` | string | kondisional | Untuk dokumen/gambar/video: **base64 isi file** (bukan URL eksternal). Untuk link: URL tujuan (harus diawali `http://` atau `https://`) |
| `caption` | string | tidak | Berlaku untuk `jenis: dokumen`, `gambar`, dan `video`. Diabaikan untuk `link`/`kontak` |
| `nama_kontak` | string | kondisional | Wajib jika `jenis: kontak` |
| `nomor_kontak` | string | kondisional | Wajib jika `jenis: kontak` |

Batas ukuran media (sebelum base64 encode): dokumen 15MB, gambar 5MB, video 16MB. MIME yang didukung: PDF/DOCX/XLSX (dokumen), JPEG/PNG/WEBP (gambar), MP4 (video).

Contoh body (teks):
```json
{
  "template_code": "perpustakaan_masuk",
  "recipient": "6281234567890",
  "variables": { "nama_siswa": "Ahmad Fauzi", "jam": "07:15" },
  "media": null,
  "reference_id": "APP-INTERNAL-12345"
}
```

Contoh body (gambar dengan caption):
```json
{
  "template_code": "info_pengumuman",
  "recipient": "081234567890",
  "variables": {},
  "media": { "jenis": "gambar", "url": "<base64>", "caption": "Pengumuman terbaru" }
}
```

Contoh body (dokumen dengan caption):
```json
{
  "template_code": "kirim_rapor",
  "recipient": "081234567890",
  "variables": {},
  "media": { "jenis": "dokumen", "url": "<base64>", "caption": "Rapor semester ini" }
}
```

Contoh body (kontak):
```json
{
  "template_code": "bagikan_kontak",
  "recipient": "081234567890",
  "variables": {},
  "media": { "jenis": "kontak", "nama_kontak": "Admin Sekolah", "nomor_kontak": "6281200000000" }
}
```

**Response Sukses (202 Accepted)**
```json
{ "job_id": "job_abc123", "status": "queued" }
```
Jika `reference_id` sudah pernah dipakai dengan payload identik (idempotency hit), respons **200 OK** dengan `status` sesuai status job yang sudah ada (bisa `queued`, `processing`, `sent`, `delivered`, `read`, `failed`).

**Response Error**

| Kode | Kondisi |
|---|---|
| 400 | Body tidak valid, media tidak valid/gagal decode base64, nomor tujuan tidak valid, variabel wajib tidak lengkap |
| 401 | HMAC gagal (signature/timestamp tidak valid, atau API key tidak ditemukan/nonaktif) |
| 403 | `template_code` tidak ditemukan untuk instansi ini, **atau** ditemukan tapi tidak terkait ke `key_id` pemanggil (kedua kasus dikembalikan dengan pesan yang sama, anti-enumerasi) |
| 409 | `reference_id` sudah dipakai dengan payload berbeda |
| 429 | API key sedang auto-pause oleh guard rail volume |
| 500 | Kegagalan internal (guard rail check, cek template, idempotency, penyimpanan media, pembuatan job) |

**Catatan urutan validasi (penting untuk debugging):** server memvalidasi **media terlebih dahulu** (decode base64, ukuran, MIME, kuota) sebelum memvalidasi kepemilikan `template_code` terhadap API key. Artinya jika `media` maupun `template_code` sama-sama tidak valid pada satu request, respons yang diterima adalah error media (400), bukan error kepemilikan template (403). Kirim ulang setelah memperbaiki media untuk melihat apakah error template juga muncul.

### 2.3 `GET /api/v1/messages/{job_id}`

**Deskripsi:** Mengambil status terkini satu job.

**Response Sukses (200)**
```json
{
  "job_id": "job_abc123",
  "status": "sent",
  "waktu_antre": "2026-07-05T08:00:00Z",
  "waktu_kirim": "2026-07-05T08:00:07Z",
  "keterangan_gagal": ""
}
```
`status` salah satu dari: `queued`, `processing`, `sent`, `delivered`, `read`, `failed`.
`waktu_kirim` dan `keterangan_gagal` dikosongkan (`""`) jika belum relevan. Jika `status = failed`, `keterangan_gagal` berisi salah satu: `session_logged_out`, `session_temp_banned`, `transient`, `validasi`.

**Response Error**

| Kode | Kondisi |
|---|---|
| 401 | HMAC gagal |
| 404 | Job tidak ditemukan atau bukan milik instansi pemanggil (tidak dibedakan, anti-enumerasi) |
| 500 | Kegagalan query internal |

### 2.4 Praktik Integrasi yang Disarankan

- **Polling status:** gunakan `GET /api/v1/messages/{job_id}` dengan interval wajar (misal setiap 5-10 detik), hindari polling agresif per detik.
- **Idempotency:** selalu kirim `reference_id` unik per transaksi bisnis Anda (misal ID transaksi internal) agar retry akibat timeout jaringan tidak menyebabkan pesan terkirim dobel.
- **Media besar:** karena media dikirim sebagai base64 dalam body JSON, ukuran payload HTTP bisa membengkak ~33% dari ukuran file asli - pastikan client HTTP Anda tidak membatasi ukuran body secara default.
- **Nomor tujuan:** kirim apa adanya (08xx, 62xx, atau +62xx), normalisasi E.164 dilakukan otomatis di server.
- **Kegagalan permanen (`validasi`):** kegagalan ini tidak akan di-retry otomatis oleh sistem dan tidak bisa dikirim ulang lewat panel - Anda perlu submit ulang sebagai job baru dengan `reference_id` baru.

---

## 3. Panel - Endpoint Publik

### `POST /auth/login`
| Field | Tipe | Wajib |
|---|---|---|
| `nomor_whatsapp` | string | ya |
| `password` | string | ya |

Response (200): `{ "token": "<jwt>" }`
Error: 400, 401 (pesan sama untuk salah nomor/password, anti-enumerasi), 403 (akun/instansi nonaktif), 429 (akun terkunci sementara akibat gagal login berturut-turut), 500.

### `POST /auth/forgot-password`
| Field | Tipe | Wajib |
|---|---|---|
| `nomor_whatsapp` | string | ya |

Response (200) selalu sama terlepas hasil internal (anti-enumerasi):
```json
{ "status": "jika nomor terdaftar, OTP telah dikirim" }
```

### `POST /auth/reset-password`
| Field | Tipe | Wajib | Validasi |
|---|---|---|---|
| `nomor_whatsapp` | string | ya | - |
| `otp` | string | ya | 6 digit, berlaku 5 menit, maks 5 percobaan, maks 3 permintaan OTP/jam |
| `password_baru` | string | ya | min 8 karakter, kombinasi huruf + angka |

Response (200): `{ "status": "password berhasil diperbarui" }`
Error: 400 (kebijakan password tidak terpenuhi, OTP tidak valid/kedaluwarsa/percobaan habis), 500.

---

## 4. Panel - Terproteksi (`/instansi/{instansi_id}/...`)

`instansi_id` pada path divalidasi terhadap `instansi_id` di klaim JWT (lihat catatan isolasi tenant di Bagian 1) - superadmin dapat mengakses instansi manapun, non-superadmin hanya instansi miliknya sendiri (mismatch → `403`). `pengguna_id` untuk audit selalu diambil dari klaim JWT, bukan dari body/path.

### 4.1 API Key

> **Penting - path parameter `{api_key_id}`:** parameter ini adalah kolom `id` internal (dikembalikan sebagai `id` pada `GET /instansi/{id}/api-key`), **bukan** `key_id` publik (string seperti `key_abc123`) yang ditampilkan ke pengguna. Respons `POST /instansi/{id}/api-key` (create) hanya mengembalikan `key_id` dan `secret` - **tidak** mengembalikan `id` internal. Untuk mendapatkan `id` yang dibutuhkan endpoint di bawah ini, panggil `GET /instansi/{id}/api-key` setelah membuat key dan cocokkan lewat field `key_id`.

| Endpoint | Body | Response Sukses | Error |
|---|---|---|---|
| `POST /instansi/{id}/api-key` | `{ nama, baseline_manual?, sesi_ids? }` | 201 `{ key_id, secret }` (secret tampil sekali) | 400, 500 |
| `GET /instansi/{id}/api-key` | - | 200 array `{ id, key_id, nama, status, dipause_otomatis_pada?, threshold_guard_rail?, dibuat_pada }` | 500 |
| `POST /instansi/{id}/api-key/{api_key_id}/threshold` | `{ threshold: int > 0 }` | 200 `{ status: "threshold_diperbarui" }` | 400, 404, 500 |
| `POST /instansi/{id}/api-key/{api_key_id}/regenerasi` | - | 200 `{ secret }` (baru, tampil sekali) | 404, 500 |
| `POST /instansi/{id}/api-key/{api_key_id}/cabut` | - | 200 `{ status: "dicabut" }` (job queued milik key ini ikut dibatalkan) | 404, 500 |
| `POST /instansi/{id}/api-key/{api_key_id}/aktifkan` | - | 200 `{ status: "aktif" }` | 400 (tidak sedang auto-pause, termasuk jika `api_key_id` tidak ditemukan), 409 (sudah di-revoke permanen), 500 |
| `POST /instansi/{id}/api-key/{api_key_id}/nama` | `{ nama }` | 200 `{ status: "nama_diperbarui" }` | 400, 404, 500 |
| `DELETE /instansi/{id}/api-key/{api_key_id}` | - | 200 `{ status: "dihapus_permanen" }` | 404, 400 (masih aktif, harus dicabut dulu), 409 (punya riwayat job) |

**Catatan `sesi_ids` (pembatasan routing):** jika diisi saat pembuatan API key, job yang dikirim menggunakan key ini **hanya** akan dirutekan ke sesi-sesi yang terdaftar di `sesi_ids` tersebut. Pembatasan ini bersifat ketat - jika seluruh sesi yang diizinkan sedang tidak `ready`, job akan tertahan/gagal (`ErrTidakAdaSesiTersedia`) **tanpa** fallback diam-diam ke sesi lain milik instansi, meskipun sesi lain tersedia. Jika dikosongkan, seluruh sesi `ready` milik instansi menjadi kandidat routing (perilaku default).

### 4.2 Template

| Endpoint | Body | Response Sukses | Error |
|---|---|---|---|
| `POST /instansi/{id}/template` | `{ kode, kategori, prioritas, varian[], variabel_wajib[]? }` | 201 `{ id }` | 400 |
| `GET /instansi/{id}/template` | - | 200 array `{ id, kode, kategori, prioritas, varian[], variabel_wajib[], api_key_id? }` | 500 |
| `PUT /instansi/{id}/template/{template_id}` | sama seperti create | 200 `{ status: "diubah" }` | 400, 404 |
| `DELETE /instansi/{id}/template/{template_id}` | - | 200 `{ status: "dihapus" }` | 404, 500 |
| `POST /instansi/{id}/template/{template_id}/kaitkan` | `{ api_key_id }` | 200 `{ status: "terkait" }` | 400 (kosong/bukan milik instansi), 404, 409 (template sudah terhubung ke API key lain) |
| `DELETE /instansi/{id}/template/{template_id}/kaitkan` | - | 200 `{ status: "terputus" }` | 404, 500 |

Validasi `varian`: 2-10 item, `prioritas` harus `High`/`Medium`/`Low`, setiap varian tidak boleh mengandung tanda seru berurutan (`!!`), lebih dari satu link, atau rasio huruf kapital berlebihan (>70% dalam satu kalimat). Field `api_key_id` pada `kaitkan` merujuk pada `id` internal API key (lihat catatan di 4.1), bukan `key_id`.

### 4.3 Sesi WhatsApp

| Endpoint | Body | Response Sukses | Error |
|---|---|---|---|
| `GET /instansi/{id}/sesi` | - | 200 array sesi (lihat format di bawah) | 500 |
| `POST /instansi/{id}/sesi/qr` | `{ nama }` | 202 `{ sesi_id }` | 400, 500 |
| `GET /instansi/{id}/sesi/{sesi_id}/qr` | - | 200 `{ kode, status: "tersedia" }` atau `{ status: "menunggu" }` | - |
| `POST /instansi/{id}/sesi/pairing` | `{ nomor_tujuan, nama }` | 202 `{ sesi_id, kode_pairing }` | 400, 500 |
| `GET /instansi/{id}/sesi/{sesi_id}/pairing` | - | sama shape dengan `/qr` | - |
| `POST /instansi/{id}/sesi/{sesi_id}/reauth/qr` | - | 202 `{ sesi_id }` | 500 |
| `POST /instansi/{id}/sesi/{sesi_id}/reauth/pairing` | `{ nomor_tujuan }` | 202 `{ kode_pairing }` | 400, 500 |
| `GET /instansi/{id}/sesi/{sesi_id}/reauth/status` | - | 200 `{ status, sesi_status, nomor_dicoba?, waktu? }` | 404, 500 |
| `POST /instansi/{id}/sesi/{sesi_id}/logout` | - | 200 `{ status: "logged_out" }` | 404, 500 |
| `POST /instansi/{id}/sesi/{sesi_id}/lepas-klaim` | - (tanpa body) | 200 `{ status: "klaim_dilepas" }` | 400 (sesi bukan `logged_out`), 500 |
| `DELETE /instansi/{id}/sesi/{sesi_id}` | - | 200 `{ status: "dihapus_permanen" }` | 400 (hanya boleh untuk sesi berstatus `failed`), 500 |
| `GET /instansi/{id}/sesi/statistik` | - | 200 array `{ sesi_id, nama?, jumlah_sejam_terakhir }` | 500 |

Format satu item sesi:
```json
{
  "id": "...", "nomor": "6281...", "nama": "Nomor Utama", "status": "ready",
  "metode_auth": "qr", "label_warmup": "matured",
  "warmup_started_at": "...", "temp_banned_until": "...",
  "dibuat_pada": "..."
}
```
Field opsional dihilangkan dari JSON jika kosong. `status` salah satu dari: `created`, `initializing`, `qr_ready`, `pairing_ready`, `authenticating`, `ready`, `disconnected`, `logged_out`, `temp_banned`, `failed`.

`status` reauth (`reauth/status`) salah satu dari: `menunggu`, `berhasil`, `mismatch_nomor`.

### 4.4 Pengiriman & Antrian Pesan

| Endpoint | Body | Response Sukses | Error |
|---|---|---|---|
| `GET /instansi/{id}/job-antrian` | - | 200 array `{ id, nomor_tujuan, prioritas, status, waktu_antre }` (status `queued`/`processing`) | 500 |
| `GET /instansi/{id}/job-antrian/statistik-status` | - | 200 array `{ status, jumlah }` untuk 24 jam terakhir (selalu 6 baris: queued/processing/sent/delivered/read/failed) | 500 |
| `POST /instansi/{id}/job-antrian/{job_id}/batalkan` | - | 200 `{ status: "dibatalkan" }` | 400 (bukan status queued), 500 |
| `POST /instansi/{id}/job-antrian/batalkan` | `{ job_ids: string[] }` | 200 `{ jumlah_dibatalkan: int }` | 400 (job_ids kosong), 500 |
| `POST /instansi/{id}/job-antrian/{job_id}/tangani-stuck` | - | 200 `{ status: "ditandai_gagal_dan_dikirim_ulang" }` | 400/404/409/429/500 (lihat catatan di bawah) |
| `DELETE /instansi/{id}/job-antrian/{job_id}/stuck` | - | 200 `{ status: "dihapus_permanen" }` | 400/404/409/500 (lihat catatan di bawah - endpoint ini **tidak** mengirim ulang, sehingga 429 guard rail tidak berlaku di sini) |
| `POST /instansi/{id}/log-pesan/{job_id}/kirim-ulang` | - | 200 `{ status: "dikirim_ulang" }` | 400/404/429/500 (lihat catatan) |
| `GET /instansi/{id}/log-pesan` | - | 200 array log (limit 200, terbaru dulu) | 500 |

Catatan job stuck/kirim ulang - kemungkinan error:
- 404: job tidak ditemukan atau bukan milik instansi.
- 400: job bukan berstatus yang diharapkan (`processing` untuk stuck, `failed` untuk kirim ulang), belum melewati ambang waktu stuck (10 menit), kegagalan bertipe `validasi` (tidak boleh dikirim ulang), API key job sudah nonaktif, atau file media job sudah tidak tersedia di disk.
- 409: sesi terkait masih berstatus `ready` (kemungkinan masih aktif diproses worker).
- 429: API key sedang di-guard-rail (auto-pause) - hanya relevan untuk `tangani-stuck` dan `kirim-ulang`, karena keduanya memicu pengiriman ulang; tidak berlaku untuk `DELETE .../stuck`.
- 500: kegagalan internal lainnya.

Format satu item log pesan:
```json
{
  "job_id": "job_abc123",
  "instansi_id": "...",
  "sesi_id": "sesi_xxx",
  "nomor_asal": "6281100000000",
  "aplikasi_asal": "key_xxx",
  "kategori_prioritas": "High",
  "nomor_tujuan": "6281234567890",
  "konten_terkirim": "Kode OTP Anda: 123456...",
  "waktu_antre": "2026-07-05T08:00:00+07:00",
  "waktu_kirim": "2026-07-05T08:00:07+07:00",
  "status_delivery": "sent",
  "jumlah_retry": 0,
  "keterangan_gagal": ""
}
```
`sesi_id`, `nomor_asal`, `konten_terkirim`, `waktu_kirim`, `keterangan_gagal` di-omit jika NULL di database.

**Catatan `nomor_asal`:** meski namanya menyiratkan nomor telepon, field ini akan berisi **nama sesi** (bukan nomornya) jika sesi tersebut punya nama yang diisi saat registrasi - nomor mentah hanya ditampilkan jika sesi tidak punya nama. Jangan mem-parsing field ini sebagai nomor telepon secara mutlak.

### 4.5 Media & Nomor Ditandai

| Endpoint | Body | Response Sukses | Error |
|---|---|---|---|
| `GET /instansi/{id}/kuota-media` | - | 200 `{ terpakai_bytes, kuota_bytes, persen }` | 500 |
| `GET /instansi/{id}/nomor-ditandai` | - | 200 array `{ nomor, status_blokir, status_responsif }` | 500 |
| `POST /instansi/{id}/nomor/pulihkan` | `{ nomor }` | 200 `{ status: "dipulihkan" }` | 400, 500 |

### 4.6 Identitas & Backup Instansi

| Endpoint | Body | Response Sukses | Error |
|---|---|---|---|
| `GET /instansi/{id}/identitas` | - | 200 `{ nama, kuota_media_bytes, punya_logo }` | 500 |
| `PUT /instansi/{id}/identitas` | `{ nama }` | 200 `{ status: "identitas_diperbarui" }` | 400, 500 |
| `POST /instansi/{id}/identitas/logo` | raw bytes (maks 500KB efektif; body >600KB ditolak lebih awal oleh batas baca server), JPEG/PNG/WEBP | 200 `{ status: "logo_diperbarui" }` | 400, 500 |
| `GET /instansi/{id}/identitas/logo` | - | 200 raw image bytes | 404 (belum ada logo), 500 |
| `GET /instansi/{id}/backup` | - | 200 file JSON terlampir (`Content-Disposition: attachment`) berisi template, api-key, sesi, log pesan 90 hari terakhir | 500 |
| `POST /instansi/{id}/restore` | file backup JSON (`versi: 1`) | 200 ringkasan `{ template_ditambah, template_dilewati, api_key_ditambah, api_key_dilewati, sesi_ditambah, sesi_dilewati, log_ditambah, log_dilewati }` | 400 (body bukan JSON valid, atau field `versi` bukan `1`), 500 |

**Catatan restore:** API key yang direstore mendapat `secret` **baru/placeholder** yang digenerate ulang oleh server (secret asli tidak bisa dipulihkan dari backup karena terenkripsi khusus untuk instansi sumber) - wajib regenerasi secret manual pasca-impor sebelum API key hasil restore bisa dipakai. Sesi hasil impor selalu berstatus `logged_out` (kredensial WhatsApp tidak ikut ter-backup) dan wajib re-registrasi (scan QR/pairing ulang).

---

## 5. Panel - Profil Pengguna (`/profil/...`)

| Endpoint | Body | Response Sukses | Error |
|---|---|---|---|
| `GET /profil/saya` | - | 200 `{ id, nama, role, instansi_id?, punya_avatar }` | 500 |
| `PUT /profil/nama` | `{ nama }` | 200 `{ status: "nama_diperbarui" }` | 400, 500 |
| `POST /profil/avatar` | raw bytes (maks 500KB efektif; body >600KB ditolak lebih awal oleh batas baca server), JPEG/PNG/WEBP | 200 `{ status: "avatar_diperbarui" }` | 400, 500 |
| `GET /profil/avatar/{pengguna_id}` | - | 200 raw image bytes | 403 (bukan diri sendiri & bukan superadmin), 404, 500 |
| `PUT /profil/password` | `{ password_lama, password_baru }` | 200 `{ status: "password berhasil diperbarui" }` | 400 (password lama salah / kebijakan password baru tidak terpenuhi), 500 |

---

## 6. Panel - Superadmin (`/superadmin/...`)

### 6.1 Instansi

| Endpoint | Body | Response Sukses | Error |
|---|---|---|---|
| `POST /superadmin/instansi` | `{ nama, kuota_media_bytes? }` | 201 `{ instansi_id }` (otomatis dibuatkan template starter 10 kategori) | 400, 500 |
| `GET /superadmin/instansi` | query `?q=` (cari nama, opsional) | 200 array `{ id, nama, status, kuota_media_bytes, punya_logo, nonaktif_sejak? }` | 500 |
| `GET /superadmin/instansi/{id}/logo` | - | 200 raw image bytes | 404, 500 |
| `POST /superadmin/instansi/{id}/nonaktifkan` | - | 200 `{ status: "nonaktif" }` | 500 |
| `POST /superadmin/instansi/{id}/aktifkan` | - | 200 `{ status: "aktif" }` | 500 |
| `POST /superadmin/instansi/{id}/kuota` | `{ kuota_media_bytes: int > 0 }` | 200 `{ status: "kuota_diperbarui" }` | 400, 500 |
| `DELETE /superadmin/instansi/{id}` | - | 200 `{ status: "dihapus_permanen" }` | 404, 400 (masih aktif), 409 (punya riwayat job) |

### 6.2 Admin Instansi

| Endpoint | Body | Response Sukses | Error |
|---|---|---|---|
| `POST /superadmin/instansi/{id}/admin` | `{ nama, nomor_whatsapp }` | 201 `{ admin_id, notifikasi_terkirim: bool }` | 400, 409 (nomor sudah terdaftar), 500 |
| `GET /superadmin/instansi/{id}/admin` | - | 200 array `{ id, nama, nomor_whatsapp, status, punya_avatar, dibuat_pada }` | 500 |
| `POST /superadmin/instansi/{id}/admin/{admin_id}/nonaktifkan` | - | 200 `{ status: "nonaktif" }` | 404, 500 |
| `POST /superadmin/instansi/{id}/admin/{admin_id}/aktifkan` | - | 200 `{ status: "aktif" }` | 404, 500 |
| `DELETE /superadmin/instansi/{id}/admin/{admin_id}` | - | 200 `{ status: "dihapus_permanen" }` | 404, 400 (masih aktif), 500 |

Catatan: password awal admin instansi digenerate otomatis dan dikirim via WhatsApp (superadmin sender fallback), **tidak pernah** dikembalikan di response API. Jika pengiriman gagal, akun tetap dibuat dan `notifikasi_terkirim: false`.

### 6.3 Audit & Media

| Endpoint | Response Sukses | Error |
|---|---|---|
| `GET /superadmin/audit-log` | 200 array 500 baris terbaru `{ id, pengguna_id?, aksi, entitas_terkait_id?, nilai_sebelum?, nilai_sesudah?, waktu }` | 500 |
| `POST /superadmin/media/retensi-manual` | 200 `{ status: "retensi_selesai" }` (trigger retensi media sinkron - request bisa memakan waktu tergantung jumlah file kandidat) | 500 |

### 6.4 Sesi Superadmin

| Endpoint | Body | Response Sukses | Error |
|---|---|---|---|
| `GET /superadmin/sesi` | - | 200 array sesi (shape sama seperti sesi instansi, `instansi_id` NULL) | 500 |
| `POST /superadmin/sesi/qr` | `{ nama }` | 202 `{ sesi_id }` | 400, 500 |
| `POST /superadmin/sesi/pairing` | `{ nomor_tujuan, nama }` | 202 `{ sesi_id, kode_pairing }` | 400, 500 |
| `POST /superadmin/sesi/{sesi_id}/lepas-klaim` | `{ masa_tunggu_hari? }` (default 30 jika ≤0) | 200 `{ status: "klaim_dilepas" }` | 400 (bukan `logged_out`, instansi belum nonaktif, atau masa tunggu belum terlampaui), 500 |
| `GET /superadmin/sesi/warning` | - | 200 `{ jumlah_ready: int, warning: bool }` (`warning=true` jika ready < 2) | - |
| `GET /superadmin/sesi/kandidat-lepas-klaim` | - | 200 array `{ sesi_id, nomor, instansi_id, instansi_nama, nonaktif_sejak, hari_nonaktif_berjalan }` | 500 |
| `DELETE /superadmin/sesi/{sesi_id}` | - | 200 `{ status: "dihapus_permanen" }` | 400 (hanya untuk sesi milik superadmin berstatus `logged_out`/`failed`), 500 |

Catatan: `POST /superadmin/sesi/qr` dan `/pairing` memakai handler yang sama persis dengan versi instansi (hanya `instansi_id` dikosongkan/NULL), sehingga validasi `nama`/`nomor_tujuan` wajib diisi (400) berlaku identik.

---

## 7. Tanpa Otentikasi

### `GET /healthz`
Response (200 jika sehat, 503 jika DB tidak terhubung):
```json
{ "proses_hidup": true, "database_terhubung": true, "jumlah_sesi_ready": 4 }
```

---

## 8. Referensi Nilai Enum

| Domain | Nilai yang mungkin |
|---|---|
| Status job pesan | `queued`, `processing`, `sent`, `delivered`, `read`, `failed` |
| Keterangan gagal job | `session_logged_out`, `session_temp_banned`, `transient`, `validasi` |
| Prioritas template/job | `High`, `Medium`, `Low` |
| Jenis pesan/media | `teks`, `dokumen`, `gambar`, `video`, `link`, `kontak` |
| Status sesi WhatsApp | `created`, `initializing`, `qr_ready`, `pairing_ready`, `authenticating`, `ready`, `disconnected`, `logged_out`, `temp_banned`, `failed` |
| Metode auth sesi | `qr`, `pairing` |
| Label warmup sesi | `warming_up`, `matured` |
| Status API key | `aktif`, `nonaktif` |
| Status instansi | `aktif`, `nonaktif` |
| Status blokir nomor | `normal`, `possibly_blocked` |
| Status responsif nomor | `responsif`, `tidak_responsif` |
| Role pengguna | `superadmin`, `admin_instansi` |
| Status reauth | `menunggu`, `berhasil`, `mismatch_nomor` |

---

## 9. Catatan Rate Limit dan Guard Rail (Relevan untuk Integrator)

- **Guard rail volume per API key:** setiap API key punya baseline volume harian (manual saat pembuatan, atau otomatis dihitung dari rata-rata 7 hari terakhir setelah 7 hari berjalan). Jika jumlah request dalam 1 jam terakhir melebihi `threshold = baseline x 3`, API key otomatis di-auto-pause dan semua request baru ditolak dengan `429` sampai admin instansi mengaktifkan kembali secara manual.
- **Rate pengiriman aktual ke WhatsApp:** dikontrol di level worker (bukan di level API), menyesuaikan jumlah sesi aktif dan status warm-up nomor - ini tidak memengaruhi response `202` dari `POST /api/v1/messages` (job tetap diterima dan diantre), hanya memengaruhi kecepatan job berpindah dari `queued` ke `sent`.
- **Idempotency window:** 24 jam sejak `waktu_antre` job pertama dengan `reference_id` yang sama.
- **Prioritas Low pada sesi warm-up:** dibatasi volume harian bertahap - 50/hari (hari 1-3), 100/hari (hari 4-7), 200/hari (hari 8-10), 350/hari (mulai hari ke-11); jika limit tercapai dan tidak ada sesi lain yang tersedia, job Low akan tertahan di `queued` menunggu slot berikutnya, bukan gagal. Prioritas `High`/`Medium` **tidak** dibatasi oleh limit warm-up ini.
- **Pembatasan sesi per API key (`sesi_ids`):** jika API key dikonfigurasi dengan `sesi_ids` spesifik saat dibuat (lihat 4.1), guard rail dan warm-up tetap berlaku normal, namun pemilihan sesi pengiriman dibatasi ketat ke sesi-sesi tersebut saja.

---

## 10. Integrasi dengan Golang

### 10.1 Client HMAC Lengkap

```go
package waclient

import (
	"bytes"
	"context"
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strconv"
	"time"
)

type Client struct {
	BaseURL    string
	APIKeyID   string
	Secret     string
	HTTPClient *http.Client
}

func NewClient(baseURL, apiKeyID, secret string) *Client {
	return &Client{
		BaseURL:    baseURL,
		APIKeyID:   apiKeyID,
		Secret:     secret,
		HTTPClient: &http.Client{Timeout: 15 * time.Second},
	}
}

type MediaInput struct {
	Jenis       string `json:"jenis"`
	URL         string `json:"url,omitempty"`
	Caption     string `json:"caption,omitempty"`
	NamaKontak  string `json:"nama_kontak,omitempty"`
	NomorKontak string `json:"nomor_kontak,omitempty"`
}

type KirimPesanRequest struct {
	TemplateCode string                 `json:"template_code"`
	Recipient    string                 `json:"recipient"`
	Variables    map[string]interface{} `json:"variables"`
	Media        *MediaInput            `json:"media"`
	ReferenceID  string                 `json:"reference_id,omitempty"`
}

type KirimPesanResponse struct {
	JobID  string `json:"job_id"`
	Status string `json:"status"`
}

type StatusResponse struct {
	JobID           string `json:"job_id"`
	Status          string `json:"status"`
	WaktuAntre      string `json:"waktu_antre"`
	WaktuKirim      string `json:"waktu_kirim,omitempty"`
	KeteranganGagal string `json:"keterangan_gagal,omitempty"`
}

type ErrorResponse struct {
	Error string `json:"error"`
}

// signAndSend membangun header HMAC, mengirim request, dan mengembalikan
// body mentah beserta status code untuk diproses pemanggil.
func (c *Client) signAndSend(ctx context.Context, method, path string, bodyBytes []byte) (int, []byte, error) {
	req, err := http.NewRequestWithContext(ctx, method, c.BaseURL+path, bytes.NewReader(bodyBytes))
	if err != nil {
		return 0, nil, err
	}

	timestamp := strconv.FormatInt(time.Now().Unix(), 10)
	mac := hmac.New(sha256.New, []byte(c.Secret))
	mac.Write(bodyBytes)
	signature := hex.EncodeToString(mac.Sum(nil))

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-API-Key", c.APIKeyID)
	req.Header.Set("X-Signature", signature)
	req.Header.Set("X-Timestamp", timestamp)

	resp, err := c.HTTPClient.Do(req)
	if err != nil {
		return 0, nil, err
	}
	defer resp.Body.Close()

	respBody, err := io.ReadAll(resp.Body)
	if err != nil {
		return 0, nil, err
	}
	return resp.StatusCode, respBody, nil
}

// KirimPesan mengirim satu job pesan baru.
// Catatan: signature dihitung dari bodyBytes hasil json.Marshal persis
// seperti yang dikirim -- jangan format ulang body setelah ini.
func (c *Client) KirimPesan(ctx context.Context, req KirimPesanRequest) (*KirimPesanResponse, error) {
	bodyBytes, err := json.Marshal(req)
	if err != nil {
		return nil, err
	}

	status, respBody, err := c.signAndSend(ctx, http.MethodPost, "/api/v1/messages", bodyBytes)
	if err != nil {
		return nil, err
	}

	if status != http.StatusAccepted && status != http.StatusOK {
		var errResp ErrorResponse
		_ = json.Unmarshal(respBody, &errResp)
		return nil, fmt.Errorf("gateway mengembalikan status %d: %s", status, errResp.Error)
	}

	var out KirimPesanResponse
	if err := json.Unmarshal(respBody, &out); err != nil {
		return nil, err
	}
	return &out, nil
}

// AmbilStatus mengambil status job. GET tanpa body tetap wajib
// ditandatangani -- body kosong tetap di-HMAC (bodyBytes panjang 0).
func (c *Client) AmbilStatus(ctx context.Context, jobID string) (*StatusResponse, error) {
	status, respBody, err := c.signAndSend(ctx, http.MethodGet, "/api/v1/messages/"+jobID, []byte{})
	if err != nil {
		return nil, err
	}

	if status != http.StatusOK {
		var errResp ErrorResponse
		_ = json.Unmarshal(respBody, &errResp)
		return nil, fmt.Errorf("gateway mengembalikan status %d: %s", status, errResp.Error)
	}

	var out StatusResponse
	if err := json.Unmarshal(respBody, &out); err != nil {
		return nil, err
	}
	return &out, nil
}
```

### 10.2 Contoh Pemakaian

```go
package main

import (
	"context"
	"log"
	"time"

	"yourapp/waclient"
)

func main() {
	client := waclient.NewClient(
		"https://whatsapp.zedlabs.id",
		"key_abc123",
		"secret-rahasia-dari-panel",
	)

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	resp, err := client.KirimPesan(ctx, waclient.KirimPesanRequest{
		TemplateCode: "perpustakaan_masuk",
		Recipient:    "6281234567890",
		Variables: map[string]interface{}{
			"nama_siswa": "Ahmad Fauzi",
			"jam":        "07:15",
		},
		Media:       nil,
		ReferenceID: "APP-INTERNAL-12345",
	})
	if err != nil {
		log.Fatalf("gagal mengirim pesan: %v", err)
	}
	log.Printf("job dibuat: %s status: %s", resp.JobID, resp.Status)

	// Polling status setelah beberapa detik
	time.Sleep(5 * time.Second)
	statusCtx, cancelStatus := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancelStatus()

	statusResp, err := client.AmbilStatus(statusCtx, resp.JobID)
	if err != nil {
		log.Fatalf("gagal mengambil status: %v", err)
	}
	log.Printf("status job %s: %s", statusResp.JobID, statusResp.Status)
}
```

### 10.3 Contoh Kirim Gambar (Base64) di Golang

```go
package main

import (
	"context"
	"encoding/base64"
	"log"
	"os"

	"yourapp/waclient"
)

func kirimGambar(client *waclient.Client, path, recipient string) {
	data, err := os.ReadFile(path)
	if err != nil {
		log.Fatalf("gagal membaca file: %v", err)
	}
	encoded := base64.StdEncoding.EncodeToString(data)

	ctx := context.Background()
	resp, err := client.KirimPesan(ctx, waclient.KirimPesanRequest{
		TemplateCode: "info_pengumuman",
		Recipient:    recipient,
		Variables:    map[string]interface{}{},
		Media: &waclient.MediaInput{
			Jenis:   "gambar",
			URL:     encoded,
			Caption: "Pengumuman terbaru",
		},
	})
	if err != nil {
		log.Fatalf("gagal mengirim gambar: %v", err)
	}
	log.Printf("job gambar dibuat: %s", resp.JobID)
}
```

### 10.4 Idempotency Retry dengan Backoff (Golang)

```go
package waclient

import (
	"context"
	"time"
)

// KirimPesanDenganRetry mencoba ulang saat terjadi error jaringan/5xx,
// dengan reference_id yang sama sehingga aman dari duplikasi pesan
// (lihat idempotency window 24 jam pada Bagian 9).
func (c *Client) KirimPesanDenganRetry(ctx context.Context, req KirimPesanRequest, maxPercobaan int) (*KirimPesanResponse, error) {
	var lastErr error
	delay := 500 * time.Millisecond

	for i := 0; i < maxPercobaan; i++ {
		resp, err := c.KirimPesan(ctx, req)
		if err == nil {
			return resp, nil
		}
		lastErr = err

		select {
		case <-ctx.Done():
			return nil, ctx.Err()
		case <-time.After(delay):
		}
		delay *= 2
	}
	return nil, lastErr
}
```

---

## 11. Integrasi dengan Python

### 11.1 Client HMAC Lengkap

```python
import hashlib
import hmac
import json
import time
from typing import Optional, Dict, Any

import requests


class WAGatewayError(Exception):
    def __init__(self, status_code: int, message: str):
        self.status_code = status_code
        self.message = message
        super().__init__(f"gateway mengembalikan status {status_code}: {message}")


class WAGatewayClient:
    def __init__(self, base_url: str, api_key_id: str, secret: str, timeout: int = 15):
        self.base_url = base_url.rstrip("/")
        self.api_key_id = api_key_id
        self.secret = secret.encode("utf-8")
        self.timeout = timeout
        self.session = requests.Session()

    def _sign(self, body_bytes: bytes) -> Dict[str, str]:
        timestamp = str(int(time.time()))
        signature = hmac.new(self.secret, body_bytes, hashlib.sha256).hexdigest()
        return {
            "Content-Type": "application/json",
            "X-API-Key": self.api_key_id,
            "X-Signature": signature,
            "X-Timestamp": timestamp,
        }

    def _request(self, method: str, path: str, body_bytes: bytes) -> Dict[str, Any]:
        headers = self._sign(body_bytes)
        url = f"{self.base_url}{path}"

        resp = self.session.request(
            method, url, data=body_bytes, headers=headers, timeout=self.timeout
        )

        try:
            payload = resp.json()
        except ValueError:
            payload = {}

        if resp.status_code not in (200, 202):
            raise WAGatewayError(resp.status_code, payload.get("error", resp.text))

        return payload

    def kirim_pesan(
        self,
        template_code: str,
        recipient: str,
        variables: Dict[str, Any],
        media: Optional[Dict[str, Any]] = None,
        reference_id: Optional[str] = None,
    ) -> Dict[str, Any]:
        body = {
            "template_code": template_code,
            "recipient": recipient,
            "variables": variables,
            "media": media,
        }
        if reference_id:
            body["reference_id"] = reference_id

        # separators rapat agar body yang di-hash sama persis dengan yang dikirim
        body_bytes = json.dumps(body, separators=(",", ":")).encode("utf-8")
        return self._request("POST", "/api/v1/messages", body_bytes)

    def ambil_status(self, job_id: str) -> Dict[str, Any]:
        return self._request("GET", f"/api/v1/messages/{job_id}", b"")
```

Catatan penting: gunakan `json.dumps(..., separators=(",", ":"))` agar tidak ada spasi tambahan yang mengubah representasi byte body -- signature dihitung dari body persis yang dikirim lewat `data=body_bytes`, bukan lewat parameter `json=` milik `requests` (yang bisa memformat ulang secara berbeda).

### 11.2 Contoh Pemakaian

```python
from wagateway import WAGatewayClient, WAGatewayError

client = WAGatewayClient(
    base_url="https://whatsapp.zedlabs.id",
    api_key_id="key_abc123",
    secret="secret-rahasia-dari-panel",
)

try:
    hasil = client.kirim_pesan(
        template_code="perpustakaan_masuk",
        recipient="6281234567890",
        variables={"nama_siswa": "Ahmad Fauzi", "jam": "07:15"},
        reference_id="APP-INTERNAL-12345",
    )
    print("job dibuat:", hasil["job_id"], hasil["status"])

    status = client.ambil_status(hasil["job_id"])
    print("status job:", status["status"])

except WAGatewayError as e:
    print(f"gagal: {e.status_code} - {e.message}")
```

### 11.3 Contoh Kirim Dokumen (Base64) di Python

```python
import base64

def kirim_dokumen(client: WAGatewayClient, path: str, recipient: str):
    with open(path, "rb") as f:
        encoded = base64.b64encode(f.read()).decode("utf-8")

    hasil = client.kirim_pesan(
        template_code="kirim_rapor",
        recipient=recipient,
        variables={},
        media={"jenis": "dokumen", "url": encoded, "caption": "Rapor semester ini"},
    )
    print("job dokumen dibuat:", hasil["job_id"])
```

### 11.4 Contoh Kirim Kontak di Python

```python
def kirim_kontak(client: WAGatewayClient, recipient: str):
    hasil = client.kirim_pesan(
        template_code="bagikan_kontak",
        recipient=recipient,
        variables={},
        media={
            "jenis": "kontak",
            "nama_kontak": "Admin Sekolah",
            "nomor_kontak": "6281200000000",
        },
    )
    print("job kontak dibuat:", hasil["job_id"])
```

### 11.5 Polling Status dengan Backoff (Python)

```python
import time

def tunggu_hasil(client: WAGatewayClient, job_id: str, maks_detik: int = 60) -> dict:
    """Poll status job sampai berstatus final (sent/delivered/read/failed)
    atau timeout maks_detik tercapai."""
    status_final = {"sent", "delivered", "read", "failed"}
    interval = 3
    waktu_berjalan = 0

    while waktu_berjalan < maks_detik:
        hasil = client.ambil_status(job_id)
        if hasil["status"] in status_final:
            return hasil
        time.sleep(interval)
        waktu_berjalan += interval
        interval = min(interval * 1.5, 10)

    raise TimeoutError(f"job {job_id} belum final setelah {maks_detik} detik")
```

### 11.6 Retry dengan Idempotency (Python)

```python
def kirim_pesan_dengan_retry(
    client: WAGatewayClient,
    template_code: str,
    recipient: str,
    variables: dict,
    reference_id: str,
    maks_percobaan: int = 3,
) -> dict:
    """Aman untuk retry karena reference_id sama -- lihat idempotency
    window 24 jam pada Bagian 9 kontrak API."""
    delay = 0.5
    error_terakhir = None

    for _ in range(maks_percobaan):
        try:
            return client.kirim_pesan(
                template_code=template_code,
                recipient=recipient,
                variables=variables,
                reference_id=reference_id,
            )
        except WAGatewayError as e:
            error_terakhir = e
            if e.status_code == 409:
                # payload berbeda dengan reference_id yang sama -- retry tidak akan membantu
                raise
            time.sleep(delay)
            delay *= 2

    raise error_terakhir
```

---

## 12. Ringkasan Perbedaan Penting Antara Dua Bahasa

| Aspek | Golang | Python |
|---|---|---|
| Perhitungan HMAC | `crypto/hmac` + `crypto/sha256`, hex encode via `encoding/hex` | `hmac` + `hashlib.sha256`, `.hexdigest()` |
| Serialisasi body sebelum sign | `json.Marshal` (default sudah tanpa spasi tambahan) | wajib `json.dumps(..., separators=(",", ":"))` agar tidak ada spasi setelah `:` dan `,` |
| Pengiriman body yang sudah ditandatangani | `bytes.NewReader(bodyBytes)` sebagai body request | `data=body_bytes` (bukan parameter `json=`) agar `requests` tidak memformat ulang |
| Timeout | `context.WithTimeout` | parameter `timeout=` pada `requests` |
| Retry idempotent | ulang `KirimPesan` dengan `ReferenceID` sama | ulang `kirim_pesan` dengan `reference_id` sama, kecuali status 409 |

Prinsip inti di kedua bahasa sama: **hitung signature dari body persis dalam bentuk byte yang akan dikirim**, bukan dari struktur data sebelum diserialisasi ulang, karena perbedaan spasi/urutan key akan menghasilkan signature yang berbeda dan ditolak server dengan `401`.

---
