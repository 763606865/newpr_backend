<?php

namespace App\Rc\Controllers;

use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivityCompany;
use App\Models\School;
use App\Rc\Controllers\Concerns\ResolvesRcOrganizations;
use App\Rc\Requests\SchoolActivityCompanyInviteRegisterRequest;
use App\Rc\Requests\SchoolActivityCompanyInviteRequest;
use App\Rc\Requests\SchoolActivityCompanyReviewRequest;
use App\Resources\Rc\RcCompanyResource;
use App\Resources\Rc\RcSchoolActivityCompanyResource;
use App\Resources\Rc\RcSchoolActivityResource;
use App\Services\RcSchoolActivityApplicationService;
use App\Services\RcSchoolActivityService;
use App\Support\SchoolActivityInviteCode;
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

    /**
     * 邀请页-活动详情（无需认证）
     *
     * GET /rc/activities/invite/{inviteCode}
     */
    public function showByInviteCode(Request $request, string $inviteCode): JsonResponse
    {
        $activity = $this->resolveActivityByInviteCode($inviteCode);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        $activity->load('organizer');

        $inviterName = $activity->organizer instanceof School
            ? $activity->organizer->name
            : null;

        return $this->success([
            'inviter_name' => $inviterName,
            'invitation_message' => filled($inviterName)
                ? "{$inviterName}邀请你参加{$activity->title}"
                : "邀请你参加{$activity->title}",
            'activity' => (new RcSchoolActivityResource($activity))->resolve($request),
        ]);
    }

    /**
     * 邀请页-提交企业信息并加入活动（无需认证）
     *
     * POST /rc/activities/invite/{inviteCode}
     */
    public function registerByInviteCode(
        SchoolActivityCompanyInviteRegisterRequest $request,
        string $inviteCode,
    ): JsonResponse {
        $activity = $this->resolveActivityByInviteCode($inviteCode);

        if ($activity instanceof JsonResponse) {
            return $activity;
        }

        $validated = $request->validated();

        try {
            $application = RcSchoolActivityApplicationService::make()->registerCompanyViaInvite(
                $activity,
                [
                    'name' => (string) $validated['name'],
                    'credit_code' => (string) $validated['credit_code'],
                    'contact_phone' => (string) $validated['contact_phone'],
                ],
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $application->load(['company', 'activityBooth']);

        return $this->success([
            'company' => (new RcCompanyResource($application->company))->resolve($request),
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

    private function resolveActivityByInviteCode(string $inviteCode): SchoolActivity|JsonResponse
    {
        $activityId = SchoolActivityInviteCode::decode($inviteCode);

        if ($activityId === null) {
            return $this->error('邀请码无效。', Response::HTTP_NOT_FOUND);
        }

        $activity = RcSchoolActivityService::make()->findPublished($activityId);

        if (! $activity instanceof SchoolActivity) {
            return $this->error('活动不存在或未发布。', Response::HTTP_NOT_FOUND);
        }

        return $activity;
    }
}
