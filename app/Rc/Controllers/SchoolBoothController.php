<?php

namespace App\Rc\Controllers;

use App\Models\Rc\SchoolBooth;
use App\Models\School;
use App\Rc\Controllers\Concerns\ResolvesRcOrganizations;
use App\Rc\Requests\SchoolBoothStoreRequest;
use App\Rc\Requests\SchoolBoothUpdateRequest;
use App\Resources\Rc\RcSchoolBoothResource;
use App\Services\RcSchoolBoothService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SchoolBoothController extends Controller
{
    use ResolvesRcOrganizations;

    /**
     * 展位列表
     *
     * GET /rc/schools/booths
     */
    public function index(Request $request): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcSchoolBoothService::make()->paginateForSchool(
            $school,
            $this->getPerPage($request),
            [
                'status' => $request->input('status'),
                'keyword' => $request->input('keyword'),
            ],
        );

        $paginator->getCollection()->transform(
            fn (SchoolBooth $booth): array => (new RcSchoolBoothResource($booth))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 展位详情
     *
     * GET /rc/schools/booths/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $booth = RcSchoolBoothService::make()->findForSchool($school, $id);

        if (! $booth instanceof SchoolBooth) {
            return $this->error('展位不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'booth' => (new RcSchoolBoothResource($booth))->resolve($request),
        ]);
    }

    /**
     * 创建展位
     *
     * POST /rc/schools/booths
     */
    public function store(SchoolBoothStoreRequest $request): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $booth = RcSchoolBoothService::make()->createForSchool($school, $request->validated());

        return $this->success([
            'booth' => (new RcSchoolBoothResource($booth))->resolve($request),
        ]);
    }

    /**
     * 更新展位
     *
     * PUT /rc/schools/booths/{id}
     */
    public function update(SchoolBoothUpdateRequest $request, int $id): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $booth = RcSchoolBoothService::make()->findForSchool($school, $id);

        if (! $booth instanceof SchoolBooth) {
            return $this->error('展位不存在。', Response::HTTP_NOT_FOUND);
        }

        $booth = RcSchoolBoothService::make()->update($booth, $request->validated());

        return $this->success([
            'booth' => (new RcSchoolBoothResource($booth))->resolve($request),
        ]);
    }

    /**
     * 删除展位
     *
     * DELETE /rc/schools/booths/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $booth = RcSchoolBoothService::make()->findForSchool($school, $id);

        if (! $booth instanceof SchoolBooth) {
            return $this->error('展位不存在。', Response::HTTP_NOT_FOUND);
        }

        RcSchoolBoothService::make()->delete($booth);

        return $this->success();
    }
}
