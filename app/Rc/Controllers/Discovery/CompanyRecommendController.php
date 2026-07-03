<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Company;
use App\Models\User;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcCompanyDiscoveryResource;
use App\Services\RcCompanySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyRecommendController extends Controller
{
    /**
     * 企业推荐（支持未登录访问）
     *
     * GET /rc/talent/companies/recommend
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'city_code' => $request->input('city_code') ?? $request->header('X-City-Code'),
        ];

        $user = $this->optionalUser();

        if ($user instanceof User) {
            $filters['exclude_blacklisted_company_for_user_id'] = $user->id;
        }

        $paginator = RcCompanySearchService::make()->recommend(
            $this->getPerPage($request),
            $filters,
        );

        $paginator->getCollection()->transform(
            static fn (Company $company): array => (new RcCompanyDiscoveryResource($company))->resolve($request),
        );

        $payload = $paginator->toArray();
        $payload['recommendation'] = [
            'strategy' => filled($filters['city_code'] ?? null) ? 'city_public_jobs' : 'public_jobs',
            'city_code' => $filters['city_code'] ?? null,
        ];

        return $this->success($payload);
    }

    private function optionalUser(): ?User
    {
        $user = auth()->guard('rc')->user();

        return $user instanceof User ? $user : null;
    }
}
