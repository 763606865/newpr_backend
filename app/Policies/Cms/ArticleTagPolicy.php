<?php

declare(strict_types=1);

namespace App\Policies\Cms;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Cms\ArticleTag;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticleTagPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ArticleTag');
    }

    public function view(AuthUser $authUser, ArticleTag $articleTag): bool
    {
        return $authUser->can('View:ArticleTag');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ArticleTag');
    }

    public function update(AuthUser $authUser, ArticleTag $articleTag): bool
    {
        return $authUser->can('Update:ArticleTag');
    }

    public function delete(AuthUser $authUser, ArticleTag $articleTag): bool
    {
        return $authUser->can('Delete:ArticleTag');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ArticleTag');
    }

    public function restore(AuthUser $authUser, ArticleTag $articleTag): bool
    {
        return $authUser->can('Restore:ArticleTag');
    }

    public function forceDelete(AuthUser $authUser, ArticleTag $articleTag): bool
    {
        return $authUser->can('ForceDelete:ArticleTag');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ArticleTag');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ArticleTag');
    }

    public function replicate(AuthUser $authUser, ArticleTag $articleTag): bool
    {
        return $authUser->can('Replicate:ArticleTag');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ArticleTag');
    }

}