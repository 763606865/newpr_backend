<?php

namespace App\Rc\Controllers\Discovery;

use App\Discovery\Recommendation\ResumeRecommendationContext;
use App\Models\Company;
use App\Models\Rc\Resume;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcResumePreviewResource;
use App\Services\RcJobService;
use App\Services\RcResumeRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ResumeRecommendController extends Controller
{
    /**
     * 招聘方推荐候选人简历
     *
     * GET /rc/talent/resumes/recommend
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $jobId = $request->filled('job_id') ? (int) $request->input('job_id') : null;

        $result = RcResumeRecommendationService::make()->recommend(
            new ResumeRecommendationContext(
                user: $this->user(),
                company: $company,
                jobIdHint: $jobId,
            ),
            $this->getPerPage($request),
        );

        $paginator = $result['paginator'];
        $paginator->getCollection()->transform(
            static fn (Resume $resume): array => (new RcResumePreviewResource($resume))->resolve($request),
        );

        $payload = $paginator->toArray();
        $payload['recommendation'] = $result['criteria']->toRecommendationMeta();

        return $this->success($payload);
    }

    private function resolveCompany(): ?Company
    {
        return RcJobService::make()->resolveRecruiterCompany($this->user());
    }
}
