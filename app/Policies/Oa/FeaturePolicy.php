<?php

declare(strict_types=1);

namespace App\Policies\Oa;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Oa\Feature;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeaturePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Feature');
    }

    public function view(AuthUser $authUser, Feature $feature): bool
    {
        return $authUser->can('View:Feature');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Feature');
    }

    public function update(AuthUser $authUser, Feature $feature): bool
    {
        return $authUser->can('Update:Feature');
    }

    public function delete(AuthUser $authUser, Feature $feature): bool
    {
        return $authUser->can('Delete:Feature');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Feature');
    }

    public function restore(AuthUser $authUser, Feature $feature): bool
    {
        return $authUser->can('Restore:Feature');
    }

    public function forceDelete(AuthUser $authUser, Feature $feature): bool
    {
        return $authUser->can('ForceDelete:Feature');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Feature');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Feature');
    }

    public function replicate(AuthUser $authUser, Feature $feature): bool
    {
        return $authUser->can('Replicate:Feature');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Feature');
    }

}