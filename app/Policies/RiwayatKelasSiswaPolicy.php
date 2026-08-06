<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RiwayatKelasSiswa;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RiwayatKelasSiswaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RiwayatKelasSiswa');
    }

    public function view(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('View:RiwayatKelasSiswa');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RiwayatKelasSiswa');
    }

    public function update(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('Update:RiwayatKelasSiswa');
    }

    public function delete(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('Delete:RiwayatKelasSiswa');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RiwayatKelasSiswa');
    }

    public function restore(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('Restore:RiwayatKelasSiswa');
    }

    public function forceDelete(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('ForceDelete:RiwayatKelasSiswa');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RiwayatKelasSiswa');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RiwayatKelasSiswa');
    }

    public function replicate(AuthUser $authUser, RiwayatKelasSiswa $riwayatKelasSiswa): bool
    {
        return $authUser->can('Replicate:RiwayatKelasSiswa');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RiwayatKelasSiswa');
    }
}
