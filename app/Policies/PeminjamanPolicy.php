<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Peminjaman;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PeminjamanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Peminjaman');
    }

    public function view(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('View:Peminjaman');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Peminjaman');
    }

    public function update(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('Update:Peminjaman');
    }

    public function delete(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('Delete:Peminjaman');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Peminjaman');
    }

    public function restore(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('Restore:Peminjaman');
    }

    public function forceDelete(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('ForceDelete:Peminjaman');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Peminjaman');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Peminjaman');
    }

    public function replicate(AuthUser $authUser, Peminjaman $peminjaman): bool
    {
        return $authUser->can('Replicate:Peminjaman');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Peminjaman');
    }
}
