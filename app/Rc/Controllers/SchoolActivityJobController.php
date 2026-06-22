<?php

namespace App\Rc\Controllers;

use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivityJob;
use App\Models\School;
use App\Rc\Controllers\Concerns\ResolvesRcOrganizations;
use App\Rc\Requests\SchoolActivityJobReviewRequest;
use App\Resources\Rc\RcSchoolActivityJobResource;
use App\Services\RcSchoolActivityApplicationService;
use App\Services\RcSchoolActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class SchoolActivityJobController extends Controller
{
    use ResolvesRcOrganizations;

    /**
     * 校招负责人-活动职位列表
     *
     * GET /rc/schools/activities/{activityId}/job-applications
     */
    public function index(Request $request, int $activityId): JsonResponse
    {
        $activity = $this->resolveOwnedActivity($activityId);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        $paginator = RcSchoolActivityApplicationService::make()->paginateActivityJobs(
            $activity,
            $this->getPerPage($request),
            [
                'audit_status' => $request->input('audit_status'),
                'company_id' => $request->input('company_id'),
            ],
        );

        $paginator->getCollection()->transform(
            fn (SchoolActivityJob $activityJob): array => (new RcSchoolActivityJobResource($activityJob))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 校招负责人-审批通过职位
     *
     * POST /rc/schools/activities/{activityId}/job-applications/{id}/approve
     */
    public function approve(Request $request, int $activityId, int $id): JsonResponse
    {
        $activityJob = $this->resolveOwnedActivityJob($activityId, $id);

        if ($activityJob instanceof JsonResponse) {
            return $activityJob;
        }

        try {
            $activityJob = RcSchoolActivityApplicationService::make()->approveActivityJob($activityJob);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activityJob->load('job');

        return $this->success([
            'activity_job' => (new RcSchoolActivityJobResource($activityJob))->resolve($request),
        ]);
    }

    /**
     * 校招负责人-驳回职位
     *
     * POST /rc/schools/activities/{activityId}/job-applications/{id}/reject
     */
    public function reject(SchoolActivityJobReviewRequest $request, int $activityId, int $id): JsonResponse
    {
        $activityJob = $this->resolveOwnedActivityJob($activityId, $id);

        if ($activityJob instanceof JsonResponse) {
            return $activityJob;
        }

        try {
            $activityJob = RcSchoolActivityApplicationService::make()->rejectActivityJob(
                $activityJob,
                (string) $request->validated()['reject_reason'],
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activityJob->load('job');

        return $this->success([
            'activity_job' => (new RcSchoolActivityJobResource($activityJob))->resolve($request),
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

    private function resolveOwnedActivityJob(int $activityId, int $activityJobId): SchoolActivityJob|JsonResponse
    {
        $activity = $this->resolveOwnedActivity($activityId);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        $activityJob = RcSchoolActivityApplicationService::make()->findActivityJob($activity, $activityJobId);

        if (! $activityJob instanceof SchoolActivityJob) {
            return $this->error('活动职位记录不存在。', Response::HTTP_NOT_FOUND);
        }

        return $activityJob;
    }
}
