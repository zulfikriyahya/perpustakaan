# Template WhatsApp — Perpustakaan

## 1. Peminjaman Aktif

**Kode Template:** `peminjaman_aktif`
**Kategori:** `Peminjaman`
**Prioritas:** `High`
**Variabel Wajib:** `nama,daftar_buku,jatuh_tempo`

```
1.
*Perpustakaan*

*Peminjaman Berhasil*
Nama: {{nama}}
Buku: {{daftar_buku}}
Jatuh Tempo: {{jatuh_tempo}}

2.
*Perpustakaan*

*Konfirmasi Peminjaman*
{{nama}} telah meminjam: {{daftar_buku}}. Harap dikembalikan sebelum {{jatuh_tempo}}.

3.
*Perpustakaan*

*Peminjaman Buku Berhasil*
Nama       : {{nama}}
Buku       : {{daftar_buku}}
Jatuh Tempo: {{jatuh_tempo}}

4.
*Perpustakaan*

Halo {{nama}}, peminjamanmu tercatat untuk buku: {{daftar_buku}}. Batas pengembalian {{jatuh_tempo}}.

5.
*Perpustakaan*

*Info Peminjaman*
{{nama}} - {{daftar_buku}}
Jatuh Tempo: {{jatuh_tempo}}

6.
*Perpustakaan*

*Peminjaman Buku*
Dicatat atas nama {{nama}} untuk buku {{daftar_buku}}, jatuh tempo {{jatuh_tempo}}.

7.
*Perpustakaan*

*Peminjaman Aktif*
Nama: {{nama}}
Buku: {{daftar_buku}}
Jatuh Tempo: {{jatuh_tempo}}

8.
*Perpustakaan*

Konfirmasi pinjam: {{nama}} - {{daftar_buku}} (jatuh tempo {{jatuh_tempo}}).

9.
*Perpustakaan*

*Peminjaman Berhasil*
Halo {{nama}}, peminjaman buku berikut sudah tercatat:
{{daftar_buku}}
Jatuh Tempo: {{jatuh_tempo}}

10.
*Perpustakaan*

*Peminjaman Buku*
{{nama}} tercatat meminjam {{daftar_buku}}. Kembalikan sebelum {{jatuh_tempo}} agar terhindar denda.
```

---

## 2. Reminder H-3

**Kode Template:** `reminder_h3`
**Kategori:** `Peminjaman`
**Prioritas:** `Medium`
**Variabel Wajib:** `nama,buku,jatuh_tempo`

```
1.
*Perpustakaan*

*Pengingat Pengembalian*
Nama: {{nama}}
Buku: {{buku}}
Jatuh Tempo: {{jatuh_tempo}} (3 hari lagi)

2.
*Perpustakaan*

*Reminder H-3*
{{nama}}, buku {{buku}} jatuh tempo dalam 3 hari ({{jatuh_tempo}}).

3.
*Perpustakaan*

*Pengingat Pengembalian Buku*
Nama       : {{nama}}
Buku       : {{buku}}
Jatuh Tempo: {{jatuh_tempo}}

4.
*Perpustakaan*

Halo {{nama}}, jangan lupa buku {{buku}} harus dikembalikan pada {{jatuh_tempo}} (H-3).

5.
*Perpustakaan*

*Info Pengembalian*
{{nama}} - {{buku}}
Sisa waktu: 3 hari, jatuh tempo {{jatuh_tempo}}

6.
*Perpustakaan*

*Pengingat*
Buku {{buku}} atas nama {{nama}} akan jatuh tempo {{jatuh_tempo}}.

7.
*Perpustakaan*

*Reminder Pengembalian*
Nama: {{nama}}
Buku: {{buku}}
Jatuh Tempo: {{jatuh_tempo}}

8.
*Perpustakaan*

Pengingat: {{nama}} - {{buku}}, jatuh tempo {{jatuh_tempo}} (3 hari lagi).

9.
*Perpustakaan*

*Pengingat Pengembalian*
Halo {{nama}}, buku {{buku}} yang kamu pinjam akan jatuh tempo pada {{jatuh_tempo}}.

10.
*Perpustakaan*

*Reminder H-3*
{{nama}}, segera kembalikan {{buku}} sebelum {{jatuh_tempo}} untuk menghindari denda.
```

