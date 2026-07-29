# DOKUMEN LOGIC MODULE — APLIKASI PERPUSTAKAAN
**Versi:** Final Draft v1.0
**Stack:** Laravel + laravel-shift/blueprint + Filament

---

## 1. Role & Permission

| Role | Scope Akses |
|---|---|
| **Siswa** | Meminjam buku, lihat point/badge/histori pribadi, terima notifikasi WA |
| **Pegawai** | Sama seperti Siswa (aturan disamakan penuh) |
| **Pustakawan** | Operasional harian: proses peminjaman, pengembalian, input kondisi buku, kelola master data (Buku, Rak, Kategori) |
| **Admin** | Konfigurasi sistem: Settings (limit peminjaman, aturan point, threshold badge/reward/punishment, tarif denda, notifikasi, jam operasional, API endpoint) |

> **Catatan pengembangan:** Gunakan Filament Shield atau custom Policy per Resource. Sidebar menu menyesuaikan role via `canAccess()` di tiap Resource.

---

## 2. Aturan Peminjaman

- **Limit**: berbasis **Peminjaman berstatus aktif**, bukan histori total.
- Rumus validasi:
  ```
  count(Peminjaman WHERE user_id = X AND status = 'aktif') < Setting('max_peminjaman_aktif')
  ```
- **Blokir otomatis**: user dengan `Denda.status_lunas = false` (tipe apapun) → `User.status_suspend = true` → tidak bisa membuat Peminjaman baru sampai semua Denda lunas.
- **Alur transaksi peminjaman (fisik)**:
  ```
  User tap kartu RFID (identifikasi)
    → Scan barcode Buku (bisa multi-buku dalam 1 transaksi)
    → Submit
    → 1 Transaksi (jenis: peminjaman) dibuat
    → N Peminjaman dibuat (1 per buku), status = 'aktif'
  ```

---

## 3. State Machine — Peminjaman

```
                    ┌───────────────────────────────────────┐
                    │              [AKTIF]                   │
                    └───────────────────────────────────────┘
                          │                    │
          (cron: lewat jatuh tempo)   (dikembalikan on-time, kondisi baik)
                          │                    │
                          ▼                    ▼
                    [TERLAMBAT]            [SELESAI]
                          │
        ┌─────────────────┼─────────────────┐
 (dikembalikan,     (kondisi rusak)   (dilaporkan hilang)
  kondisi baik)            │                  │
        │                  ▼                  ▼
        ▼            [SELESAI]           [HILANG]  ← final state
   [SELESAI]         + Denda kerusakan   + Denda kehilangan
   + Denda keterlambatan
   (final, hitung hari telat)
```

**Aturan transisi:**
| Dari | Ke | Trigger | Efek |
|---|---|---|---|
| Aktif | Terlambat | Cron harian, `tanggal_jatuh_tempo` terlewati | Notif WA + mulai hitung denda berjalan |
| Aktif | Selesai | Pengembalian on-time, kondisi baik | - |
| Terlambat | Selesai | Pengembalian, kondisi baik | Hitung & catat Denda keterlambatan final |
| Aktif/Terlambat | Selesai | Pengembalian, kondisi rusak | Catat Denda kerusakan (dari `harga_ganti` Buku) |
| Aktif/Terlambat | Hilang | Dilaporkan hilang | Catat Denda kehilangan (dari `harga_ganti` penuh), status final (tidak lanjut ke Selesai) |

---

## 4. Sistem Point, Badge, Reward, Punishment

**Alur otomatis:**
```
Event terjadi (Kunjungan / Peminjaman / Pengembalian / Kerusakan / Kehilangan)
  → Insert log Point (nilai dari Setting, bisa negatif)
  → Update User.akumulasi_point
  → Cek threshold LevelBadge → update User.level_badge_id jika naik level
  → Cek threshold Reward → jika tercapai: insert RewardLog + kirim notif WA
  → Cek threshold Punishment (point minus) → jika tercapai: insert PunishmentLog + apply suspend + kirim notif WA
```

