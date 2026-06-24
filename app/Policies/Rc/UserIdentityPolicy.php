<?php

declare(strict_types=1);

namespace App\Policies\Rc;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Rc\UserIdentity;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserIdentityPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UserIdentity');
    }

    public function view(AuthUser $authUser, UserIdentity $userIdentity): bool
    {
        return $authUser->can('View:UserIdentity');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:UserIdentity');
    }

    public function update(AuthUser $authUser, UserIdentity $userIdentity): bool
    {
        return $authUser->can('Update:UserIdentity');
    }

    public function delete(AuthUser $authUser, UserIdentity $userIdentity): bool
    {
        return $authUser->can('Delete:UserIdentity');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:UserIdentity');
    }

    public function restore(AuthUser $authUser, UserIdentity $userIdentity): bool
    {
        return $authUser->can('Restore:UserIdentity');
    }

    public function forceDelete(AuthUser $authUser, UserIdentity $userIdentity): bool
    {
        return $authUser->can('ForceDelete:UserIdentity');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:UserIdentity');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:UserIdentity');
    }

    public function replicate(AuthUser $authUser, UserIdentity $userIdentity): bool
    {
        return $authUser->can('Replicate:UserIdentity');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:UserIdentity');
    }

}