---

## 3. Reminder H-1

**Kode Template:** `reminder_h1`
**Kategori:** `Peminjaman`
**Prioritas:** `High`
**Variabel Wajib:** `nama,buku,jatuh_tempo`

```
1.
*Perpustakaan*

*Pengingat Pengembalian - Besok!*
Nama: {{nama}}
Buku: {{buku}}
Jatuh Tempo: {{jatuh_tempo}}

2.
*Perpustakaan*

*Reminder H-1*
{{nama}}, buku {{buku}} jatuh tempo BESOK ({{jatuh_tempo}}).

3.
*Perpustakaan*

*Pengingat Terakhir*
Nama       : {{nama}}
Buku       : {{buku}}
Jatuh Tempo: {{jatuh_tempo}} (besok)

4.
*Perpustakaan*

Halo {{nama}}, buku {{buku}} harus dikembalikan besok, {{jatuh_tempo}}.

5.
*Perpustakaan*

*Info Pengembalian Segera*
{{nama}} - {{buku}}
Jatuh tempo besok: {{jatuh_tempo}}

6.
*Perpustakaan*

*Pengingat Terakhir*
Buku {{buku}} atas nama {{nama}} jatuh tempo besok, {{jatuh_tempo}}.

7.
*Perpustakaan*

*Reminder Pengembalian - Segera*
Nama: {{nama}}
Buku: {{buku}}
Jatuh Tempo: {{jatuh_tempo}}

8.
*Perpustakaan*

Pengingat penting: {{nama}} - {{buku}}, jatuh tempo besok ({{jatuh_tempo}}).

9.
*Perpustakaan*

*Pengingat H-1*
Halo {{nama}}, besok adalah batas akhir pengembalian buku {{buku}} ({{jatuh_tempo}}).

10.
*Perpustakaan*

*Reminder H-1*
{{nama}}, segera kembalikan {{buku}} besok ({{jatuh_tempo}}) agar tidak terkena denda keterlambatan.
```

---

## 4. Jadi Terlambat

**Kode Template:** `jadi_terlambat`
**Kategori:** `Peminjaman`
**Prioritas:** `High`
**Variabel Wajib:** `nama,buku`

```
1.
*Perpustakaan*

*Peringatan Keterlambatan*
Nama: {{nama}}
Buku: {{buku}}
Status: Terlambat, denda mulai berjalan.

2.
*Perpustakaan*

*Buku Terlambat*
{{nama}}, buku {{buku}} sudah melewati jatuh tempo. Denda mulai dihitung.

3.
*Perpustakaan*

*Peringatan Keterlambatan*
Nama  : {{nama}}
Buku  : {{buku}}
Status: Terlambat

4.
*Perpustakaan*

Halo {{nama}}, buku {{buku}} yang kamu pinjam sudah terlambat. Segera dikembalikan.

5.
*Perpustakaan*

*Info Keterlambatan*
{{nama}} - {{buku}}
Denda keterlambatan mulai berjalan.

6.
*Perpustakaan*

*Peringatan*
Buku {{buku}} atas nama {{nama}} berstatus terlambat.

7.
*Perpustakaan*

*Buku Terlambat*
Nama: {{nama}}
Buku: {{buku}}
Denda harian mulai dihitung.

8.
*Perpustakaan*

Peringatan: {{nama}} - {{buku}} sudah terlambat, denda berjalan.

9.
*Perpustakaan*

*Peringatan Keterlambatan*
Halo {{nama}}, buku {{buku}} belum dikembalikan dan sudah melewati batas waktu.

10.
*Perpustakaan*

*Buku Terlambat*
{{nama}}, segera kembalikan {{buku}} untuk menghentikan penambahan denda.
```

---

## 5. Pengembalian Diproses

**Kode Template:** `pengembalian_diproses`
**Kategori:** `Peminjaman`
**Prioritas:** `Medium`
**Variabel Wajib:** `nama,kondisi`

