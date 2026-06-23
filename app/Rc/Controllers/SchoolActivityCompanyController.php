<?php

namespace App\Rc\Controllers;

use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivityCompany;
use App\Models\School;
use App\Rc\Controllers\Concerns\ResolvesRcOrganizations;
use App\Rc\Requests\SchoolActivityCompanyInviteRequest;
use App\Rc\Requests\SchoolActivityCompanyReviewRequest;
use App\Resources\Rc\RcSchoolActivityCompanyResource;
use App\Services\RcSchoolActivityApplicationService;
use App\Services\RcSchoolActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class SchoolActivityCompanyController extends Controller
{
    use ResolvesRcOrganizations;

    /**
     * 校招负责人-企业报名列表
     *
     * GET /rc/schools/activities/{activityId}/company-applications
     */
    public function index(Request $request, int $activityId): JsonResponse
    {
        $activity = $this->resolveOwnedActivity($activityId);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        $paginator = RcSchoolActivityApplicationService::make()->paginateCompanyApplications(
            $activity,
            $this->getPerPage($request),
            [
                'apply_status' => $request->input('apply_status'),
                'join_source' => $request->input('join_source'),
            ],
        );

        $paginator->getCollection()->transform(
            fn (SchoolActivityCompany $application): array => (new RcSchoolActivityCompanyResource($application))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 校招负责人-邀约企业
     *
     * POST /rc/schools/activities/{activityId}/company-invitations
     */
    public function invite(SchoolActivityCompanyInviteRequest $request, int $activityId): JsonResponse
    {
        $activity = $this->resolveOwnedActivity($activityId);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        $validated = $request->validated();

        try {
            $application = RcSchoolActivityApplicationService::make()->inviteCompany(
                $activity,
                (int) $validated['company_id'],
                isset($validated['activity_booth_id']) ? (int) $validated['activity_booth_id'] : null,
                $validated['remark'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $application->load(['company', 'activityBooth']);

        return $this->success([
            'application' => (new RcSchoolActivityCompanyResource($application))->resolve($request),
        ]);
    }

    /**
     * 校招负责人-审批通过企业
     *
     * POST /rc/schools/activities/{activityId}/company-applications/{id}/approve
     */
    public function approve(SchoolActivityCompanyReviewRequest $request, int $activityId, int $id): JsonResponse
    {
        $application = $this->resolveOwnedApplication($activityId, $id);

        if ($application instanceof JsonResponse) {
            return $application;
        }

        $validated = $request->validated();

        try {
            $application = RcSchoolActivityApplicationService::make()->approveCompanyApplication(
                $application,
                isset($validated['activity_booth_id']) ? (int) $validated['activity_booth_id'] : null,
                $validated['remark'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $application->load(['company', 'activityBooth']);

        return $this->success([
            'application' => (new RcSchoolActivityCompanyResource($application))->resolve($request),
        ]);
    }

    /**
     * 校招负责人-驳回企业
     *
     * POST /rc/schools/activities/{activityId}/company-applications/{id}/reject
     */
    public function reject(SchoolActivityCompanyReviewRequest $request, int $activityId, int $id): JsonResponse
    {
        $application = $this->resolveOwnedApplication($activityId, $id);

        if ($application instanceof JsonResponse) {
            return $application;
        }

        try {
            $application = RcSchoolActivityApplicationService::make()->rejectCompanyApplication(
                $application,
                $request->validated()['remark'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $application->load(['company', 'activityBooth']);

        return $this->success([
            'application' => (new RcSchoolActivityCompanyResource($application))->resolve($request),
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

    private function resolveOwnedApplication(int $activityId, int $applicationId): SchoolActivityCompany|JsonResponse
    {
        $activity = $this->resolveOwnedActivity($activityId);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        $application = RcSchoolActivityApplicationService::make()->findCompanyApplicationById($activity, $applicationId);

        if (! $application instanceof SchoolActivityCompany) {
            return $this->error('企业报名记录不存在。', Response::HTTP_NOT_FOUND);
        }

        return $application;
    }
}
