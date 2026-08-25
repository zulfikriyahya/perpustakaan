<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Punishment;
use Illuminate\Auth\Access\HandlesAuthorization;

class PunishmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Punishment');
    }

    public function view(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('View:Punishment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Punishment');
    }

    public function update(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('Update:Punishment');
    }

    public function delete(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('Delete:Punishment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Punishment');
    }

    public function restore(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('Restore:Punishment');
    }

    public function forceDelete(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('ForceDelete:Punishment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Punishment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Punishment');
    }

    public function replicate(AuthUser $authUser, Punishment $punishment): bool
    {
        return $authUser->can('Replicate:Punishment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Punishment');
    }

}