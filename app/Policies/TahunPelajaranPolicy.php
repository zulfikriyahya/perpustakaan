<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TahunPelajaran;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TahunPelajaranPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TahunPelajaran');
    }

    public function view(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('View:TahunPelajaran');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TahunPelajaran');
    }

    public function update(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('Update:TahunPelajaran');
    }

    public function delete(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('Delete:TahunPelajaran');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TahunPelajaran');
    }

    public function restore(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('Restore:TahunPelajaran');
    }

    public function forceDelete(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('ForceDelete:TahunPelajaran');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TahunPelajaran');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TahunPelajaran');
    }

    public function replicate(AuthUser $authUser, TahunPelajaran $tahunPelajaran): bool
    {
        return $authUser->can('Replicate:TahunPelajaran');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TahunPelajaran');
    }
}
