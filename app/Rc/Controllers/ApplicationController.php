<?php

namespace App\Rc\Controllers;

use App\Enums\RcIdentityType;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\Job;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Rc\Requests\ApplicationCheckRequest;
use App\Rc\Requests\ApplicationHireRequest;
use App\Rc\Requests\ApplicationInviteInterviewRequest;
use App\Rc\Requests\ApplicationRejectRequest;
use App\Rc\Requests\ApplicationRespondInterviewRequest;
use App\Rc\Requests\ApplicationRespondOfferRequest;
use App\Rc\Requests\ApplicationSendOfferRequest;
use App\Rc\Requests\ApplicationStoreRequest;
use App\Resources\Rc\RcApplicationResource;
use App\Services\RcApplicationService;
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

            if ($application instanceof Application) {
                $application = $service->markScreeningOnRecruiterView($this->user(), $application);
            }
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

    /**
     * 接受面试邀请
     *
     * POST /rc/applications/{id}/accept-interview
     */
    public function acceptInterview(ApplicationRespondInterviewRequest $request, int $id): JsonResponse
    {
        return $this->handleJobSeekerFlowAction(
            $request,
            $id,
            static fn (RcApplicationService $service, User $user, Application $application): Application => $service->acceptInterviewInvitation(
                $user,
                $application,
            ),
        );
    }

    /**
     * 拒绝面试邀请
     *
     * POST /rc/applications/{id}/reject-interview
     */
    public function rejectInterview(ApplicationRespondInterviewRequest $request, int $id): JsonResponse
    {
        return $this->handleJobSeekerFlowAction(
            $request,
            $id,
            static fn (RcApplicationService $service, User $user, Application $application): Application => $service->rejectInterviewInvitation(
                $user,
                $application,
                $request->validated('note'),
            ),
        );
    }

    /**
     * 接受 Offer
     *
     * POST /rc/applications/{id}/accept-offer
     */
    public function acceptOffer(ApplicationRespondOfferRequest $request, int $id): JsonResponse
    {
        return $this->handleJobSeekerFlowAction(
            $request,
            $id,
            static fn (RcApplicationService $service, User $user, Application $application): Application => $service->acceptOfferInvitation(
                $user,
                $application,
                $request->validated('note'),
            ),
        );
    }

    /**
     * 拒绝 Offer
     *
     * POST /rc/applications/{id}/reject-offer
     */
    public function rejectOffer(ApplicationRespondOfferRequest $request, int $id): JsonResponse
    {
        return $this->handleJobSeekerFlowAction(
            $request,
            $id,
            static fn (RcApplicationService $service, User $user, Application $application): Application => $service->rejectOfferInvitation(
                $user,
                $application,
                $request->validated('note'),
            ),
        );
    }

    /**
     * 邀请面试
     *
     * POST /rc/applications/{id}/invite-interview
     */
    public function inviteInterview(ApplicationInviteInterviewRequest $request, int $id): JsonResponse
    {
        return $this->handleRecruiterFlowAction(
            $request,
            $id,
            static fn (RcApplicationService $service, User $user, Application $application): Application => $service->inviteInterview(
                $user,
                $application,
                $request->validated(),
            ),
        );
    }

    /**
     * 发送 Offer
     *
     * POST /rc/applications/{id}/send-offer
     */
    public function sendOffer(ApplicationSendOfferRequest $request, int $id): JsonResponse
    {
        return $this->handleRecruiterFlowAction(
            $request,
            $id,
            static fn (RcApplicationService $service, User $user, Application $application): Application => $service->sendOffer(
                $user,
                $application,
                $request->validated(),
            ),
        );
    }

    /**
     * 确认录用
     *
     * POST /rc/applications/{id}/hire
     */
    public function hire(ApplicationHireRequest $request, int $id): JsonResponse
    {
        return $this->handleRecruiterFlowAction(
            $request,
            $id,
            static fn (RcApplicationService $service, User $user, Application $application): Application => $service->hire(
                $user,
                $application,
                $request->validated('note'),
            ),
        );
    }

    /**
     * 淘汰
     *
     * POST /rc/applications/{id}/reject
     */
    public function reject(ApplicationRejectRequest $request, int $id): JsonResponse
    {
        return $this->handleRecruiterFlowAction(
            $request,
            $id,
            static fn (RcApplicationService $service, User $user, Application $application): Application => $service->reject(
                $user,
                $application,
                $request->validated('note'),
            ),
        );
    }

    /**
     * 根据职位和求职者/简历查询投递记录
     *
     * GET /rc/applications/check
     */
    public function checkByJobAndUser(ApplicationCheckRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $job = Job::query()->find((int) $validated['job_id']);

        if (! $job instanceof Job) {
            return $this->success(null);
        }

        $query = Application::query()
            ->where('job_id', $job->id)
            ->with(['job', 'resume', 'company']);

        if (filled($validated['candidate_user_id'] ?? null)) {
            $query->where('candidate_user_id', (int) $validated['candidate_user_id']);
        }

        if (filled($validated['resume_id'] ?? null)) {
            $query->where('resume_id', (int) $validated['resume_id']);
        }

        if (($company = $this->resolveCurrentRecruiterCompany()) instanceof Company) {
            if ((int) $job->company_id !== (int) $company->id) {
                return $this->success(null);
            }

            $application = $query
                ->where('company_id', $company->id)
                ->latest('id')
                ->first();

            return $this->success($application instanceof Application
                ? (new RcApplicationResource($application))->resolve($request)
                : null);
        }

        if ($this->isCurrentJobSeeker()) {
            if (
                filled($validated['candidate_user_id'] ?? null)
                && (int) $validated['candidate_user_id'] !== (int) $this->user()->id
            ) {
                return $this->success(null);
            }

            $application = $query
                ->where('candidate_user_id', $this->user()->id)
                ->latest('id')
                ->first();

            return $this->success($application instanceof Application
                ? (new RcApplicationResource($application))->resolve($request)
                : null);
        }

        return $this->error('请先切换为求职者或招聘方身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param  callable(RcApplicationService, User, Application): Application  $action
     */
    private function handleJobSeekerFlowAction(Request $request, int $id, callable $action): JsonResponse
    {
        if (! $this->isCurrentJobSeeker()) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $service = RcApplicationService::make();
        $application = $service->findForCandidate($this->user(), $id);

        if (! $application instanceof Application) {
            return $this->error('投递记录不存在。', Response::HTTP_NOT_FOUND);
        }

        try {
            $application = $action($service, $this->user(), $application);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new RcApplicationResource($application))->resolve($request));
    }

    /**
     * @param  callable(RcApplicationService, User, Application): Application  $action
     */
    private function handleRecruiterFlowAction(Request $request, int $id, callable $action): JsonResponse
    {
        $company = $this->resolveCurrentRecruiterCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $service = RcApplicationService::make();
        $application = $service->findForCompany($company, $id);

        if (! $application instanceof Application) {
            return $this->error('投递记录不存在。', Response::HTTP_NOT_FOUND);
        }

        try {
            $application = $action($service, $this->user(), $application);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new RcApplicationResource($application))->resolve($request));
    }

    private function isCurrentJobSeeker(): bool
    {
        $identity = $this->currentIdentity();

        return $identity?->identity_type === RcIdentityType::JobSeeker;
    }

    private function resolveCurrentRecruiterCompany(): ?Company
    {
        $identity = $this->currentIdentity();

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
