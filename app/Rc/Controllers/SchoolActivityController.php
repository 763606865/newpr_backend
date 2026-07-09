<?php

namespace App\Rc\Controllers;

use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivitySchool;
use App\Models\School;
use App\Rc\Controllers\Concerns\ResolvesRcOrganizations;
use App\Rc\Requests\SchoolActivityStoreRequest;
use App\Rc\Requests\SchoolActivityUpdateRequest;
use App\Resources\Rc\RcSchoolActivityResource;
use App\Resources\Rc\RcSchoolParticipatedActivityResource;
use App\Services\RcSchoolActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class SchoolActivityController extends Controller
{
    use ResolvesRcOrganizations;

    /**
     * 校招负责人-活动列表
     *
     * GET /rc/schools/activities
     */
    public function index(Request $request): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcSchoolActivityService::make()->paginateForSchoolOrganizer(
            $school,
            $this->getPerPage($request),
            [
                'status' => $request->input('status'),
                'type' => $request->input('type'),
                'keyword' => $request->input('keyword'),
            ],
        );

        $paginator->getCollection()->transform(
            fn (SchoolActivity $activity): array => (new RcSchoolActivityResource($activity))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 校招负责人-参与的活动列表
     *
     * GET /rc/schools/activities/participated
     */
    public function participated(Request $request): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcSchoolActivityService::make()->paginateParticipatedForSchool(
            $school,
            $this->getPerPage($request),
            [
                'apply_status' => $request->input('apply_status'),
                'activity_status' => $request->input('activity_status'),
                'type' => $request->input('type'),
                'keyword' => $request->input('keyword'),
            ],
        );

        $paginator->getCollection()->transform(
            fn (SchoolActivitySchool $schoolApplication): array => (new RcSchoolParticipatedActivityResource($schoolApplication))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 校招负责人-活动详情
     *
     * GET /rc/schools/activities/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activity = RcSchoolActivityService::make()->findDetailForSchoolOrganizer($school, $id);

        if (! $activity instanceof SchoolActivity) {
            return $this->error('活动不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'activity' => (new RcSchoolActivityResource($activity))->resolve($request),
        ]);
    }

    /**
     * 创建活动
     *
     * POST /rc/schools/activities
     */
    public function store(SchoolActivityStoreRequest $request): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activity = RcSchoolActivityService::make()->createForSchool($school, $request->validated());

        return $this->success([
            'activity' => (new RcSchoolActivityResource($activity))->resolve($request),
        ]);
    }

    /**
     * 更新活动
     *
     * PUT /rc/schools/activities/{id}
     */
    public function update(SchoolActivityUpdateRequest $request, int $id): JsonResponse
    {
        $activity = $this->resolveOwnedActivity($id);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        try {
            $activity = RcSchoolActivityService::make()->update($activity, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'activity' => (new RcSchoolActivityResource($activity))->resolve($request),
        ]);
    }

    /**
     * 删除活动
     *
     * DELETE /rc/schools/activities/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $activity = $this->resolveOwnedActivity($id);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        try {
            RcSchoolActivityService::make()->delete($activity);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success();
    }

    /**
     * 发布活动
     *
     * POST /rc/schools/activities/{id}/publish
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $activity = $this->resolveOwnedActivity($id);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        try {
            $activity = RcSchoolActivityService::make()->publish($activity);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'activity' => (new RcSchoolActivityResource($activity))->resolve($request),
        ]);
    }

    /**
     * 结束活动
     *
     * POST /rc/schools/activities/{id}/end
     */
    public function end(Request $request, int $id): JsonResponse
    {
        $activity = $this->resolveOwnedActivity($id);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        try {
            $activity = RcSchoolActivityService::make()->end($activity);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'activity' => (new RcSchoolActivityResource($activity))->resolve($request),
        ]);
    }

    private function resolveOwnedActivity(int $activityId): SchoolActivity|JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activity = RcSchoolActivityService::make()->findForSchoolOrganizer($school, $activityId);

        if (! $activity instanceof SchoolActivity) {
            return $this->error('活动不存在。', Response::HTTP_NOT_FOUND);
        }

        return $activity;
    }
}
