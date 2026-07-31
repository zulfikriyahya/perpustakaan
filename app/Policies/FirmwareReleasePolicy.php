<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FirmwareRelease;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FirmwareReleasePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FirmwareRelease');
    }

    public function view(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('View:FirmwareRelease');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FirmwareRelease');
    }

    public function update(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('Update:FirmwareRelease');
    }

    public function delete(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('Delete:FirmwareRelease');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FirmwareRelease');
    }

    public function restore(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('Restore:FirmwareRelease');
    }

    public function forceDelete(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('ForceDelete:FirmwareRelease');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FirmwareRelease');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FirmwareRelease');
    }

    public function replicate(AuthUser $authUser, FirmwareRelease $firmwareRelease): bool
    {
        return $authUser->can('Replicate:FirmwareRelease');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FirmwareRelease');
    }
}