```
1.
*Perpustakaan*

*Pengembalian Diproses*
Nama: {{nama}}
Kondisi Buku: {{kondisi}}

2.
*Perpustakaan*

*Konfirmasi Pengembalian*
{{nama}}, pengembalian buku sudah diproses dengan kondisi {{kondisi}}.

3.
*Perpustakaan*

*Pengembalian Berhasil*
Nama   : {{nama}}
Kondisi: {{kondisi}}

4.
*Perpustakaan*

Halo {{nama}}, terima kasih telah mengembalikan buku. Kondisi tercatat: {{kondisi}}.

5.
*Perpustakaan*

*Info Pengembalian*
{{nama}} - Kondisi: {{kondisi}}

6.
*Perpustakaan*

*Pengembalian Diproses*
Dicatat atas nama {{nama}} dengan kondisi buku {{kondisi}}.

7.
*Perpustakaan*

*Konfirmasi Pengembalian Buku*
Nama: {{nama}}
Kondisi: {{kondisi}}

8.
*Perpustakaan*

Konfirmasi: {{nama}} - pengembalian diproses ({{kondisi}}).

9.
*Perpustakaan*

*Pengembalian Diproses*
Halo {{nama}}, buku telah diterima kembali oleh pustakawan. Kondisi: {{kondisi}}.

10.
*Perpustakaan*

*Pengembalian Berhasil*
{{nama}}, pengembalianmu sudah tercatat dengan kondisi {{kondisi}}.
```

---

## 6. Denda Dibuat

**Kode Template:** `denda_dibuat`
**Kategori:** `Denda`
**Prioritas:** `High`
**Variabel Wajib:** `nama,tipe,nominal`

```
1.
*Perpustakaan*

*Denda Tercatat*
Nama: {{nama}}
Jenis: {{tipe}}
Nominal: Rp{{nominal}}

2.
*Perpustakaan*

*Info Denda*
{{nama}}, denda {{tipe}} sebesar Rp{{nominal}} telah tercatat pada akunmu.

3.
*Perpustakaan*

*Denda Baru*
Nama    : {{nama}}
Jenis   : {{tipe}}
Nominal : Rp{{nominal}}

4.
*Perpustakaan*

Halo {{nama}}, kamu dikenakan denda {{tipe}} sebesar Rp{{nominal}}.

5.
*Perpustakaan*

*Info Denda*
{{nama}} - {{tipe}}
Nominal: Rp{{nominal}}

6.
*Perpustakaan*

*Denda Tercatat*
Denda jenis {{tipe}} atas nama {{nama}} sebesar Rp{{nominal}}.

7.
*Perpustakaan*

*Denda Baru*
Nama: {{nama}}
Jenis: {{tipe}}
Nominal: Rp{{nominal}}

8.
*Perpustakaan*

Info: {{nama}} dikenakan denda {{tipe}} - Rp{{nominal}}.

9.
*Perpustakaan*

*Denda Tercatat*
Halo {{nama}}, denda {{tipe}} sebesar Rp{{nominal}} sudah tercatat. Segera lakukan pembayaran.

10.
*Perpustakaan*

*Info Denda*
{{nama}}, denda {{tipe}} (Rp{{nominal}}) perlu dilunasi agar dapat meminjam kembali.
```

---

## 7. Denda Lunas

**Kode Template:** `denda_lunas`
**Kategori:** `Denda`
**Prioritas:** `Medium`
**Variabel Wajib:** `nama`

```
1.
*Perpustakaan*

*Denda Lunas*
Nama: {{nama}}
Status: Seluruh denda telah lunas. Akun kembali aktif.

2.
*Perpustakaan*

*Konfirmasi Pelunasan*
{{nama}}, seluruh dendamu sudah lunas. Kamu dapat meminjam kembali.

3.
*Perpustakaan*

*Pelunasan Denda*
Nama  : {{nama}}
Status: Lunas, akun tidak diblokir.

4.
*Perpustakaan*

Halo {{nama}}, terima kasih. Denda sudah lunas dan akunmu kembali normal.

5.
*Perpustakaan*

*Info Pelunasan*
{{nama}} - Denda lunas, status unblock.

6.
*Perpustakaan*

*Denda Lunas*
Seluruh denda atas nama {{nama}} sudah dilunasi.

7.
*Perpustakaan*

*Konfirmasi Lunas*
Nama: {{nama}}
Status: Akun aktif kembali.

8.
*Perpustakaan*

Konfirmasi: {{nama}} - denda lunas, akun unblock.

9.
*Perpustakaan*

*Denda Lunas*
Halo {{nama}}, pembayaran dendamu sudah kami terima. Akunmu sudah tidak diblokir.

10.
*Perpustakaan*

*Pelunasan Berhasil*
{{nama}}, semua denda sudah lunas. Selamat meminjam kembali!
```

