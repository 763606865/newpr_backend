<?php

namespace App\SApi\Controllers;

use App\Models\Company;
use App\Resources\SApi\SApiCompanyResource;
use App\SApi\Requests\CompanyIndexRequest;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    /**
     * 拉取企业列表
     *
     * GET /sapi/companies
     */
    public function index(CompanyIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Company::query()
            ->when(filled($validated['parent_id'] ?? null), function ($query) use ($validated): void {
                $query->where('parent_id', (int) $validated['parent_id']);
            })
            ->when(isset($validated['status']), function ($query) use ($validated): void {
                $query->where('status', (int) $validated['status']);
            })
            ->tap(fn ($query) => $this->applyCreatedBetween($query, $validated))
            ->tap(fn ($query) => $this->applyUpdatedBetween($query, $validated))
            ->orderByDesc('id');

        return $this->indexDataResponse(
            $request,
            $query,
            fn (Company $company) => (new SApiCompanyResource($company))->resolve($request),
        );
    }
}
