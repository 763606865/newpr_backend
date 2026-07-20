<?php

namespace App\Http\Controllers;

use App\Models\Cms\SiteConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteConfigController extends Controller
{
    /**
     * 站点配置
     *
     * GET /cms/site-configs
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $cityCode = $this->resolveCityCode($request);

        $siteConfig = SiteConfig::query()
            ->forCity($cityCode)
            ->enabled()
            ->first();

        return api_response($siteConfig);
    }
}
