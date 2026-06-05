<?php

namespace App\Rc\Controllers;

use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\User;
use App\Rc\Requests\JobStoreRequest;
use App\Rc\Requests\JobUpdateRequest;
use App\Resources\Rc\RcJobResource;
use App\Services\RcJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class JobController extends Controller
{
    /**
     * 职位列表
     *
     * GET /rc/jobs
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcJobService::make()->paginateForCompany(
            $company,
            $this->getPerPage($request),
            [
                'status' => $request->input('status'),
                'employment_type' => $request->input('employment_type'),
                'keyword' => $request->input('keyword'),
            ],
        );

        $paginator->getCollection()->transform(
            static fn (Job $job): array => (new RcJobResource($job))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 职位详情
     *
     * GET /rc/jobs/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $job = RcJobService::make()->findForCompany($company, $id);

        if (! $job instanceof Job) {
            return $this->error('职位不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcJobResource($job))->resolve($request));
    }

    /**
     * 创建职位（草稿或发布）
     *
     * POST /rc/jobs
     */
    public function store(JobStoreRequest $request): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var User $user */
        $user = $this->user();
        $service = RcJobService::make();

        try {
            $job = $service->create($user, $company, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new RcJobResource($job))->resolve($request));
    }

    /**
     * 更新职位
     *
     * PUT /rc/jobs/{id}
     */
    public function update(JobUpdateRequest $request, int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $job = RcJobService::make()->findForCompany($company, $id);

        if (! $job instanceof Job) {
            return $this->error('职位不存在。', Response::HTTP_NOT_FOUND);
        }

        try {
            $job = RcJobService::make()->update($job, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new RcJobResource($job))->resolve($request));
    }

    /**
     * 发布职位
     *
     * POST /rc/jobs/{id}/publish
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $job = RcJobService::make()->findForCompany($company, $id);

        if (! $job instanceof Job) {
            return $this->error('职位不存在。', Response::HTTP_NOT_FOUND);
        }

        if ($job->status === RcJobStatus::Published) {
            return $this->error('职位已发布，无需重复操作。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $job = RcJobService::make()->publish($job);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new RcJobResource($job))->resolve($request));
    }

    /**
     * 暂停职位
     *
     * POST /rc/jobs/{id}/pause
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $job = RcJobService::make()->findForCompany($company, $id);

        if (! $job instanceof Job) {
            return $this->error('职位不存在。', Response::HTTP_NOT_FOUND);
        }

        $job = RcJobService::make()->pause($job);

        return $this->success((new RcJobResource($job))->resolve($request));
    }

    /**
     * 关闭职位
     *
     * POST /rc/jobs/{id}/close
     */
    public function close(Request $request, int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $job = RcJobService::make()->findForCompany($company, $id);

        if (! $job instanceof Job) {
            return $this->error('职位不存在。', Response::HTTP_NOT_FOUND);
        }

        $job = RcJobService::make()->close($job);

        return $this->success((new RcJobResource($job))->resolve($request));
    }

    /**
     * 删除职位
     *
     * DELETE /rc/jobs/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $job = RcJobService::make()->findForCompany($company, $id);

        if (! $job instanceof Job) {
            return $this->error('职位不存在。', Response::HTTP_NOT_FOUND);
        }

        RcJobService::make()->delete($job);

        return $this->success();
    }

    private function resolveCompany(): ?Company
    {
        /** @var User $user */
        $user = $this->user();

        return RcJobService::make()->resolveRecruiterCompany($user);
    }
}
