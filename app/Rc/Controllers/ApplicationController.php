<?php

namespace App\Rc\Controllers;

use App\Enums\RcIdentityType;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\Job;
use App\Models\Rc\UserIdentity;
use App\Rc\Requests\ApplicationStoreRequest;
use App\Resources\Rc\RcApplicationResource;
use App\Services\RcApplicationService;
use App\Services\RcIdentityOrganizationService;
use App\Services\RcJobDiscoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class ApplicationController extends Controller
{
    /**
     * 投递列表（求职者看自己的，招聘方看本企业的）
     *
     * GET /rc/applications
     */
    public function index(Request $request): JsonResponse
    {
        $service = RcApplicationService::make();
        $perPage = $this->getPerPage($request);

        if (($company = $this->resolveCurrentRecruiterCompany()) instanceof Company) {
            $paginator = $service->paginateForCompany(
                $company,
                $perPage,
                $request->only(['job_id', 'status']),
            );
        } elseif ($this->isCurrentJobSeeker()) {
            $paginator = $service->paginateForCandidate($this->user(), $perPage);
        } else {
            return $this->error('请先切换为求职者或招聘方身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator->getCollection()->transform(
            static fn (Application $application): array => (new RcApplicationResource($application))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 投递详情
     *
     * GET /rc/applications/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $service = RcApplicationService::make();
        $application = null;

        if (($company = $this->resolveCurrentRecruiterCompany()) instanceof Company) {
            $application = $service->findForCompany($company, $id);
        } elseif ($this->isCurrentJobSeeker()) {
            $application = $service->findForCandidate($this->user(), $id);
        } else {
            return $this->error('请先切换为求职者或招聘方身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $application instanceof Application) {
            return $this->error('投递记录不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcApplicationResource($application))->resolve($request));
    }

    /**
     * 投递简历
     *
     * POST /rc/applications
     */
    public function store(ApplicationStoreRequest $request): JsonResponse
    {
        if (! $this->isCurrentJobSeeker()) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $job = RcJobDiscoveryService::make()->findPublicJob((int) $request->validated('job_id'));

        if (! $job instanceof Job) {
            return $this->error('职位不存在或已下架。', Response::HTTP_NOT_FOUND);
        }

        try {
            $application = RcApplicationService::make()->apply(
                $this->user(),
                $job,
                $request->validated('resume_id'),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new RcApplicationResource($application))->resolve($request));
    }

    /**
     * 撤回投递
     *
     * POST /rc/applications/{id}/withdraw
     */
    public function withdraw(Request $request, int $id): JsonResponse
    {
        if (! $this->isCurrentJobSeeker()) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $application = RcApplicationService::make()->findForCandidate($this->user(), $id);

        if (! $application instanceof Application) {
            return $this->error('投递记录不存在。', Response::HTTP_NOT_FOUND);
        }

        try {
            $application = RcApplicationService::make()->withdraw($this->user(), $application);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new RcApplicationResource($application))->resolve($request));
    }

    private function isCurrentJobSeeker(): bool
    {
        $identity = RcIdentityOrganizationService::make()->resolveCurrentIdentity($this->user());

        return $identity?->identity_type === RcIdentityType::JobSeeker;
    }

    private function resolveCurrentRecruiterCompany(): ?Company
    {
        $identity = RcIdentityOrganizationService::make()->resolveCurrentIdentity($this->user());

        if (! $identity instanceof UserIdentity || $identity->identity_type !== RcIdentityType::Recruiter) {
            return null;
        }

        if ($identity->organization_type !== 'company' || ! $identity->organization_id) {
            return null;
        }

        $company = Company::query()->find($identity->organization_id);

        return $company instanceof Company ? $company : null;
    }
}
