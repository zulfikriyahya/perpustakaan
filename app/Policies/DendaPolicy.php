<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Denda;
use Illuminate\Auth\Access\HandlesAuthorization;

class DendaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Denda');
    }

    public function view(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('View:Denda');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Denda');
    }

    public function update(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('Update:Denda');
    }

    public function delete(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('Delete:Denda');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Denda');
    }

    public function restore(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('Restore:Denda');
    }

    public function forceDelete(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('ForceDelete:Denda');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Denda');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Denda');
    }

    public function replicate(AuthUser $authUser, Denda $denda): bool
    {
        return $authUser->can('Replicate:Denda');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Denda');
    }

}