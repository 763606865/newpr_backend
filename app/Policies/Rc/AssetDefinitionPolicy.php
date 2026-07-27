<?php

declare(strict_types=1);

namespace App\Policies\Rc;

use App\Models\Rc\AssetDefinition;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AssetDefinitionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AssetDefinition');
    }

    public function view(AuthUser $authUser, AssetDefinition $assetDefinition): bool
    {
        return $authUser->can('View:AssetDefinition');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AssetDefinition');
    }

    public function update(AuthUser $authUser, AssetDefinition $assetDefinition): bool
    {
        return $authUser->can('Update:AssetDefinition');
    }

    public function delete(AuthUser $authUser, AssetDefinition $assetDefinition): bool
    {
        return $authUser->can('Delete:AssetDefinition');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AssetDefinition');
    }

    public function restore(AuthUser $authUser, AssetDefinition $assetDefinition): bool
    {
        return $authUser->can('Restore:AssetDefinition');
    }

    public function forceDelete(AuthUser $authUser, AssetDefinition $assetDefinition): bool
    {
        return $authUser->can('ForceDelete:AssetDefinition');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AssetDefinition');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AssetDefinition');
    }

    public function replicate(AuthUser $authUser, AssetDefinition $assetDefinition): bool
    {
        return $authUser->can('Replicate:AssetDefinition');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AssetDefinition');
    }
}
