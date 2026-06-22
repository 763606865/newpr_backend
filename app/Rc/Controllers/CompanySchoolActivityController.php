<?php

namespace App\Rc\Controllers;

use App\Models\Company;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivityCompany;
use App\Models\Rc\SchoolActivityJob;
use App\Rc\Controllers\Concerns\ResolvesRcOrganizations;
use App\Rc\Requests\SchoolActivityCompanyApplyRequest;
use App\Rc\Requests\SchoolActivityJobSubmitRequest;
use App\Resources\Rc\RcRecruiterParticipatedActivityResource;
use App\Resources\Rc\RcSchoolActivityCompanyResource;
use App\Resources\Rc\RcSchoolActivityJobResource;
use App\Resources\Rc\RcSchoolActivityResource;
use App\Services\RcSchoolActivityApplicationService;
use App\Services\RcSchoolActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class CompanySchoolActivityController extends Controller
{
    use ResolvesRcOrganizations;

    /**
     * 我企业参与的活动列表
     *
     * GET /rc/companies/school-activities
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveRecruiterCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcSchoolActivityApplicationService::make()->paginateParticipatedForCompany(
            $company,
            $this->getPerPage($request),
            [
                'apply_status' => $request->input('apply_status'),
                'activity_status' => $request->input('activity_status'),
                'type' => $request->input('type'),
                'keyword' => $request->input('keyword'),
            ],
        );

        $paginator->getCollection()->transform(
            fn ($application): array => (new RcRecruiterParticipatedActivityResource($application))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 可报名活动列表
     *
     * GET /rc/companies/school-activities/available
     */
    public function available(Request $request): JsonResponse
    {
        if ($this->resolveRecruiterCompany() === null) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcSchoolActivityService::make()->paginateAvailableForRecruiter(
            $this->getPerPage($request),
            [
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
     * 活动详情
     *
     * GET /rc/companies/school-activities/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if ($this->resolveRecruiterCompany() === null) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activity = RcSchoolActivityService::make()->findPublished($id);

        if (! $activity instanceof SchoolActivity) {
            return $this->error('活动不存在或未发布。', Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'activity' => (new RcSchoolActivityResource($activity))->resolve($request),
        ]);
    }

    /**
     * 申请参加活动
     *
     * POST /rc/companies/school-activities/{activityId}/apply
     */
    public function apply(SchoolActivityCompanyApplyRequest $request, int $activityId): JsonResponse
    {
        $company = $this->resolveRecruiterCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activity = RcSchoolActivityService::make()->findPublished($activityId);

        if (! $activity instanceof SchoolActivity) {
            return $this->error('活动不存在或未发布。', Response::HTTP_NOT_FOUND);
        }

        try {
            $application = RcSchoolActivityApplicationService::make()->applyAsCompany(
                $activity,
                $company,
                $request->validated()['remark'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'application' => (new RcSchoolActivityCompanyResource($application))->resolve($request),
        ]);
    }

    /**
     * 查看本企业报名状态
     *
     * GET /rc/companies/school-activities/{activityId}/my-application
     */
    public function myApplication(Request $request, int $activityId): JsonResponse
    {
        $company = $this->resolveRecruiterCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activity = RcSchoolActivityService::make()->findPublished($activityId);

        if (! $activity instanceof SchoolActivity) {
            return $this->error('活动不存在或未发布。', Response::HTTP_NOT_FOUND);
        }

        $application = RcSchoolActivityApplicationService::make()->findCompanyApplication($activity, $company);

        if (! $application instanceof SchoolActivityCompany) {
            return $this->error('尚未申请参加该活动。', Response::HTTP_NOT_FOUND);
        }

        $application->load(['activityBooth', 'activityJobs.job']);

        return $this->success([
            'application' => (new RcSchoolActivityCompanyResource($application))->resolve($request),
        ]);
    }

    /**
     * 提交参加活动职位
     *
     * POST /rc/companies/school-activities/{activityId}/jobs
     */
    public function store(SchoolActivityJobSubmitRequest $request, int $activityId): JsonResponse
    {
        $company = $this->resolveRecruiterCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activity = RcSchoolActivityService::make()->findPublished($activityId);

        if (! $activity instanceof SchoolActivity) {
            return $this->error('活动不存在或未发布。', Response::HTTP_NOT_FOUND);
        }

        $application = RcSchoolActivityApplicationService::make()->findCompanyApplication($activity, $company);

        if (! $application instanceof SchoolActivityCompany) {
            return $this->error('请先申请参加活动并通过审批。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $activityJobs = RcSchoolActivityApplicationService::make()->submitJobs(
                $application,
                $request->validated()['job_ids'],
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'activity_jobs' => RcSchoolActivityJobResource::collection($activityJobs)->resolve($request),
        ]);
    }

    /**
     * 查看本企业已提交职位
     *
     * GET /rc/companies/school-activities/{activityId}/jobs
     */
    public function myJobs(Request $request, int $activityId): JsonResponse
    {
        $company = $this->resolveRecruiterCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $activity = RcSchoolActivityService::make()->findPublished($activityId);

        if (! $activity instanceof SchoolActivity) {
            return $this->error('活动不存在或未发布。', Response::HTTP_NOT_FOUND);
        }

        $application = RcSchoolActivityApplicationService::make()->findCompanyApplication($activity, $company);

        if (! $application instanceof SchoolActivityCompany) {
            return $this->error('尚未申请参加该活动。', Response::HTTP_NOT_FOUND);
        }

        $paginator = RcSchoolActivityApplicationService::make()->paginateCompanyActivityJobs(
            $application,
            $this->getPerPage($request),
        );

        $paginator->getCollection()->transform(
            fn (SchoolActivityJob $activityJob): array => (new RcSchoolActivityJobResource($activityJob))->resolve($request),
        );

        return $this->success($paginator);
    }
}