- **Point** = tabel log (histori per event), bukan saldo langsung.
- **Nilai point per event** dikonfigurasi di Settings (Admin), termasuk nilai minus untuk Kerusakan/Kehilangan.
- **LevelBadge** = threshold berjenjang (`min_point`–`max_point` → `nama_badge`).
- **Reward** & **Punishment** = master rule (threshold + deskripsi), **RewardLog** & **PunishmentLog** = histori realisasi per user.
- Semua trigger **otomatis oleh sistem**, tanpa approval manual.

---

## 5. Denda

Satu tabel `Denda`, dibedakan lewat kolom `tipe`:

| Tipe | Formula | Sumber Input |
|---|---|---|
| `keterlambatan` | `(tanggal_kembali_aktual - tanggal_jatuh_tempo) × tarif_per_hari` | Otomatis saat proses pengembalian |
| `kerusakan` | Dari `Buku.harga_ganti` (nilai penuh/persentase, ditentukan Setting) | Input manual Pustakawan saat cek kondisi |
| `kehilangan` | Dari `Buku.harga_ganti` (penuh) | Input manual Pustakawan/otomatis saat status jadi Hilang |

- Setiap Denda punya `status_lunas` → mempengaruhi `User.status_suspend`.
- Saat semua Denda user lunas → `status_suspend` otomatis kembali `false`.

---

## 6. RFID Visitor Counter

- Device menyimpan tap di memori lokal (SD card), **reset hanya setelah ack sukses** dari server.
- Sinkronisasi ke aplikasi setiap interval (default 5 menit, configurable via Setting).
- **Validasi dilakukan di device (real-time saat tap)**:
  1. Unik per hari per user (abaikan tap ganda).
  2. Dalam jam operasional (`jam_buka`–`jam_tutup` dari Setting, disinkronkan ke device).
  3. **Tap di luar jam operasional → ditolak total**, tidak masuk log sama sekali.
- Tap valid → kirim ke server → insert `Kunjungan` → trigger Point (event `kunjungan`).
- **Prasyarat teknis**: device wajib punya RTC akurat; jam operasional harus di-push dari aplikasi ke device (bukan hanya kirim satu arah).

---

## 7. Rak, Kategori, Buku

- `Buku` ↔ `Kategori` → **many-to-many**
- `Rak` ↔ `Kategori` → **many-to-many**
- `Buku` → `Rak` → **one-to-many** (1 buku fisik = 1 lokasi rak)

---

## 8. Notifikasi WhatsApp (Trigger Mapping)

| Event | Trigger | Isi Notifikasi |
|---|---|---|
| Peminjaman Aktif | Submit sukses | Daftar buku + tanggal jatuh tempo |
| Reminder H-3 | Cron pagi hari | Pengingat jatuh tempo (3 hari lagi) |
| Reminder H-1 | Cron pagi hari | Pengingat jatuh tempo (besok) |
| Jadi Terlambat | Cron pagi hari (H+1) | Peringatan + info denda mulai berjalan |
| Pengembalian diproses | Manual oleh Pustakawan | Konfirmasi + kondisi buku |
| Denda dibuat/final | Saat pengembalian/lapor hilang | Jenis + nominal + (jika telat: jumlah hari) |
| Denda lunas | Pembayaran dikonfirmasi | Konfirmasi pelunasan + status unblock |
| Badge naik | Trigger akumulasi point | Info badge baru |
| Reward didapat | Trigger threshold | Info reward |
| Punishment diterapkan | Trigger threshold | Info + alasan |

**Cron Job Harian (urutan eksekusi pagi, sebelum jam operasional):**
```
1. Scan semua Peminjaman [Aktif]
   a. Jatuh tempo H-3 → kirim reminder H-3
   b. Jatuh tempo H-1 → kirim reminder H-1
   c. Jatuh tempo terlewati → ubah status [Terlambat] + notif + mulai hitung denda
2. Jam operasional dimulai → device RFID mulai aktif menerima tap
```

---

## 9. Diagram Relasi Data (High-Level)

