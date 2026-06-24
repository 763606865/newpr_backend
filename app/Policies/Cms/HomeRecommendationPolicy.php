<?php

declare(strict_types=1);

namespace App\Policies\Cms;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Cms\HomeRecommendation;
use Illuminate\Auth\Access\HandlesAuthorization;

class HomeRecommendationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HomeRecommendation');
    }

    public function view(AuthUser $authUser, HomeRecommendation $homeRecommendation): bool
    {
        return $authUser->can('View:HomeRecommendation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HomeRecommendation');
    }

    public function update(AuthUser $authUser, HomeRecommendation $homeRecommendation): bool
    {
        return $authUser->can('Update:HomeRecommendation');
    }

    public function delete(AuthUser $authUser, HomeRecommendation $homeRecommendation): bool
    {
        return $authUser->can('Delete:HomeRecommendation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HomeRecommendation');
    }

    public function restore(AuthUser $authUser, HomeRecommendation $homeRecommendation): bool
    {
        return $authUser->can('Restore:HomeRecommendation');
    }

    public function forceDelete(AuthUser $authUser, HomeRecommendation $homeRecommendation): bool
    {
        return $authUser->can('ForceDelete:HomeRecommendation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HomeRecommendation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HomeRecommendation');
    }

    public function replicate(AuthUser $authUser, HomeRecommendation $homeRecommendation): bool
    {
        return $authUser->can('Replicate:HomeRecommendation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HomeRecommendation');
    }

}