<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LevelBadgeLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LevelBadgeLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LevelBadgeLog');
    }

    public function view(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('View:LevelBadgeLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LevelBadgeLog');
    }

    public function update(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('Update:LevelBadgeLog');
    }

    public function delete(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('Delete:LevelBadgeLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LevelBadgeLog');
    }

    public function restore(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('Restore:LevelBadgeLog');
    }

    public function forceDelete(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('ForceDelete:LevelBadgeLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LevelBadgeLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LevelBadgeLog');
    }

    public function replicate(AuthUser $authUser, LevelBadgeLog $levelBadgeLog): bool
    {
        return $authUser->can('Replicate:LevelBadgeLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LevelBadgeLog');
    }
}
