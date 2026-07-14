<?php

namespace App\Rc\Controllers;

use App\Models\Company;
use App\Models\Rc\Application;
use App\Resources\Rc\RcApplicationResource;
use App\Services\RcApplicationService;
use Illuminate\Http\Request;

class CompanyApplicationController extends Controller
{
    public function index(Request $request)
    {
        $company = $this->resolveCurrentRecruiterCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', 422);
        }

        $perPage = $this->getPerPage($request);
        $filters = $request->only(['job_id', 'status', 'candidate_user_id']);

        $paginator = RcApplicationService::make()->paginateForCompany($company, $perPage, $filters);

        $paginator->getCollection()->transform(
            static fn (Application $application) => (new RcApplicationResource($application))->resolve($request)
        );

        return $this->success($paginator);
    }

    private function resolveCurrentRecruiterCompany(): ?Company
    {
        $identity = $this->currentIdentity();

        if (! $identity || $identity->identity_type->value !== \App\Enums\RcIdentityType::Recruiter->value) {
            return null;
        }

        if ($identity->organization_type !== 'company' || ! $identity->organization_id) {
            return null;
        }

        $company = Company::query()->find($identity->organization_id);

        return $company instanceof Company ? $company : null;
    }
}
