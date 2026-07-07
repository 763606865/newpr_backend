<?php

namespace App\Http\Controllers;

use App\Models\Cms\BannerPosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * 轮播图
     *
     * GET /cms/home/banners
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'banner_position_code' => ['required', 'string', 'max:64'],
        ], []);

        $cityCode = $this->resolveCityCode($request);

        $bannerPosition = BannerPosition::query()
            ->enabled()
            ->where('code', '=', $validated['banner_position_code'])
            ->with([
                'banners' => fn ($query) => $query
                    ->enabled()
                    ->forCity($cityCode)
                    ->orderBy('sort')
                    ->orderBy('id'),
            ])
            ->firstOrFail();

        return $this->success([
            'banner_position' => $bannerPosition->makeVisible(['banners']),
            'banners' => $bannerPosition->banners,
        ]);
    }
}
