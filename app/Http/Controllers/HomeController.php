<?php

namespace App\Http\Controllers;

use App\Models\Cms\AdSlot;
use App\Models\Cms\Announcement;
use App\Models\Cms\BannerPosition;
use App\Models\Cms\FriendLink;
use App\Models\Cms\Menu;
use App\Models\Cms\SiteConfig;
use App\Resources\Cms\CmsMenuCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * 首页内容
     *
     * GET /home
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $cityCode = $request->string('city_code')->toString();
        $cityCode = $cityCode !== '' ? $cityCode : null;

        $menus = Menu::query()->enabled()->shown()->orderBy('sort')->get();
        $bannerPosition = BannerPosition::query()
            ->enabled()
            ->with([
                'banners' => fn ($query) => $query->enabled()->orderBy('sort'),
            ])
            ->where('code', '=', 'zcgz.index.banner-1')
            ->first();

        $adSlot = AdSlot::query()
            ->with([
                'ads' => fn ($query) => $query->enabled()->orderBy('sort'),
            ])
            ->enabled()
            ->whereLike('code', '%index.%')
            ->orderBy('sort')
            ->get();

        $siteConfig = SiteConfig::query()
            ->forCity($cityCode)
            ->enabled()
            ->first();

        $friendLinks = FriendLink::query()
            ->forCity($cityCode)
            ->enabled()
            ->orderBy('sort')
            ->get()
            ->setVisible([
                'id',
                'name',
                'url',
                'logo',
                'target',
            ]);

        return api_response([
            'menus' => new CmsMenuCollection($menus),
            'banner_position' => $bannerPosition?->makeVisible(['banners']),
            'ad_slot' => $adSlot->makeVisible(['ads']),
            'site_config' => $siteConfig,
            'friend_links' => $friendLinks,
        ]);
    }

    /**
     * 首页公告简介（最多 10 条）
     *
     * GET /home/announcements
     *
     * @throws \Exception
     */
    public function announcement(Request $request): JsonResponse
    {
        $cityCode = $request->string('city_code')->toString();
        $cityCode = $cityCode !== '' ? $cityCode : null;

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
}
