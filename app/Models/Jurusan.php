<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jurusan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['nama', 'kode'];

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
