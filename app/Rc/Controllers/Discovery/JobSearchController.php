<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Rc\Job;
use App\Models\Rc\UserIdentity;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcJobResource;
use App\Services\RcApplicationService;
use App\Services\RcIdentityOrganizationService;
use App\Services\RcJobFavoriteService;
use App\Services\RcJobSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class JobSearchController extends Controller
{
    /**
     * 求职者搜索职位
     *
     * GET /rc/talent/jobs
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->user();

        $paginator = RcJobSearchService::make()->search(
            $this->getPerPage($request),
            $request->only([
                'keyword',
                'city_code',
                'employment_type',
                'education_level',
                'position_code',
                'company_id',
                'experience_min',
                'experience_max',
                'salary_min',
                'salary_max',
            ]),
        );

        $jobIds = $paginator->getCollection()
            ->pluck('id')
            ->map(fn (mixed $jobId): int => (int) $jobId)
            ->all();

        $appliedJobIds = RcApplicationService::make()->getAppliedJobIdsForUser($user, $jobIds);
        $favoritedJobIds = RcJobFavoriteService::make()->getFavoritedJobIdsForUser($user, $jobIds);

        $paginator->getCollection()->transform(
            function (Job $job) use ($request, $appliedJobIds, $favoritedJobIds): array {
                $data = (new RcJobResource($job))->resolve($request);
                $data['is_applied'] = isset($appliedJobIds[$job->id]);
                $data['is_favorited'] = isset($favoritedJobIds[$job->id]);

                return $data;
            },
        );

        return $this->success($paginator);
    }

    private function resolveJobSeekerIdentity(): ?UserIdentity
    {
        return RcIdentityOrganizationService::make()->resolveJobSeekerIdentity($this->user());
    }
}