---

## 8. Badge Naik

**Kode Template:** `badge_naik`
**Kategori:** `Poin`
**Prioritas:** `Low`
**Variabel Wajib:** `nama,badge`

```
1.
*Perpustakaan*

*Badge Baru!*
Nama: {{nama}}
Badge: {{badge}}

2.
*Perpustakaan*

*Selamat!*
{{nama}}, kamu naik level menjadi {{badge}}!

3.
*Perpustakaan*

*Info Badge*
Nama : {{nama}}
Badge: {{badge}}

4.
*Perpustakaan*

Halo {{nama}}, selamat! Badge barumu adalah {{badge}}.

5.
*Perpustakaan*

*Level Up*
{{nama}} - Badge: {{badge}}

6.
*Perpustakaan*

*Badge Naik*
{{nama}} berhasil meraih badge {{badge}}.

7.
*Perpustakaan*

*Selamat, Badge Baru!*
Nama: {{nama}}
Badge: {{badge}}

8.
*Perpustakaan*

Info: {{nama}} sekarang meraih badge {{badge}}.

9.
*Perpustakaan*

*Badge Baru!*
Halo {{nama}}, terus rajin ke perpustakaan! Badge barumu: {{badge}}.

10.
*Perpustakaan*

*Level Up!*
{{nama}}, selamat atas pencapaian badge {{badge}}!
```

---

## 9. Reward Didapat

**Kode Template:** `reward_didapat`
**Kategori:** `Poin`
**Prioritas:** `Medium`
**Variabel Wajib:** `nama,reward`

```
1.
*Perpustakaan*

*Reward Diterima!*
Nama: {{nama}}
Reward: {{reward}}

2.
*Perpustakaan*

*Selamat!*
{{nama}}, kamu mendapatkan reward {{reward}}!

3.
*Perpustakaan*

*Info Reward*
Nama  : {{nama}}
Reward: {{reward}}

4.
*Perpustakaan*

Halo {{nama}}, selamat! Kamu berhak atas reward {{reward}}.

5.
*Perpustakaan*

*Reward Baru*
{{nama}} - {{reward}}

6.
*Perpustakaan*

*Reward Didapat*
{{nama}} berhasil meraih reward {{reward}}.

7.
*Perpustakaan*

*Selamat, Reward Baru!*
Nama: {{nama}}
Reward: {{reward}}

8.
*Perpustakaan*

Info: {{nama}} mendapatkan reward {{reward}}.

9.
*Perpustakaan*

*Reward Diterima!*
Halo {{nama}}, terima kasih atas keaktifanmu! Reward: {{reward}}.

10.
*Perpustakaan*

*Selamat!*
{{nama}}, kamu meraih reward {{reward}}. Silakan hubungi pustakawan untuk klaim.
```

---

## 10. Punishment Diterapkan

**Kode Template:** `punishment_diterapkan`
**Kategori:** `Poin`
**Prioritas:** `High`
**Variabel Wajib:** `nama,alasan`

```
1.
*Perpustakaan*

*Punishment Diterapkan*
Nama: {{nama}}
Alasan: {{alasan}}

2.
*Perpustakaan*

*Pemberitahuan*
{{nama}}, akunmu dikenakan punishment karena {{alasan}}.

3.
*Perpustakaan*

*Info Punishment*
Nama  : {{nama}}
Alasan: {{alasan}}

4.
*Perpustakaan*

Halo {{nama}}, akunmu sementara dibatasi. Alasan: {{alasan}}.

5.
*Perpustakaan*

*Punishment*
{{nama}} - {{alasan}}

6.
*Perpustakaan*

*Pemberitahuan Punishment*
Punishment diterapkan pada {{nama}} karena {{alasan}}.

7.
*Perpustakaan*

*Punishment Diterapkan*
Nama: {{nama}}
Alasan: {{alasan}}

8.
*Perpustakaan*

Info: {{nama}} dikenakan punishment - {{alasan}}.

9.
*Perpustakaan*

*Pemberitahuan Punishment*
Halo {{nama}}, mohon perhatikan aturan perpustakaan. Alasan: {{alasan}}.

10.
*Perpustakaan*

*Punishment Diterapkan*
{{nama}}, akunmu dikenakan pembatasan sementara. Alasan: {{alasan}}.
```

