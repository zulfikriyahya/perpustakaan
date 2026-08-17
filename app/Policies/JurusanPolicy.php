<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Jurusan;
use Illuminate\Auth\Access\HandlesAuthorization;

class JurusanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Jurusan');
    }

    public function view(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('View:Jurusan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Jurusan');
    }

    public function update(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('Update:Jurusan');
    }

    public function delete(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('Delete:Jurusan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Jurusan');
    }

    public function restore(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('Restore:Jurusan');
    }

    public function forceDelete(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('ForceDelete:Jurusan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Jurusan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Jurusan');
    }

    public function replicate(AuthUser $authUser, Jurusan $jurusan): bool
    {
        return $authUser->can('Replicate:Jurusan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Jurusan');
    }

}