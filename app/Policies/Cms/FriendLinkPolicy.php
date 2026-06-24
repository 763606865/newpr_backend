<?php

declare(strict_types=1);

namespace App\Policies\Cms;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Cms\FriendLink;
use Illuminate\Auth\Access\HandlesAuthorization;

class FriendLinkPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FriendLink');
    }

    public function view(AuthUser $authUser, FriendLink $friendLink): bool
    {
        return $authUser->can('View:FriendLink');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FriendLink');
    }

    public function update(AuthUser $authUser, FriendLink $friendLink): bool
    {
        return $authUser->can('Update:FriendLink');
    }

    public function delete(AuthUser $authUser, FriendLink $friendLink): bool
    {
        return $authUser->can('Delete:FriendLink');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FriendLink');
    }

    public function restore(AuthUser $authUser, FriendLink $friendLink): bool
    {
        return $authUser->can('Restore:FriendLink');
    }

    public function forceDelete(AuthUser $authUser, FriendLink $friendLink): bool
    {
        return $authUser->can('ForceDelete:FriendLink');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FriendLink');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FriendLink');
    }

    public function replicate(AuthUser $authUser, FriendLink $friendLink): bool
    {
        return $authUser->can('Replicate:FriendLink');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FriendLink');
    }

}