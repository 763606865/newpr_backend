<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Rc\Job;
use App\Models\User;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcCompanyDiscoveryResource;
use App\Resources\Rc\RcJobResource;
use App\Services\RcApplicationService;
use App\Services\RcCompanyDiscoveryService;
use App\Services\RcCompanyFavoriteService;
use App\Services\RcJobFavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompanyDetailController extends Controller
{
    /**
     * 求职者查看企业信息页（支持未登录访问）
     *
     * GET /rc/talent/companies/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $company = RcCompanyDiscoveryService::make()->findDiscoverableCompany($id);

        if ($company === null) {
            return $this->error('企业不存在或不可查看。', Response::HTTP_NOT_FOUND);
        }

        $jobs = RcCompanyDiscoveryService::make()->paginatePublicJobs(
            $company,
            $this->getPerPage($request),
        );

        $viewer = auth()->guard('rc')->user();
        $jobIds = $jobs->getCollection()
            ->pluck('id')
            ->map(fn (mixed $jobId): int => (int) $jobId)
            ->all();

        $appliedJobIds = [];
        $favoritedJobIds = [];

        if ($viewer instanceof User) {
            $applicationService = RcApplicationService::make();
            $jobFavoriteService = RcJobFavoriteService::make();
            $appliedJobIds = $applicationService->getAppliedJobIdsForUser($viewer, $jobIds);
            $favoritedJobIds = $jobFavoriteService->getFavoritedJobIdsForUser($viewer, $jobIds);
        }

        $jobs->getCollection()->transform(
            function (Job $job) use ($request, $viewer, $appliedJobIds, $favoritedJobIds): array {
                $data = (new RcJobResource($job))->resolve($request);

                if ($viewer instanceof User) {
                    $data['is_applied'] = isset($appliedJobIds[$job->id]);
                    $data['is_favorited'] = isset($favoritedJobIds[$job->id]);
                }

                return $data;
            },
        );

        $data = [
            'company' => (new RcCompanyDiscoveryResource($company))->resolve($request),
            'jobs' => $jobs,
        ];

        if ($viewer instanceof User) {
            $data['is_favorited'] = RcCompanyFavoriteService::make()->isFavorited($viewer, $company->id);
        }

        return $this->success($data);
    }
}
