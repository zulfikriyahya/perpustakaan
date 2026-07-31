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

    protected $primaryKey = 'buku_id';

    protected $keyType = 'string';
}