---

## 11. Reset Password OTP

**Kode Template:** `reset_password_otp`
**Kategori:** `Akun`
**Prioritas:** `High`
**Variabel Wajib:** `nama,otp`

```
1.
*Perpustakaan*

*Kode Reset Password*
Nama: {{nama}}
Kode OTP: {{otp}}
Berlaku 5 menit. Jangan bagikan kode ini ke siapa pun.

2.
*Perpustakaan*

*Permintaan Reset Password*
Halo {{nama}}, gunakan kode berikut untuk reset password: {{otp}}. Kode berlaku 5 menit.

3.
*Perpustakaan*

*Kode Verifikasi*
Nama    : {{nama}}
Kode OTP: {{otp}}
Jangan berikan kode ini kepada siapa pun, termasuk pihak yang mengaku dari perpustakaan.

4.
*Perpustakaan*

Halo {{nama}}, kode OTP reset password-mu adalah {{otp}}. Kode ini berlaku selama 5 menit.

5.
*Perpustakaan*

*Info Reset Password*
{{nama}} - Kode OTP: {{otp}}
Segera masukkan kode ini di halaman reset password.

6.
*Perpustakaan*

*Kode OTP*
Permintaan reset password atas nama {{nama}}. Kode: {{otp}} (berlaku 5 menit).

7.
*Perpustakaan*

*Reset Password*
Nama: {{nama}}
Kode OTP: {{otp}}
Jika kamu tidak meminta ini, abaikan pesan ini dan segera hubungi Pustakawan/Admin.

8.
*Perpustakaan*

Kode reset password untuk {{nama}}: {{otp}}. Berlaku 5 menit, jangan dibagikan.

9.
*Perpustakaan*

*Permintaan Reset Password*
Halo {{nama}}, kami menerima permintaan reset password untuk akunmu. Kode OTP: {{otp}}. Berlaku 5 menit.

10.
*Perpustakaan*

*Kode Verifikasi Reset Password*
{{nama}}, masukkan kode berikut untuk melanjutkan reset password: {{otp}}. Kode kedaluwarsa dalam 5 menit demi keamanan akunmu.
```

---

## 12. Login OTP

**Kode Template:** `login_otp`
**Kategori:** `Akun`
**Prioritas:** `High`
**Variabel Wajib:** `nama,otp`

```
1.
*Perpustakaan*

*Kode Login OTP*
Nama: {{nama}}
Kode OTP: {{otp}}
Berlaku 5 menit. Jangan bagikan kode ini ke siapa pun, kode ini bisa dipakai untuk masuk ke akunmu.

2.
*Perpustakaan*

*Permintaan Masuk (Login)*
Halo {{nama}}, gunakan kode berikut untuk masuk ke akunmu: {{otp}}. Kode berlaku 5 menit.

3.
*Perpustakaan*

*Kode Verifikasi Login*
Nama    : {{nama}}
Kode OTP: {{otp}}
Jangan berikan kode ini kepada siapa pun, termasuk pihak yang mengaku dari perpustakaan.

4.
*Perpustakaan*

Halo {{nama}}, kode OTP untuk masuk ke akunmu adalah {{otp}}. Kode ini berlaku selama 5 menit.

5.
*Perpustakaan*

*Info Login*
{{nama}} - Kode OTP: {{otp}}
Segera masukkan kode ini di halaman login.

6.
*Perpustakaan*

*Kode OTP Login*
Permintaan masuk (login) atas nama {{nama}}. Kode: {{otp}} (berlaku 5 menit).

7.
*Perpustakaan*

*Login dengan OTP*
Nama: {{nama}}
Kode OTP: {{otp}}
Jika kamu tidak meminta ini, abaikan pesan ini dan segera hubungi Pustakawan/Admin - seseorang mungkin mencoba masuk ke akunmu.

8.
*Perpustakaan*

Kode login untuk {{nama}}: {{otp}}. Berlaku 5 menit, jangan dibagikan ke siapa pun.

9.
*Perpustakaan*

*Permintaan Masuk ke Akun*
Halo {{nama}}, kami menerima permintaan login ke akunmu. Kode OTP: {{otp}}. Berlaku 5 menit.

10.
*Perpustakaan*

*Kode Verifikasi Login*
{{nama}}, masukkan kode berikut untuk masuk ke akunmu: {{otp}}. Kode kedaluwarsa dalam 5 menit demi keamanan akunmu. Kode ini setara password - jangan bagikan.
```

