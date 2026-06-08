<?php

declare(strict_types=1);

namespace App\Policies\Rc;

use App\Models\Rc\Resume;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ResumePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Resume');
    }

    public function view(AuthUser $authUser, Resume $resume): bool
    {
        return $authUser->can('View:Resume');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Resume');
    }

    public function update(AuthUser $authUser, Resume $resume): bool
    {
        return $authUser->can('Update:Resume');
    }

    public function delete(AuthUser $authUser, Resume $resume): bool
    {
        return $authUser->can('Delete:Resume');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Resume');
    }

    public function restore(AuthUser $authUser, Resume $resume): bool
    {
        return $authUser->can('Restore:Resume');
    }

    public function forceDelete(AuthUser $authUser, Resume $resume): bool
    {
        return $authUser->can('ForceDelete:Resume');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Resume');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Resume');
    }

    public function replicate(AuthUser $authUser, Resume $resume): bool
    {
        return $authUser->can('Replicate:Resume');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Resume');
    }
}
