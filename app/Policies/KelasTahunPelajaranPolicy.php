<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\KelasTahunPelajaran;
use Illuminate\Auth\Access\HandlesAuthorization;

class KelasTahunPelajaranPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KelasTahunPelajaran');
    }

    public function view(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('View:KelasTahunPelajaran');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KelasTahunPelajaran');
    }

    public function update(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('Update:KelasTahunPelajaran');
    }

    public function delete(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('Delete:KelasTahunPelajaran');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:KelasTahunPelajaran');
    }

    public function restore(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('Restore:KelasTahunPelajaran');
    }

    public function forceDelete(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('ForceDelete:KelasTahunPelajaran');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KelasTahunPelajaran');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KelasTahunPelajaran');
    }

    public function replicate(AuthUser $authUser, KelasTahunPelajaran $kelasTahunPelajaran): bool
    {
        return $authUser->can('Replicate:KelasTahunPelajaran');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:KelasTahunPelajaran');
    }

}