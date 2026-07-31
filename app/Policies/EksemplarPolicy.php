<?php

namespace App\Policies;

use App\Models\Eksemplar;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * TODO: GAP-SPEC - dibuat karena EksemplarsRelationManager memakai
 * can('create'/'viewAny', Eksemplar::class) tapi belum pernah ada Policy
 * terdaftar untuk model ini (Eksemplar bukan Filament Resource sendiri,
 * jadi Shield tidak auto-generate). CRUD lengkap disediakan (bukan
 * hanya ViewAny/Create) supaya EditAction/DeleteAction di
 * EksemplarsRelationManager (yang implisit memanggil ability
 * update/delete) tidak tiba-tiba mati begitu Policy ini terdaftar -
 * lihat Aturan poin 17.
 */
class EksemplarPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Eksemplar');
    }

    public function view(User $user, Eksemplar $eksemplar): bool
    {
        return $user->can('View:Eksemplar');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Eksemplar');
    }

    public function update(User $user, Eksemplar $eksemplar): bool
    {
        return $user->can('Update:Eksemplar');
    }

    public function delete(User $user, Eksemplar $eksemplar): bool
    {
        return $user->can('Delete:Eksemplar');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Eksemplar');
    }

    public function restore(User $user, Eksemplar $eksemplar): bool
    {
        return $user->can('Restore:Eksemplar');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Eksemplar');
    }

    public function forceDelete(User $user, Eksemplar $eksemplar): bool
    {
        return $user->can('ForceDelete:Eksemplar');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Eksemplar');
    }

    public function replicate(User $user): bool
    {
        return $user->can('Replicate:Eksemplar');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Eksemplar');
    }
}
