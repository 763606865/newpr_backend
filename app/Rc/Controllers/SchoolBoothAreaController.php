<?php

namespace App\Rc\Controllers;

use App\Models\Rc\SchoolBooth;
use App\Models\Rc\SchoolBoothArea;
use App\Models\School;
use App\Rc\Controllers\Concerns\ResolvesRcOrganizations;
use App\Rc\Requests\SchoolBoothAreaStoreRequest;
use App\Rc\Requests\SchoolBoothAreaUpdateRequest;
use App\Resources\Rc\RcSchoolBoothAreaResource;
use App\Services\RcSchoolBoothService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SchoolBoothAreaController extends Controller
{
    use ResolvesRcOrganizations;

    /**
     * 展区列表
     *
     * GET /rc/schools/booths/{boothId}/areas
     */
    public function index(Request $request, int $boothId): JsonResponse
    {
        $booth = $this->resolveBooth($boothId);

        if ($booth instanceof JsonResponse) {
            return $booth;
        }

        $areas = $booth->areas()->ordered()->get();

        return $this->success([
            'areas' => RcSchoolBoothAreaResource::collection($areas)->resolve($request),
        ]);
    }

    /**
     * 展区详情
     *
     * GET /rc/schools/booths/{boothId}/areas/{id}
     */
    public function show(Request $request, int $boothId, int $id): JsonResponse
    {
        $booth = $this->resolveBooth($boothId);

        if ($booth instanceof JsonResponse) {
            return $booth;
        }

        $area = RcSchoolBoothService::make()->findAreaForBooth($booth, $id);

        if (! $area instanceof SchoolBoothArea) {
            return $this->error('展区不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'area' => (new RcSchoolBoothAreaResource($area))->resolve($request),
        ]);
    }

    /**
     * 创建展区
     *
     * POST /rc/schools/booths/{boothId}/areas
     */
    public function store(SchoolBoothAreaStoreRequest $request, int $boothId): JsonResponse
    {
        $booth = $this->resolveBooth($boothId);

        if ($booth instanceof JsonResponse) {
            return $booth;
        }

        $area = RcSchoolBoothService::make()->createArea($booth, $request->validated());

        return $this->success([
            'area' => (new RcSchoolBoothAreaResource($area))->resolve($request),
        ]);
    }

    /**
     * 更新展区
     *
     * PUT /rc/schools/booths/{boothId}/areas/{id}
     */
    public function update(SchoolBoothAreaUpdateRequest $request, int $boothId, int $id): JsonResponse
    {
        $booth = $this->resolveBooth($boothId);

        if ($booth instanceof JsonResponse) {
            return $booth;
        }

        $area = RcSchoolBoothService::make()->findAreaForBooth($booth, $id);

        if (! $area instanceof SchoolBoothArea) {
            return $this->error('展区不存在。', Response::HTTP_NOT_FOUND);
        }

        $area = RcSchoolBoothService::make()->updateArea($area, $request->validated());

        return $this->success([
            'area' => (new RcSchoolBoothAreaResource($area))->resolve($request),
        ]);
    }

    /**
     * 删除展区
     *
     * DELETE /rc/schools/booths/{boothId}/areas/{id}
     */
    public function destroy(int $boothId, int $id): JsonResponse
    {
        $booth = $this->resolveBooth($boothId);

        if ($booth instanceof JsonResponse) {
            return $booth;
        }

        $area = RcSchoolBoothService::make()->findAreaForBooth($booth, $id);

        if (! $area instanceof SchoolBoothArea) {
            return $this->error('展区不存在。', Response::HTTP_NOT_FOUND);
        }

        RcSchoolBoothService::make()->deleteArea($area);

        return $this->success();
    }

    private function resolveBooth(int $boothId): SchoolBooth|JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $booth = RcSchoolBoothService::make()->findForSchool($school, $boothId);

        if (! $booth instanceof SchoolBooth) {
            return $this->error('展位不存在。', Response::HTTP_NOT_FOUND);
        }

        return $booth;
    }
}
