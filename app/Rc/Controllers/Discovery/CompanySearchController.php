<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Company;
use App\Models\Rc\UserIdentity;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcCompanyDiscoveryResource;
use App\Services\RcCompanySearchService;
use App\Services\RcIdentityOrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompanySearchController extends Controller
{
    /**
     * 求职者搜索企业
     *
     * GET /rc/talent/companies
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcCompanySearchService::make()->search(
            $this->getPerPage($request),
            array_merge($request->only([
                'keyword',
                'city_code',
                'industry_code',
                'scale_type',
                'nature_type',
            ]), [
                'exclude_blacklisted_company_for_user_id' => $this->user()->id,
            ]),
        );

        $paginator->getCollection()->transform(
            static fn (Company $company): array => (new RcCompanyDiscoveryResource($company))->resolve($request),
        );

        return $this->success($paginator);
    }

    private function resolveJobSeekerIdentity(): ?UserIdentity
    {
        return RcIdentityOrganizationService::make()->resolveJobSeekerIdentity($this->user());
    }
}
