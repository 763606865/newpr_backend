<?php

namespace App\Rc\Controllers;

use App\Models\Company;
use App\Models\Rc\Interview;
use App\Resources\Rc\RcApplicationResource;
use App\Resources\Rc\RcInterviewInvitationResource;
use Illuminate\Http\Request;

class CompanyInterviewController extends Controller
{
    public function index(Request $request)
    {
        $company = $this->resolveCurrentRecruiterCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', 422);
        }

        $perPage = $this->getPerPage($request);

        $query = Interview::query()
            ->where('company_id', $company->id)
            ->with(['application', 'application.job', 'application.resume', 'interviewer']);

        if (filled($request->input('status'))) {
            $query->where('status', (int) $request->input('status'));
        }

        if (filled($request->input('job_id'))) {
            $query->whereHas('application', static fn ($q) => $q->where('job_id', (int) $request->input('job_id')));
        }

        if ($request->filled('interview_at_from')) {
            $query->where('interview_at', '>=', $request->input('interview_at_from'));
        }

        if ($request->filled('interview_at_to')) {
            $query->where('interview_at', '<=', $request->input('interview_at_to'));
        }

        $paginator = $query->orderByDesc('interview_at')->orderByDesc('id')->paginate($perPage);

        $paginator->getCollection()->transform(static function (Interview $interview) use ($request) {
            $data = (new RcInterviewInvitationResource($interview))->resolve($request);
            $data['application'] = $interview->application ? (new RcApplicationResource($interview->application))->resolve($request) : null;

            return $data;
        });

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