```
User (Siswa/Pegawai/Pustakawan/Admin)
  │
  ├──< Transaksi (1 transaksi = 1 proses pinjam/kunjungan/bayar denda)
  │       └──< Peminjaman (N buku per transaksi)
  │               ├── Pengembalian (1:1)
  │               └──< Denda (keterlambatan/kerusakan/kehilangan)
  │
  ├──< Kunjungan (dari RFID, tervalidasi jam operasional di device)
  │
  ├──< Point (log per event)
  │     └─ akumulasi → LevelBadge
  │                  → Reward       → RewardLog
  │                  → Punishment   → PunishmentLog
  │
  └── status_suspend (dipengaruhi Denda belum lunas)

Buku ──belongsTo── Rak
Buku ──belongsToMany── Kategori
Rak  ──belongsToMany── Kategori

Setting (key-value config: limit, point, badge/reward/punishment threshold,
         tarif denda, jam operasional, interval sync device, endpoint WA & perpustakaan)
```

---

## 10. Blueprint Model Final (Laravel Shift Blueprint)

> **Aturan UUID:** Semua model menggunakan `id: uuid` **kecuali `User`** (tetap bigint auto-increment bawaan Laravel, agar kompatibel penuh dengan auth Filament tanpa penyesuaian tambahan).
> Foreign key ke `users` → `foreign:users` (tanpa prefix uuid). Foreign key ke tabel lain → `uuid foreign:nama_tabel`.

```yaml
models:
    User:
        avatar: string nullable
        role: enum:siswa,pegawai,pustakawan,admin default:siswa
        nis: string nullable unique
        nip: string nullable unique
        kelas: string nullable
        jabatan: string nullable
        no_telepon: string
        no_kartu_rfid: string nullable unique
        status_suspend: boolean default:false
        akumulasi_point: integer default:0
        level_badge_id: uuid foreign:level_badges nullable
        softDeletes: true

    Kategori:
        id: uuid
        nama: string
        deskripsi: text nullable
        softDeletes: true
        relationships:
            belongsToMany: Buku, Rak

    Rak:
        id: uuid
        nama: string
        lokasi: string nullable
        softDeletes: true
        relationships:
            belongsToMany: Kategori
            hasMany: Buku

    Buku:
        id: uuid
        judul: string
        cover: string nullable
        penulis: string nullable
        penerbit: string nullable
        isbn: string nullable unique
        barcode: string unique
        rak_id: uuid foreign:raks nullable
        harga_ganti: decimal:10,2 default:0
        stok: integer default:1
        deskripsi: text nullable
        softDeletes: true
        relationships:
            belongsTo: Rak
            belongsToMany: Kategori
            hasMany: Peminjaman

    Transaksi:
        id: uuid
        user_id: foreign:users
        jenis: enum:peminjaman,kunjungan,pembayaran_denda default:peminjaman
        diproses_oleh: foreign:users nullable
        tanggal: datetime
        keterangan: text nullable
        softDeletes: true
        relationships:
            belongsTo: User
            hasMany: Peminjaman

    Peminjaman:
        id: uuid
        transaksi_id: uuid foreign:transaksis
        user_id: foreign:users
        buku_id: uuid foreign:bukus
        tanggal_pinjam: date
        tanggal_jatuh_tempo: date
        status: enum:aktif,terlambat,selesai,hilang default:aktif
        diproses_oleh: foreign:users nullable
        softDeletes: true
        relationships:
            belongsTo: Transaksi, User, Buku
            hasOne: Pengembalian
            hasMany: Denda

    Pengembalian:
        id: uuid
        peminjaman_id: uuid foreign:peminjamans
        tanggal_kembali: date
        kondisi: enum:baik,rusak,hilang default:baik
        catatan: text nullable
        diproses_oleh: foreign:users nullable
        softDeletes: true
        relationships:
            belongsTo: Peminjaman

    Denda:
        id: uuid
        peminjaman_id: uuid foreign:peminjamans
        user_id: foreign:users
        tipe: enum:keterlambatan,kerusakan,kehilangan
        nominal: decimal:10,2
        status_lunas: boolean default:false
        tanggal_lunas: datetime nullable
        keterangan: text nullable
        softDeletes: true
        relationships:
            belongsTo: Peminjaman, User

    Point:
        id: uuid
        user_id: foreign:users
        event_type: enum:kunjungan,peminjaman,pengembalian,kerusakan,kehilangan
        nilai: integer
        ref_type: string nullable
        ref_id: uuid nullable
        keterangan: string nullable
        softDeletes: true
        relationships:
            belongsTo: User

    LevelBadge:
        id: uuid
        nama_badge: string
        min_point: integer
        max_point: integer nullable
        icon: string nullable
        urutan: integer default:0
        softDeletes: true
        relationships:
            hasMany: User

    Reward:
        id: uuid
        nama: string
        deskripsi: text nullable
        threshold_point: integer
        aktif: boolean default:true
        softDeletes: true

    RewardLog:
        id: uuid
        user_id: foreign:users
        reward_id: uuid foreign:rewards
        tanggal_didapat: datetime
        softDeletes: true
        relationships:
            belongsTo: User, Reward

    Punishment:
        id: uuid
        nama: string
        deskripsi: text nullable
        threshold_point_minus: integer
        durasi_suspend_hari: integer nullable
        aktif: boolean default:true
        softDeletes: true

    PunishmentLog:
        id: uuid
        user_id: foreign:users
        punishment_id: uuid foreign:punishments
        tanggal_diterapkan: datetime
        tanggal_berakhir: datetime nullable
        softDeletes: true
        relationships:
            belongsTo: User, Punishment

    Kunjungan:
        id: uuid
        user_id: foreign:users
        tanggal: date
        jam_tap: time
        source: enum:rfid,manual default:rfid
        softDeletes: true
        relationships:
            belongsTo: User

    Setting:
        id: uuid
        key: string unique
        value: text nullable
        group: enum:peminjaman,point,notifikasi,denda,device,whatsapp default:peminjaman
        keterangan: string nullable
        timestamps: true
```

