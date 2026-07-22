<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ImSystemUser;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ImSystemUserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ImSystemUser');
    }

    public function view(AuthUser $authUser, ImSystemUser $imSystemUser): bool
    {
        return $authUser->can('View:ImSystemUser');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ImSystemUser');
    }

    public function update(AuthUser $authUser, ImSystemUser $imSystemUser): bool
    {
        return $authUser->can('Update:ImSystemUser');
    }

    public function delete(AuthUser $authUser, ImSystemUser $imSystemUser): bool
    {
        return $authUser->can('Delete:ImSystemUser');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ImSystemUser');
    }

    public function restore(AuthUser $authUser, ImSystemUser $imSystemUser): bool
    {
        return $authUser->can('Restore:ImSystemUser');
    }

    public function forceDelete(AuthUser $authUser, ImSystemUser $imSystemUser): bool
    {
        return $authUser->can('ForceDelete:ImSystemUser');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ImSystemUser');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ImSystemUser');
    }

    public function replicate(AuthUser $authUser, ImSystemUser $imSystemUser): bool
    {
        return $authUser->can('Replicate:ImSystemUser');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ImSystemUser');
    }
}
