<?php

namespace App\Http\Controllers;

use App\Discovery\Recommendation\SchoolActivityRecommendationContext;
use App\Models\Cms\AdSlot;
use App\Models\Cms\Announcement;
use App\Models\Cms\BannerPosition;
use App\Models\Cms\FriendLink;
use App\Models\Cms\Menu;
use App\Models\Cms\SiteConfig;
use App\Models\Rc\Industry;
use App\Models\Rc\Position;
use App\Resources\Cms\CmsMenuCollection;
use App\Resources\Rc\RcIndustryResource;
use App\Resources\Rc\RcPositionResource;
use App\Resources\Rc\RcSchoolActivityResource;
use App\Services\CmsHomeRecommendationService;
use App\Services\CmsMenuAudienceService;
use App\Services\RcSchoolActivityRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * 菜单列表
     *
     * GET /cms/menus
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $audienceService = CmsMenuAudienceService::make();

        $menus = Menu::query()
            ->enabled()
            ->shown()
            ->forIdentity($audienceService->resolveRcIdentityType($request))
            ->with('menuIdentities')
            ->orderBy('sort')
            ->get();

        return api_response([
            'menus' => (new CmsMenuCollection($menus))->resolve($request)
        ]);
    }
}
