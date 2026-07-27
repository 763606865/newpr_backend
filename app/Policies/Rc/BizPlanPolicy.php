<?php

declare(strict_types=1);

namespace App\Policies\Rc;

use App\Models\Rc\BizPlan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BizPlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BizPlan');
    }

    public function view(AuthUser $authUser, BizPlan $bizPlan): bool
    {
        return $authUser->can('View:BizPlan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BizPlan');
    }

    public function update(AuthUser $authUser, BizPlan $bizPlan): bool
    {
        return $authUser->can('Update:BizPlan');
    }

    public function delete(AuthUser $authUser, BizPlan $bizPlan): bool
    {
        return $authUser->can('Delete:BizPlan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BizPlan');
    }

    public function restore(AuthUser $authUser, BizPlan $bizPlan): bool
    {
        return $authUser->can('Restore:BizPlan');
    }

    public function forceDelete(AuthUser $authUser, BizPlan $bizPlan): bool
    {
        return $authUser->can('ForceDelete:BizPlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BizPlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BizPlan');
    }

    public function replicate(AuthUser $authUser, BizPlan $bizPlan): bool
    {
        return $authUser->can('Replicate:BizPlan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BizPlan');
    }
}
