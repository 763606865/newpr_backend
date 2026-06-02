<?php

namespace App\Rc\Controllers;

use App\Models\Area;
use App\Models\Rc\Industry;
use App\Models\Rc\Position;
use App\Resources\Rc\RcAreaResource;
use App\Resources\Rc\RcIndustryResource;
use App\Resources\Rc\RcPositionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    /**
     * 简历填写元数据汇总
     *
     * GET /rc/meta
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        return $this->success([
            'cities' => $this->citiesPayload($request),
            'industries' => $this->industriesPayload($request),
            'positions' => $this->positionsPayload($request),
        ]);
    }

    /**
     * 城市元数据
     *
     * GET /rc/meta/cities
     *
     * @throws \Exception
     */
    public function cities(Request $request): JsonResponse
    {
        return $this->success([
            'cities' => $this->citiesPayload($request),
        ]);
    }

    /**
     * 常用行业元数据
     *
     * GET /rc/meta/industries
     *
     * @throws \Exception
     */
    public function industries(Request $request): JsonResponse
    {
        return $this->success([
            'industries' => $this->industriesPayload($request),
        ]);
    }

    /**
     * 常用职位元数据
     *
     * GET /rc/meta/positions
     *
     * @throws \Exception
     */
    public function positions(Request $request): JsonResponse
    {
        return $this->success([
            'positions' => $this->positionsPayload($request),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function citiesPayload(Request $request): array
    {
        $areas = Area::query()
            ->orderBy('level')
            ->orderBy('code')
            ->get();

        return tree(
            RcAreaResource::collection($areas)->resolve($request),
            'parent_code',
            'code',
            '',
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function industriesPayload(Request $request): array
    {
        $industries = Industry::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return tree(RcIndustryResource::collection($industries)->resolve($request));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function positionsPayload(Request $request): array
    {
        $positions = Position::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return tree(RcPositionResource::collection($positions)->resolve($request));
    }
}