---

## 13. Koreksi Kondisi Pengembalian

**Kode Template:** `koreksi_kondisi_pengembalian`
**Kategori:** `Peminjaman`
**Prioritas:** `Medium`
**Variabel Wajib:** `nama,kondisi_lama,kondisi_baru`

```
1.
*Perpustakaan*

*Koreksi Kondisi Buku*
Nama: {{nama}}
Kondisi Sebelumnya: {{kondisi_lama}}
Kondisi Sekarang: {{kondisi_baru}}

2.
*Perpustakaan*

*Pemberitahuan Koreksi*
Halo {{nama}}, kondisi buku yang kamu kembalikan telah dikoreksi dari {{kondisi_lama}} menjadi {{kondisi_baru}}.

3.
*Perpustakaan*

*Koreksi Data Pengembalian*
Nama              : {{nama}}
Kondisi Sebelumnya: {{kondisi_lama}}
Kondisi Sekarang  : {{kondisi_baru}}

4.
*Perpustakaan*

Halo {{nama}}, pustakawan telah memperbarui catatan kondisi bukumu dari {{kondisi_lama}} menjadi {{kondisi_baru}}.

5.
*Perpustakaan*

*Info Koreksi Kondisi*
{{nama}} - {{kondisi_lama}} -> {{kondisi_baru}}

6.
*Perpustakaan*

*Koreksi Kondisi Buku*
Kondisi buku atas nama {{nama}} dikoreksi: {{kondisi_lama}} menjadi {{kondisi_baru}}.

7.
*Perpustakaan*

*Pembaruan Kondisi Pengembalian*
Nama: {{nama}}
Dari: {{kondisi_lama}}
Menjadi: {{kondisi_baru}}

8.
*Perpustakaan*

Info: {{nama}} - kondisi buku dikoreksi dari {{kondisi_lama}} ke {{kondisi_baru}}.

9.
*Perpustakaan*

*Koreksi Kondisi Buku*
Halo {{nama}}, mohon diperhatikan: kondisi buku yang tercatat sebelumnya ({{kondisi_lama}}) telah diperbarui menjadi {{kondisi_baru}}. Jika ada denda terkait, akan disesuaikan otomatis.

10.
*Perpustakaan*

*Pemberitahuan Koreksi Kondisi*
{{nama}}, catatan kondisi bukumu diperbarui dari {{kondisi_lama}} menjadi {{kondisi_baru}} oleh pustakawan.
```

---

## 14. Denda Dibatalkan (Perlu Refund)

**Kode Template:** `denda_dibatalkan_perlu_refund`
**Kategori:** `Denda`
**Prioritas:** `High`
**Variabel Wajib:** `nama,tipe,nominal`

