<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LevelBadge;
use Illuminate\Auth\Access\HandlesAuthorization;

class LevelBadgePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LevelBadge');
    }

    public function view(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('View:LevelBadge');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LevelBadge');
    }

    public function update(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('Update:LevelBadge');
    }

    public function delete(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('Delete:LevelBadge');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LevelBadge');
    }

    public function restore(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('Restore:LevelBadge');
    }

    public function forceDelete(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('ForceDelete:LevelBadge');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LevelBadge');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LevelBadge');
    }

    public function replicate(AuthUser $authUser, LevelBadge $levelBadge): bool
    {
        return $authUser->can('Replicate:LevelBadge');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LevelBadge');
    }

}