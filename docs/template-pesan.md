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
