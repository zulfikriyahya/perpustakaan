<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Reward;
use Illuminate\Auth\Access\HandlesAuthorization;

class RewardPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Reward');
    }

    public function view(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('View:Reward');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Reward');
    }

    public function update(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('Update:Reward');
    }

    public function delete(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('Delete:Reward');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Reward');
    }

    public function restore(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('Restore:Reward');
    }

    public function forceDelete(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('ForceDelete:Reward');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Reward');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Reward');
    }

    public function replicate(AuthUser $authUser, Reward $reward): bool
    {
        return $authUser->can('Replicate:Reward');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Reward');
    }

}