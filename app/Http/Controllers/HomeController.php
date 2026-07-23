<?php

namespace App\Http\Controllers;

use App\Discovery\Recommendation\SchoolActivityRecommendationContext;
use App\Models\Cms\AdSlot;
use App\Models\Cms\Announcement;
use App\Models\Cms\BannerPosition;
use App\Models\Rc\Industry;
use App\Models\Rc\Position;
use App\Resources\Rc\RcIndustryResource;
use App\Resources\Rc\RcPositionResource;
use App\Resources\Rc\RcSchoolActivityResource;
use App\Services\CmsHomeRecommendationService;
use App\Services\RcSchoolActivityRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * 首页内容
     *
     * GET /cms/home
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $cityCode = $this->resolveCityCode($request);

        $bannerPosition = BannerPosition::query()
            ->enabled()
            ->with([
                'banners' => fn ($query) => $query->enabled()->orderBy('sort'),
            ])
            ->whereLike('code', 'zcyp.index.%')
            ->get();

        $adSlot = AdSlot::query()
            ->with([
                'ads' => fn ($query) => $query->enabled()->orderBy('sort'),
            ])
            ->enabled()
            ->whereLike('code', '%index.%')
            ->orderBy('sort')
            ->get();

        $homeRecommendations = CmsHomeRecommendationService::make()->groupedForHome($cityCode, $request);

        return api_response([
            'banner_position' => $bannerPosition?->makeVisible(['banners']),
            'ad_slot' => $adSlot->makeVisible(['ads']),
            'urgent_jobs' => $homeRecommendations['urgent_jobs'],
            'hot_jobs' => $homeRecommendations['hot_jobs'],
            'famous_companies' => $homeRecommendations['famous_companies'],
        ]);
    }

    /**
     * 首页公告简介（最多 10 条）
     *
     * GET /cms/home/announcements
     *
     * @throws \Exception
     */
    public function announcement(Request $request): JsonResponse
    {
        $cityCode = $this->resolveCityCode($request);

        $bannerPosition = BannerPosition::query()
            ->enabled()
            ->where('code', '=', 'zcgz.announcement.banner-1')
            ->with([
                'banners' => fn ($query) => $query->enabled()->forCity($cityCode)->orderBy('sort'),
            ])
            ->first();

        $adSlot = AdSlot::query()
            ->enabled()
            ->whereLike('code', '%announcement.%')
            ->with([
                'ads' => fn ($query) => $query->enabled()->forCity($cityCode)->orderBy('sort'),
            ])
            ->orderBy('sort')
            ->get();

        $announcements = Announcement::query()
            ->enabled()
            ->forCity($cityCode)
            ->orderByDesc('is_top')
            ->orderBy('sort')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get([
                'id',
                'city_code',
                'title',
                'sub_title',
                'summary',
                'link_url',
                'type',
                'source_name',
                'published_at',
                'is_top',
            ]);

        return api_response([
            'banner_position' => $bannerPosition?->makeVisible(['banners']),
            'ad_slot' => $adSlot->makeVisible(['ads']),
            'announcements' => $announcements,
        ]);
    }

    /**
     * 首页岗位信息
     *
     * GET /cms/home/rc/positions
     *
     * @throws \Exception
     */
    public function position(Request $request): JsonResponse
    {
        $positions = Position::query()
            ->whereNotIn('code', ['115pumPa', '11fv7tR']) // 过滤掉【不限岗位】
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $payloads = RcPositionResource::collection($positions)->resolve($request);

        return api_response([
            'positions' => tree($payloads),
        ]);
    }

    /**
     * 首页行业信息
     *
     * GET /cms/home/rc/industries
     *
     * @throws \Exception
     */
    public function industry(Request $request): JsonResponse
    {
        $industries = Industry::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $payloads = RcIndustryResource::collection($industries)->resolve($request);

        return api_response([
            'industries' => tree($payloads),
        ]);
    }

    /**
     * 中测校园
     *
     * GET /cms/home/schools
     *
     * @throws \Exception
     */
    public function school(Request $request): JsonResponse
    {
        $cityCode = $this->resolveCityCode($request);

        $bannerPosition = BannerPosition::query()
            ->enabled()
            ->where('code', '=', 'zcyp.school.banner-1')
            ->with([
                'banners' => fn ($query) => $query->enabled()->forCity($cityCode)->orderBy('sort'),
            ])
            ->first();

        $adSlot = AdSlot::query()
            ->enabled()
            ->whereLike('code', '%school.%')
            ->with([
                'ads' => fn ($query) => $query->enabled()->forCity($cityCode)->orderBy('sort'),
            ])
            ->orderBy('sort')
            ->get();

        $recommendations = RcSchoolActivityRecommendationService::make()->recommendGrouped(
            new SchoolActivityRecommendationContext(cityHint: $cityCode),
        );

        return api_response([
            'banner_position' => $bannerPosition?->makeVisible(['banners']),
            'ad_slot' => $adSlot->makeVisible(['ads']),
            'dual_selections' => RcSchoolActivityResource::collection($recommendations['dual_selections'])->resolve($request),
            'presentations' => RcSchoolActivityResource::collection($recommendations['presentations'])->resolve($request),
            'job_fairs' => RcSchoolActivityResource::collection($recommendations['job_fairs'])->resolve($request),
            'recommendation' => $recommendations['criteria']->toRecommendationMeta(),
        ]);
    }
}
