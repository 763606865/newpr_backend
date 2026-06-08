<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Rc\Application;
use App\Models\Rc\Job;
use App\Models\Rc\UserIdentity;
use App\Rc\Controllers\Controller;
use App\Rc\Requests\JobApplicationStoreRequest;
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
     * 我的投递列表
     *
     * GET /rc/talent/applications
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcApplicationService::make()->paginateForCandidate(
            $this->user(),
            $this->getPerPage($request),
        );

        $paginator->getCollection()->transform(
            static fn (Application $application): array => (new RcApplicationResource($application))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 投递简历
     *
     * POST /rc/talent/jobs/{id}/apply
     */
    public function store(JobApplicationStoreRequest $request, int $id): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $job = RcJobDiscoveryService::make()->findPublicJob($id);

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
     * POST /rc/talent/applications/{id}/withdraw
     */
    public function withdraw(Request $request, int $id): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
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

    private function resolveJobSeekerIdentity(): ?UserIdentity
    {
        return RcIdentityOrganizationService::make()->resolveJobSeekerIdentity($this->user());
    }
}
