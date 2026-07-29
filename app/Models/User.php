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
        'nis',
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

    // TODO: GAP-SPEC - resolusi login multi-identifier (nis/nip ATAU no_telepon) belum
    // diimplementasikan. Filament default hanya support satu kolom username tetap.
    // Butuh custom Login Page yang query:
    //   User::where('nis', $login)->orWhere('nip', $login)->orWhere('no_telepon', $login)->first()
}
