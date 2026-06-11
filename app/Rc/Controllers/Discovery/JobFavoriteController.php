<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Rc\JobFavorite;
use App\Models\Rc\UserIdentity;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcJobResource;
use App\Services\RcApplicationService;
use App\Services\RcIdentityOrganizationService;
use App\Services\RcJobFavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class JobFavoriteController extends Controller
{
    /**
     * 我收藏的职位列表
     *
     * GET /rc/talent/favorites/jobs
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->user();
        $paginator = RcJobFavoriteService::make()->paginateForUser($user, $this->getPerPage($request));

        $jobIds = $paginator->getCollection()
            ->pluck('job_id')
            ->map(fn (mixed $jobId): int => (int) $jobId)
            ->all();

        $appliedJobIds = RcApplicationService::make()->getAppliedJobIdsForUser($user, $jobIds);

        $paginator->getCollection()->transform(
            function (JobFavorite $favorite) use ($request, $appliedJobIds): array {
                $data = (new RcJobResource($favorite->job))->resolve($request);
                $data['is_favorited'] = true;
                $data['is_applied'] = isset($appliedJobIds[$favorite->job_id]);
                $data['favorited_at'] = $favorite->created_at;

                return $data;
            },
        );

        return $this->success($paginator);
    }

    /**
     * 收藏职位
     *
     * POST /rc/talent/jobs/{id}/favorite
     */
    public function store(int $id): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $job = RcJobFavoriteService::make()->resolvePublicJobOrFail($id);
            RcJobFavoriteService::make()->favorite($this->user(), $job);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'is_favorited' => true,
        ]);
    }

    /**
     * 取消收藏职位
     *
     * DELETE /rc/talent/jobs/{id}/favorite
     */
    public function destroy(int $id): JsonResponse
    {
        if (! $this->resolveJobSeekerIdentity() instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $job = RcJobFavoriteService::make()->resolvePublicJobOrFail($id);
            RcJobFavoriteService::make()->unfavorite($this->user(), $job);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'is_favorited' => false,
        ]);
    }

    private function resolveJobSeekerIdentity(): ?UserIdentity
    {
        return RcIdentityOrganizationService::make()->resolveJobSeekerIdentity($this->user());
    }
}
