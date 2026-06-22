<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolActivityIndexRequest;
use App\Models\Rc\SchoolActivity;
use App\Resources\Cms\CmsSchoolActivityResource;
use App\Services\CmsSchoolActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolActivityController extends Controller
{
    /**
     * 校园活动列表（分页）
     *
     * GET /cms/school-activities
     *
     * @throws \Exception
     */
    public function index(SchoolActivityIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 50));

        $paginator = CmsSchoolActivityService::make()->paginate(
            $perPage,
            $request->searchFilters(),
        );

        $paginator->getCollection()->transform(
            fn (SchoolActivity $activity): array => (new CmsSchoolActivityResource($activity))->resolve($request),
        );

        return api_response($paginator);
    }

    /**
     * 校园活动详情
     *
     * GET /cms/school-activities/{id}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $activity = CmsSchoolActivityService::make()->findPublished(
            $id,
            $this->resolveRegionCode($request),
        );

        if (! $activity instanceof SchoolActivity) {
            abort(404);
        }

        return api_response((new CmsSchoolActivityResource($activity))->resolve($request));
    }

    private function resolveRegionCode(Request $request): ?string
    {
        $districtCode = $request->string('district_code')->toString();

        if ($districtCode !== '') {
            return $districtCode;
        }

        $cityCode = $request->string('city_code')->toString();

        if ($cityCode !== '') {
            return $cityCode;
        }

        $provinceCode = $request->string('province_code')->toString();

        if ($provinceCode !== '') {
            return $provinceCode;
        }

        return null;
    }
}
