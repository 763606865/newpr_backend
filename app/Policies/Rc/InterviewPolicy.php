<?php

declare(strict_types=1);

namespace App\Policies\Rc;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Rc\Interview;
use Illuminate\Auth\Access\HandlesAuthorization;

class InterviewPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Interview');
    }

    public function view(AuthUser $authUser, Interview $interview): bool
    {
        return $authUser->can('View:Interview');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Interview');
    }

    public function update(AuthUser $authUser, Interview $interview): bool
    {
        return $authUser->can('Update:Interview');
    }

    public function delete(AuthUser $authUser, Interview $interview): bool
    {
        return $authUser->can('Delete:Interview');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Interview');
    }

    public function restore(AuthUser $authUser, Interview $interview): bool
    {
        return $authUser->can('Restore:Interview');
    }

    public function forceDelete(AuthUser $authUser, Interview $interview): bool
    {
        return $authUser->can('ForceDelete:Interview');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Interview');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Interview');
    }

    public function replicate(AuthUser $authUser, Interview $interview): bool
    {
        return $authUser->can('Replicate:Interview');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Interview');
    }

}