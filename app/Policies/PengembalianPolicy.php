<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pengembalian;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PengembalianPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pengembalian');
    }

    public function view(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('View:Pengembalian');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pengembalian');
    }

    public function update(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('Update:Pengembalian');
    }

    public function delete(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('Delete:Pengembalian');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Pengembalian');
    }

    public function restore(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('Restore:Pengembalian');
    }

    public function forceDelete(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('ForceDelete:Pengembalian');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pengembalian');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pengembalian');
    }

    public function replicate(AuthUser $authUser, Pengembalian $pengembalian): bool
    {
        return $authUser->can('Replicate:Pengembalian');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pengembalian');
    }
}
