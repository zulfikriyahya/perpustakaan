<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RewardLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class RewardLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RewardLog');
    }

    public function view(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('View:RewardLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RewardLog');
    }

    public function update(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('Update:RewardLog');
    }

    public function delete(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('Delete:RewardLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RewardLog');
    }

    public function restore(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('Restore:RewardLog');
    }

    public function forceDelete(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('ForceDelete:RewardLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RewardLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RewardLog');
    }

    public function replicate(AuthUser $authUser, RewardLog $rewardLog): bool
    {
        return $authUser->can('Replicate:RewardLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RewardLog');
    }

}