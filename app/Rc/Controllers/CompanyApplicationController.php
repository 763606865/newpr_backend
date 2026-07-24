<?php

namespace App\Rc\Controllers;

use App\Enums\RcIdentityType;
use App\Exceptions\UnauthenticatedException;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\Job;
use App\Models\User;
use App\Rc\Requests\ApplicationCheckRequest;
use App\Rc\Requests\ApplicationHireRequest;
use App\Rc\Requests\ApplicationInviteInterviewRequest;
use App\Rc\Requests\ApplicationRejectRequest;
use App\Rc\Requests\ApplicationSendOfferRequest;
use App\Resources\Rc\RcApplicationResource;
use App\Services\RcApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class CompanyApplicationController extends Controller
{
    /**
     * 招聘后台-投递记录
     *
     * GET /rc/companies/applications
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCurrentRecruiterCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $perPage = $this->getPerPage($request);
        $filters = $request->only(['job_id', 'status', 'candidate_user_id']);

        $paginator = RcApplicationService::make()->paginateForCompany($company, $perPage, $filters);

        $paginator->getCollection()->transform(
            static fn (Application $application) => (new RcApplicationResource($application))->resolve($request)
        );

        return $this->success($paginator);
    }

    /**
     * 招聘后台-投递记录详情
     *
     * GET /rc/companies/applications/{id}
     *
     * @throws UnauthenticatedException
     */
    public function show(Request $request, int $id): JsonResponse
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

        $application = $service->markScreeningOnRecruiterView($this->user(), $application);

        return $this->success((new RcApplicationResource($application))->resolve($request));
    }

    /**
     * 招聘后台-根据职位和求职者/简历查询投递记录
     *
     * GET /rc/companies/applications/check
     *
     * @throws \Exception
     */
    public function checkByJobAndUser(ApplicationCheckRequest $request): JsonResponse
    {
        $company = $this->resolveCurrentRecruiterCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validated();
        $job = Job::query()->find((int) $validated['job_id']);

        if (! $job instanceof Job || (int) $job->company_id !== (int) $company->id) {
            return $this->success(null);
        }

        $query = Application::query()
            ->where('company_id', $company->id)
            ->where('job_id', $job->id)
            ->with([
                'job',
                'company',
                'candidateUser.jobseekerIdentity',
                'latestFlow',
            ]);

        if (filled($validated['candidate_user_id'] ?? null)) {
            $query->where('candidate_user_id', (int) $validated['candidate_user_id']);
        }

        if (filled($validated['resume_id'] ?? null)) {
            $query->where('resume_id', (int) $validated['resume_id']);
        }

        $application = $query
            ->latest('id')
            ->first();

        return $this->success($application instanceof Application
            ? (new RcApplicationResource($application))->resolve($request)
            : null);
    }

    /**
     * 招聘后台-邀请面试
     *
     * POST /rc/companies/applications/{id}/invite-interview
     *
     * @throws UnauthenticatedException
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
     * 招聘后台-发送 Offer
     *
     * POST /rc/companies/applications/{id}/send-offer
     *
     * @throws UnauthenticatedException
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
     * 招聘后台-确认录用
     *
     * POST /rc/companies/applications/{id}/hire
     *
     * @throws UnauthenticatedException
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
     * 招聘后台-淘汰
     *
     * POST /rc/companies/applications/{id}/reject
     *
     * @throws UnauthenticatedException
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
     * @param  callable(RcApplicationService, User, Application): Application  $action
     *
     * @throws UnauthenticatedException
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

    private function resolveCurrentRecruiterCompany(): ?Company
    {
        $identity = $this->currentIdentity();

        if (! $identity || $identity->identity_type->value !== RcIdentityType::Recruiter->value) {
            return null;
        }

        if ($identity->organization_type !== 'company' || ! $identity->organization_id) {
            return null;
        }

        $company = Company::query()->find($identity->organization_id);

        return $company instanceof Company ? $company : null;
    }
}
