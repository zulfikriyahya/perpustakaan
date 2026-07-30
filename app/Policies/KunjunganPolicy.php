<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Kunjungan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class KunjunganPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Kunjungan');
    }

    public function view(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('View:Kunjungan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Kunjungan');
    }

    public function update(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('Update:Kunjungan');
    }

    public function delete(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('Delete:Kunjungan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Kunjungan');
    }

    public function restore(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('Restore:Kunjungan');
    }

    public function forceDelete(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('ForceDelete:Kunjungan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Kunjungan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Kunjungan');
    }

    public function replicate(AuthUser $authUser, Kunjungan $kunjungan): bool
    {
        return $authUser->can('Replicate:Kunjungan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Kunjungan');
    }
}
