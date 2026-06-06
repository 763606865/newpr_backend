<?php

namespace App\Rc\Controllers\Discovery;

use App\Discovery\Recommendation\JobRecommendationContext;
use App\Models\Rc\Job;
use App\Models\User;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcJobResource;
use App\Services\RcJobRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobRecommendController extends Controller
{
    /**
     * 求职者职位推荐（支持未登录访问）
     *
     * GET /rc/talent/jobs/recommend
     */
    public function index(Request $request): JsonResponse
    {
        $result = RcJobRecommendationService::make()->recommend(
            new JobRecommendationContext(
                user: $this->optionalUser(),
                cityHint: $request->input('city_code') ?? $request->header('X-City-Code'),
            ),
            $this->getPerPage($request),
        );

        $paginator = $result['paginator'];
        $paginator->getCollection()->transform(
            static fn (Job $job): array => (new RcJobResource($job))->resolve($request),
        );

        $payload = $paginator->toArray();
        $payload['recommendation'] = $result['criteria']->toRecommendationMeta();

        return $this->success($payload);
    }

    private function optionalUser(): ?User
    {
        $user = auth()->guard('rc')->user();

        return $user instanceof User ? $user : null;
    }
}
