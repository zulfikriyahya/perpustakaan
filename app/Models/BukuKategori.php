<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Pivot model untuk buku_kategori, dipakai sebagai "through" table
// pada Kategori::eksemplars() (hasManyThrough)
class BukuKategori extends Model
{
    protected $table = 'buku_kategori';

    public $incrementing = false;

    public $timestamps = false;

    // Primary key TIDAK di-set (dihapus dari versi sebelumnya) - tabel
    // buku_kategori punya composite primary key [buku_id, kategori_id]
    // (lihat migration create_buku_kategori_table), TIDAK bisa
    // direpresentasikan lewat $primaryKey (Eloquent hanya mendukung satu
    // kolom PK). Model ini murni "through" table read-only untuk
    // hasManyThrough di Kategori::eksemplars() - tidak pernah dipanggil
    // find()/save()/update() langsung, sehingga tidak butuh PK yang benar
    // secara fungsional. Sebelumnya di-set keliru ke 'buku_id' saja, yang
    // bisa menyesatkan jika suatu saat ada kode baru yang memanggil
    // find()/save() di model ini secara langsung.
}
