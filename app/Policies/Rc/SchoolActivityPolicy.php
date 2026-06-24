<?php

declare(strict_types=1);

namespace App\Policies\Rc;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Rc\SchoolActivity;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolActivityPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SchoolActivity');
    }

    public function view(AuthUser $authUser, SchoolActivity $schoolActivity): bool
    {
        return $authUser->can('View:SchoolActivity');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SchoolActivity');
    }

    public function update(AuthUser $authUser, SchoolActivity $schoolActivity): bool
    {
        return $authUser->can('Update:SchoolActivity');
    }

    public function delete(AuthUser $authUser, SchoolActivity $schoolActivity): bool
    {
        return $authUser->can('Delete:SchoolActivity');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SchoolActivity');
    }

    public function restore(AuthUser $authUser, SchoolActivity $schoolActivity): bool
    {
        return $authUser->can('Restore:SchoolActivity');
    }

    public function forceDelete(AuthUser $authUser, SchoolActivity $schoolActivity): bool
    {
        return $authUser->can('ForceDelete:SchoolActivity');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SchoolActivity');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SchoolActivity');
    }

    public function replicate(AuthUser $authUser, SchoolActivity $schoolActivity): bool
    {
        return $authUser->can('Replicate:SchoolActivity');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SchoolActivity');
    }

}