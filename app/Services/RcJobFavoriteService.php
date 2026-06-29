<?php

namespace App\Services;

use App\Models\Rc\Job;
use App\Models\Rc\JobFavorite;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class RcJobFavoriteService extends Service
{
    public function favorite(User $user, Job $job): void
    {
        JobFavorite::query()->firstOrCreate([
            'user_id' => $user->id,
            'job_id' => $job->id,
        ]);
    }

    public function unfavorite(User $user, Job $job): void
    {
        JobFavorite::query()
            ->where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->delete();
    }

    public function isFavorited(User $user, int $jobId): bool
    {
        return isset($this->getFavoritedJobIdsForUser($user, [$jobId])[$jobId]);
    }

    /**
     * 批量查询用户已收藏的职位 ID，返回 job_id => true 映射便于 O(1) 判断。
     *
     * @param  list<int>  $jobIds
     * @return array<int, true>
     */
    public function getFavoritedJobIdsForUser(User $user, array $jobIds): array
    {
        if ($jobIds === []) {
            return [];
        }

        $favoritedJobIds = JobFavorite::query()
            ->where('user_id', $user->id)
            ->whereIn('job_id', $jobIds)
            ->pluck('job_id')
            ->map(fn (mixed $jobId): int => (int) $jobId)
            ->all();

        return array_fill_keys($favoritedJobIds, true);
    }

    /**
     * @return LengthAwarePaginator<int, JobFavorite>
     */
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return JobFavorite::query()
            ->where('user_id', $user->id)
            ->whereHas('job')
            ->with(Job::discoveryRelationsWithPrefix('job'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function resolvePublicJobOrFail(int $jobId): Job
    {
        $job = RcJobDiscoveryService::make()->findPublicJob($jobId);

        if (! $job instanceof Job) {
            throw new InvalidArgumentException('职位不存在或已下架。');
        }

        return $job;
    }
}
