<?php

namespace App\Models;

use App\Enums\RoleUser;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements AuthenticatableContract, HasName, FilamentUser
{
    use HasFactory, SoftDeletes, HasRoles;

    protected $fillable = [
        'avatar',
        'nama',
        'role',
        'nisn',
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

    public function getFilamentName(): string
    {
        return $this->nama;
    }

    /**
     * Konfirmasi Aturan: SATU panel untuk semua role, pembatasan akses
     * dilakukan lewat Policy per Resource (bukan di sini). Semua role yang
     * berhasil login (termasuk yang status_suspend = true, karena mereka
     * tetap perlu melihat Denda/Punishment miliknya sendiri untuk tahu
     * alasan suspend) lolos ke panel. Guard sesungguhnya (Siswa tidak bisa
     * CRUD Buku, Pustakawan tidak bisa ubah Setting, dst.) ditulis di
     * masing-masing app/Policies/*Policy.php, di-enforce via Shield.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function levelBadge(): BelongsTo
    {
        return $this->belongsTo(LevelBadge::class);
    }
}
