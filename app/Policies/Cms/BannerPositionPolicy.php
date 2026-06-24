<?php

declare(strict_types=1);

namespace App\Policies\Cms;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Cms\BannerPosition;
use Illuminate\Auth\Access\HandlesAuthorization;

class BannerPositionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BannerPosition');
    }

    public function view(AuthUser $authUser, BannerPosition $bannerPosition): bool
    {
        return $authUser->can('View:BannerPosition');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BannerPosition');
    }

    public function update(AuthUser $authUser, BannerPosition $bannerPosition): bool
    {
        return $authUser->can('Update:BannerPosition');
    }

    public function delete(AuthUser $authUser, BannerPosition $bannerPosition): bool
    {
        return $authUser->can('Delete:BannerPosition');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BannerPosition');
    }

    public function restore(AuthUser $authUser, BannerPosition $bannerPosition): bool
    {
        return $authUser->can('Restore:BannerPosition');
    }

    public function forceDelete(AuthUser $authUser, BannerPosition $bannerPosition): bool
    {
        return $authUser->can('ForceDelete:BannerPosition');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BannerPosition');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BannerPosition');
    }

    public function replicate(AuthUser $authUser, BannerPosition $bannerPosition): bool
    {
        return $authUser->can('Replicate:BannerPosition');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BannerPosition');
    }

}