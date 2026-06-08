<?php

declare(strict_types=1);

namespace App\Policies\Cms;

use App\Models\Cms\SiteConfig;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SiteConfigPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SiteConfig');
    }

    public function view(AuthUser $authUser, SiteConfig $siteConfig): bool
    {
        return $authUser->can('View:SiteConfig');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SiteConfig');
    }

    public function update(AuthUser $authUser, SiteConfig $siteConfig): bool
    {
        return $authUser->can('Update:SiteConfig');
    }

    public function delete(AuthUser $authUser, SiteConfig $siteConfig): bool
    {
        return $authUser->can('Delete:SiteConfig');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SiteConfig');
    }

    public function restore(AuthUser $authUser, SiteConfig $siteConfig): bool
    {
        return $authUser->can('Restore:SiteConfig');
    }

    public function forceDelete(AuthUser $authUser, SiteConfig $siteConfig): bool
    {
        return $authUser->can('ForceDelete:SiteConfig');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SiteConfig');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SiteConfig');
    }

    public function replicate(AuthUser $authUser, SiteConfig $siteConfig): bool
    {
        return $authUser->can('Replicate:SiteConfig');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SiteConfig');
    }
}