```
1.
*Perpustakaan*

*Denda Dibatalkan*
Nama: {{nama}}
Jenis: {{tipe}}
Nominal: Rp{{nominal}}
Denda ini sudah terbayar sebelumnya - refund akan diproses secara manual oleh pustakawan.

2.
*Perpustakaan*

*Pemberitahuan Pembatalan Denda*
Halo {{nama}}, denda {{tipe}} sebesar Rp{{nominal}} yang sudah kamu bayar telah dibatalkan akibat koreksi kondisi. Silakan hubungi pustakawan untuk proses refund.

3.
*Perpustakaan*

*Denda Dibatalkan - Perlu Refund*
Nama    : {{nama}}
Jenis    : {{tipe}}
Nominal : Rp{{nominal}}

4.
*Perpustakaan*

Halo {{nama}}, denda {{tipe}} yang sudah kamu bayar (Rp{{nominal}}) dibatalkan setelah koreksi kondisi buku. Silakan datang ke perpustakaan untuk klaim refund.

5.
*Perpustakaan*

*Info Pembatalan Denda*
{{nama}} - {{tipe}}
Nominal dibatalkan: Rp{{nominal}} (perlu refund manual)

6.
*Perpustakaan*

*Denda Dibatalkan*
Denda {{tipe}} atas nama {{nama}} sebesar Rp{{nominal}} dibatalkan karena koreksi kondisi buku. Refund akan diproses pustakawan.

7.
*Perpustakaan*

*Pembatalan Denda - Perlu Refund*
Nama: {{nama}}
Jenis: {{tipe}}
Nominal: Rp{{nominal}}

8.
*Perpustakaan*

Info: denda {{tipe}} milik {{nama}} (Rp{{nominal}}) dibatalkan, perlu refund manual.

9.
*Perpustakaan*

*Denda Dibatalkan*
Halo {{nama}}, mohon maaf atas ketidaknyamanannya. Denda {{tipe}} yang sudah kamu bayar (Rp{{nominal}}) dibatalkan karena kondisi buku dikoreksi. Silakan konfirmasi ke pustakawan untuk pengembalian dana.

10.
*Perpustakaan*

*Pemberitahuan Refund*
{{nama}}, denda {{tipe}} sebesar Rp{{nominal}} yang telah dibayar dibatalkan. Silakan hubungi pustakawan/admin untuk proses refund lebih lanjut.
```

---

Untuk memeriksa widget dan menutup gap N+1 di sana, saya perlu lihat isi filenya dulu (poin 18) — jangan sampai saya menebak query yang dipakai. Yang relevan (mengandung kata "Buku"/"Denda" per nama file dari tree project):

```bash
cat app/Filament/Widgets/BukuPerKategoriWidget.php
cat app/Filament/Widgets/BukuRusakHilangWidget.php
cat app/Filament/Widgets/DendaTerbaruWidget.php
cat app/Filament/Widgets/GamifikasiBulananWidget.php
cat app/Filament/Widgets/PeminjamanJatuhTempoWidget.php
cat app/Filament/Widgets/PeminjamanStatsWidget.php
cat app/Filament/Widgets/PerJenisKelaminWidget.php
cat app/Filament/Widgets/TrenBulananWidget.php
cat app/Filament/Widgets/WhatsappLogWidget.php
```

---

## 15. Buku Ditemukan Kembali

**Kode Template:** `buku_ditemukan_kembali`
**Kategori:** `Peminjaman`
**Prioritas:** `Medium`
**Variabel Wajib:** `nama`

```
1.
*Perpustakaan*

*Buku Ditemukan Kembali*
Nama: {{nama}}
Status: Buku yang sempat dilaporkan hilang sudah ditemukan. Denda kehilangan dibatalkan.

2.
*Perpustakaan*

*Kabar Baik!*
{{nama}}, buku yang sempat dilaporkan hilang sudah ditemukan kembali. Denda terkait sudah dibatalkan.

3.
*Perpustakaan*

*Konfirmasi Buku Ditemukan*
Nama  : {{nama}}
Status: Denda kehilangan dibatalkan, akun diperbarui.

4.
*Perpustakaan*

Halo {{nama}}, terima kasih! Buku yang hilang sudah ditemukan kembali dan Denda kehilangan sudah dibatalkan.

5.
*Perpustakaan*

*Info Pembatalan Denda*
{{nama}} - Buku ditemukan, Denda kehilangan dibatalkan.

6.
*Perpustakaan*

*Buku Ditemukan*
Buku yang dilaporkan hilang atas nama {{nama}} sudah ditemukan kembali oleh pustakawan.

7.
*Perpustakaan*

*Konfirmasi Buku Ditemukan Kembali*
Nama: {{nama}}
Status: Denda kehilangan dibatalkan.

8.
*Perpustakaan*

Info: buku milik {{nama}} yang sempat hilang sudah ditemukan, Denda dibatalkan.

9.
*Perpustakaan*

*Buku Ditemukan Kembali*
Halo {{nama}}, buku yang kamu laporkan hilang sudah kami terima kembali. Denda kehilangan otomatis dibatalkan. Jika sebelumnya sudah membayar, silakan hubungi pustakawan untuk proses refund.

10.
*Perpustakaan*

*Kabar Baik - Buku Ditemukan!*
{{nama}}, buku yang sempat hilang sudah ditemukan dan dikembalikan ke rak. Terima kasih atas kejujurannya.
```
