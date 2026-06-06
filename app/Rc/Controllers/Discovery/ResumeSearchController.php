<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Company;
use App\Models\Rc\Resume;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcResumePreviewResource;
use App\Services\RcJobService;
use App\Services\RcResumeSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ResumeSearchController extends Controller
{
    /**
     * 招人方搜索候选人简历
     *
     * GET /rc/talent/resumes
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcResumeSearchService::make()->search(
            $this->getPerPage($request),
            $request->only([
                'keyword',
                'highest_education_level',
                'current_city_code',
                'is_fresh_graduate',
                'work_years_min',
                'work_years_max',
            ]),
        );

        $paginator->getCollection()->transform(
            static fn (Resume $resume): array => (new RcResumePreviewResource($resume))->resolve($request),
        );

        return $this->success($paginator);
    }

    private function resolveCompany(): ?Company
    {
        return RcJobService::make()->resolveRecruiterCompany($this->user());
    }
}
