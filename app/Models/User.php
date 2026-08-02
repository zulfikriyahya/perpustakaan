<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Enums\RoleUser;
use App\Enums\StatusAkademik;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements AuthenticatableContract, FilamentUser, HasName
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'avatar',
        'nama',
        'jenis_kelamin',
        'role',
        'nisn',
        'nip',
        'kelas_tahun_pelajaran_id',
        'status_akademik',
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
            'jenis_kelamin' => JenisKelamin::class,
            'status_akademik' => StatusAkademik::class,
            'status_suspend' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getFilamentName(): string
    {
        return $this->nama;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function levelBadge(): BelongsTo
    {
        return $this->belongsTo(LevelBadge::class)->withTrashed();
    }

    public function kelasTahunPelajaran(): BelongsTo
    {
        return $this->belongsTo(KelasTahunPelajaran::class)->withTrashed();
    }

    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(RiwayatKelasSiswa::class);
    }
}
