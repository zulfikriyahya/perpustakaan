<?php

namespace App\Enums;

enum StatusRiwayatKelas: string
{
    case Aktif = 'aktif';       // sedang berjalan di KTP ini
    case Naik = 'naik';         // selesai, siswa naik ke KTP berikutnya
    case Tinggal = 'tinggal';   // selesai, siswa tinggal kelas (KTP tingkat sama, tahun baru)
    case Lulus = 'lulus';       // selesai, siswa lulus dari KTP ini
    case Keluar = 'keluar';     // selesai, siswa keluar/pindah sekolah
}
