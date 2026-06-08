<?php

namespace App\B\Controllers;

use App\B\Requests\CompanyRequest;
use App\Models\Company;
use App\Services\BUserService;
use App\Services\CompanyOperationLogService;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * 企业列表
     *
     * GET /b/companies
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->user();

        return $this->success($user->companies()->get());
    }

    /**
     * 企业入驻
     *
     * POST /b/companies
     *
     * @throws \Exception
     */
    public function store(CompanyRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $this->user();
        $company = CompanyService::make()->create($validated);

        if ($company->wasRecentlyCreated) {
            CompanyOperationLogService::make()->recordCreatedFromRequest($company, $request);
        }

        BUserService::make()->attachCompany($user, $company);

        return $this->success($company);
    }

    /**
     * 企业信息
     *
     * GET /b/companies/{id}
     *
     * @throws \Exception
     */
    public function show(Request $request, string $id): JsonResponse
    {
        /** @var Company $company */
        $company = Company::findOrFail($id);

        return $this->success($company);
    }

    /**
     * 企业信息
     *
     * GET /b/companies/{id}/edit
     *
     * @throws \Exception
     */
    public function edit(Request $request, string $id): JsonResponse
    {
        return $this->show($request, $id);
    }

    /**
     * 编辑企业
     *
     * PUT /b/companies/{id}
     *
     * @throws \Exception
     */
    public function update(CompanyRequest $request, string $id): JsonResponse
    {
        /** @var Company $company */
        $company = Company::findOrFail($id);
        $logService = CompanyOperationLogService::make();
        $before = $logService->snapshotCompanyAttributes($company);
        $validated = $request->validated();
        $company->fill($validated)->save();
        $logService->recordCompanyAttributesChanged($company, $before, $request);

        return $this->success();
    }
}