**Nama tabel hasil generate (default pluralization):**
`users`, `kategoris`, `raks`, `bukus`, `transaksis`, `peminjamans`, `pengembalians`, `dendas`, `points`, `level_badges`, `rewards`, `reward_logs`, `punishments`, `punishment_logs`, `kunjungans`, `settings`.

---

## 11. Catatan Teknis Implementasi (Checklist Sebelum Coding)

- [ ] Tambahkan trait `HasUuids` manual ke semua model **kecuali `User`**.
- [ ] Migration `users` tetap jalan pertama (default Laravel) sebelum tabel yang mereferensikannya.
- [ ] Validasi `nis`/`nip` kondisional per role (`required_if:role,siswa` dst.) — di Form Request/Filament Form, bukan di migration.
- [ ] Normalisasi format `no_telepon` (misal ke `62xxx`) sebelum dikirim ke gateway WA.
- [ ] `ref_type`/`ref_id` di `Point` bersifat polymorphic manual (bukan Eloquent morph) — dokumentasikan mapping `ref_type` yang valid (`peminjaman`, `pengembalian`, `kunjungan`, dst).
- [ ] Observer/Job untuk update `User.status_suspend` otomatis berdasarkan status lunas Denda — bukan field yang bisa diedit manual dari Filament.
- [ ] Cron/Scheduler Laravel untuk: reminder H-3/H-1, transisi status Terlambat, cek threshold Badge/Reward/Punishment.
- [ ] Job/Queue terpisah untuk pengiriman notifikasi WA (agar tidak blocking proses utama).
- [ ] Endpoint API khusus untuk device RFID: terima batch data tap + kirim balik konfigurasi (`jam_operasional`, `interval_sync`).
- [ ] Filament Resource + Policy per role (Pustakawan vs Admin) agar sidebar menyesuaikan otomatis.
- [ ] Pertimbangkan index tambahan pada kolom yang sering di-query: `Peminjaman.status`, `Peminjaman.tanggal_jatuh_tempo`, `Denda.status_lunas`, `Kunjungan.tanggal`.

---

Dokumen ini sudah mencakup seluruh logic, relasi data, state machine, dan blueprint siap generate. Selanjutnya, mau saya bantu susun **Filament Resource plan** (form/table/filter/RelationManager per model + policy per role), atau langsung ke **daftar API endpoint** untuk device RFID dan integrasi WhatsApp?
