<?php

declare(strict_types=1);

namespace App\Policies\Cms;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Cms\AdSlot;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdSlotPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AdSlot');
    }

    public function view(AuthUser $authUser, AdSlot $adSlot): bool
    {
        return $authUser->can('View:AdSlot');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AdSlot');
    }

    public function update(AuthUser $authUser, AdSlot $adSlot): bool
    {
        return $authUser->can('Update:AdSlot');
    }

    public function delete(AuthUser $authUser, AdSlot $adSlot): bool
    {
        return $authUser->can('Delete:AdSlot');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AdSlot');
    }

    public function restore(AuthUser $authUser, AdSlot $adSlot): bool
    {
        return $authUser->can('Restore:AdSlot');
    }

    public function forceDelete(AuthUser $authUser, AdSlot $adSlot): bool
    {
        return $authUser->can('ForceDelete:AdSlot');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AdSlot');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AdSlot');
    }

    public function replicate(AuthUser $authUser, AdSlot $adSlot): bool
    {
        return $authUser->can('Replicate:AdSlot');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AdSlot');
    }

}