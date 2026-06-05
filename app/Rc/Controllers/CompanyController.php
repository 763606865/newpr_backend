<?php

namespace App\Rc\Controllers;

use App\Models\Company;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Rc\Requests\CompanyBindRequest;
use App\Rc\Requests\CompanyLookupRequest;
use App\Rc\Requests\CompanyStoreRequest;
use App\Resources\Rc\RcCompanyResource;
use App\Resources\Rc\RcUserIdentityResource;
use App\Services\RcCompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompanyController extends Controller
{
    /**
     * 按统一社会信用代码查询企业
     *
     * GET /rc/companies/lookup
     */
    public function lookup(CompanyLookupRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = RcCompanyService::make()->lookup((string) $validated['credit_code']);

        return $this->success([
            'exists' => $result['exists'],
            'company' => $result['company'] instanceof Company
                ? (new RcCompanyResource($result['company']))->resolve($request)
                : null,
        ]);
    }

    /**
     * 绑定已存在企业
     *
     * POST /rc/companies/bind
     */
    public function bind(CompanyBindRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();
        $service = RcCompanyService::make();
        $validated = $request->validated();

        if (! $service->resolveRecruiterIdentity($user) instanceof UserIdentity) {
            return $this->error('请先切换为招聘方身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $company = $service->findById((int) $validated['company_id']);

        if (! $company instanceof Company) {
            return $this->error('企业不存在。', Response::HTTP_NOT_FOUND);
        }

        $bindableMessage = $service->companyBindableMessage($company);

        if ($bindableMessage !== null) {
            return $this->error($bindableMessage, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $alreadyBoundMessage = $service->userAlreadyBoundCompanyMessage($user, $company);

        if ($alreadyBoundMessage !== null) {
            return $this->error($alreadyBoundMessage, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $identity = $service->bind(
            $service->prepareRecruiterIdentityForCompanyBind($user),
            $company,
            (string) $validated['job_title'],
        );

        return $this->success($this->bindPayload($request, $company, $identity));
    }

    /**
     * 注册企业并绑定招聘方身份
     *
     * POST /rc/companies
     */
    public function store(CompanyStoreRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();
        $service = RcCompanyService::make();
        $validated = $request->validated();

        if (! $service->resolveRecruiterIdentity($user) instanceof UserIdentity) {
            return $this->error('请先切换为招聘方身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $existingCompany = $service->findByCreditCode((string) $validated['credit_code']);

        if ($existingCompany instanceof Company) {
            $alreadyBoundMessage = $service->userAlreadyBoundCompanyMessage($user, $existingCompany);

            if ($alreadyBoundMessage !== null) {
                return $this->error($alreadyBoundMessage, Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return $this->error('企业已存在，请直接绑定。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $identity = $service->prepareRecruiterIdentityForCompanyBind($user);

        $companyData = [
            'name' => (string) $validated['name'],
            'credit_code' => (string) $validated['credit_code'],
            'legal_person' => (string) $validated['legal_person'],
            'contact_phone' => (string) $validated['contact_phone'],
            'address' => $validated['address'] ?? null,
        ];

        $result = $service->registerAndBind(
            $identity,
            $companyData,
            (string) $validated['job_title'],
            filled($validated['licenses_file_path'] ?? null) ? (string) $validated['licenses_file_path'] : null,
        );

        return $this->success($this->bindPayload($request, $result['company'], $result['identity']));
    }

    /**
     * @return array{company: array<string, mixed>, identity: array<string, mixed>}
     */
    private function bindPayload(Request $request, Company $company, UserIdentity $identity): array
    {
        $company->load([
            'licenses' => fn ($query) => $query->orderBy('sort')->orderBy('id'),
            'contacts' => fn ($query) => $query->orderBy('sort')->orderBy('id'),
        ]);

        return [
            'company' => (new RcCompanyResource($company))->resolve($request),
            'identity' => (new RcUserIdentityResource($identity))->resolve($request),
        ];
    }
}
