<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PunishmentLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PunishmentLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PunishmentLog');
    }

    public function view(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('View:PunishmentLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PunishmentLog');
    }

    public function update(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('Update:PunishmentLog');
    }

    public function delete(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('Delete:PunishmentLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PunishmentLog');
    }

    public function restore(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('Restore:PunishmentLog');
    }

    public function forceDelete(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('ForceDelete:PunishmentLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PunishmentLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PunishmentLog');
    }

    public function replicate(AuthUser $authUser, PunishmentLog $punishmentLog): bool
    {
        return $authUser->can('Replicate:PunishmentLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PunishmentLog');
    }
}
