<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rak;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RakPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Rak');
    }

    public function view(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('View:Rak');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Rak');
    }

    public function update(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('Update:Rak');
    }

    public function delete(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('Delete:Rak');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Rak');
    }

    public function restore(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('Restore:Rak');
    }

    public function forceDelete(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('ForceDelete:Rak');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Rak');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Rak');
    }

    public function replicate(AuthUser $authUser, Rak $rak): bool
    {
        return $authUser->can('Replicate:Rak');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Rak');
    }
}
