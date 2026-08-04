<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Buku;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BukuPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Buku');
    }

    public function view(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('View:Buku');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Buku');
    }

    public function update(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('Update:Buku');
    }

    public function delete(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('Delete:Buku');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Buku');
    }

    public function restore(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('Restore:Buku');
    }

    public function forceDelete(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('ForceDelete:Buku');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Buku');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Buku');
    }

    public function replicate(AuthUser $authUser, Buku $buku): bool
    {
        return $authUser->can('Replicate:Buku');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Buku');
    }
}
