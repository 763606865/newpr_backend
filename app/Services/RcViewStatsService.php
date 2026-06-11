<?php

namespace App\Services;

use App\Models\Rc\Job;
use App\Models\Rc\JobStatsDaily;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeStatsDaily;
use App\Models\User;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Throwable;

class RcViewStatsService extends Service
{
    /**
     * 记录职位详情浏览（PV + UV）。
     */
    public function recordJobView(Job $job, ?User $viewer = null): void
    {
        $this->recordView(
            type: 'job',
            entityId: (int) $job->id,
            viewer: $viewer,
        );
    }

    /**
     * 记录简历详情浏览（PV + UV）。
     */
    public function recordResumeView(Resume $resume, ?User $viewer = null): void
    {
        $this->recordView(
            type: 'resume',
            entityId: (int) $resume->id,
            viewer: $viewer,
        );
    }

    /**
     * 读取某日职位浏览量（供看板或归档任务使用）。
     *
     * @return array{views_total: int, views_uv: int}
     */
    public function getJobDailyViews(int $jobId, ?string $statDate = null): array
    {
        return $this->getDailyViews('job', $jobId, $statDate);
    }

    /**
     * 读取某日简历浏览量（供看板或归档任务使用）。
     *
     * @return array{views_total: int, views_uv: int}
     */
    public function getResumeDailyViews(int $resumeId, ?string $statDate = null): array
    {
        return $this->getDailyViews('resume', $resumeId, $statDate);
    }

    /**
     * 批量读取简历累计浏览量（历史归档 + 当日 Redis）。
     *
     * @param  list<int>  $resumeIds
     * @return array<int, int>
     */
    public function getResumeTotalViewsForIds(array $resumeIds): array
    {
        return $this->getTotalViewsForIds('resume', $resumeIds);
    }

    /**
     * 批量读取职位累计浏览量（历史归档 + 当日 Redis）。
     *
     * @param  list<int>  $jobIds
     * @return array<int, int>
     */
    public function getJobTotalViewsForIds(array $jobIds): array
    {
        return $this->getTotalViewsForIds('job', $jobIds);
    }

    /**
     * @return array{views_total: int, views_uv: int}
     */
    private function getDailyViews(string $type, int $entityId, ?string $statDate = null): array
    {
        $date = $statDate ?? $this->resolveStatDate();
        $connection = $this->redis();

        $pv = (int) $connection->get($this->pvKey($type, $entityId, $date));
        $uv = (int) $connection->pfcount($this->uvKey($type, $entityId, $date));

        return [
            'views_total' => $pv,
            'views_uv' => $uv,
        ];
    }

    /**
     * @param  list<int>  $entityIds
     * @return array<int, int>
     */
    private function getTotalViewsForIds(string $type, array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $entityIds = array_values(array_unique($entityIds));
        $today = $this->resolveStatDate();
        $foreignKey = $type === 'job' ? 'job_id' : 'resume_id';
        $statsModel = $type === 'job' ? JobStatsDaily::class : ResumeStatsDaily::class;

        /** @var array<int, int|string> $archived */
        $archived = $statsModel::query()
            ->whereIn($foreignKey, $entityIds)
            ->where('stat_date', '<', $today)
            ->groupBy($foreignKey)
            ->selectRaw("{$foreignKey}, SUM(views_total) as total_views")
            ->pluck('total_views', $foreignKey)
            ->all();

        $keys = array_map(
            fn (int $entityId): string => $this->pvKey($type, $entityId, $today),
            $entityIds,
        );

        /** @var list<int|string|null> $todayValues */
        $todayValues = $this->redis()->mget($keys);

        $totals = [];

        foreach ($entityIds as $index => $entityId) {
            $totals[$entityId] = (int) ($archived[$entityId] ?? 0) + (int) ($todayValues[$index] ?? 0);
        }

        return $totals;
    }

    private function recordView(string $type, int $entityId, ?User $viewer): void
    {
        try {
            $date = $this->resolveStatDate();
            $ttl = $this->keyTtlSeconds($date);
            $viewerKey = $this->resolveViewerKey($viewer);
            $pvKey = $this->pvKey($type, $entityId, $date);
            $uvKey = $this->uvKey($type, $entityId, $date);

            $this->redis()->pipeline(function ($pipe) use ($pvKey, $uvKey, $viewerKey, $ttl): void {
                $pipe->incr($pvKey);
                $pipe->expire($pvKey, $ttl);

                if ($viewerKey !== null) {
                    $pipe->pfadd($uvKey, [$viewerKey]);
                    $pipe->expire($uvKey, $ttl);
                }
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function redis(): Connection
    {
        /** @var Connection $connection */
        $connection = Redis::connection((string) config('rc_stats.redis_connection'));

        return $connection;
    }

    private function resolveStatDate(): string
    {
        return Carbon::now()->toDateString();
    }

    private function keyTtlSeconds(string $statDate): int
    {
        $expiresAt = Carbon::parse($statDate)
            ->endOfDay()
            ->addDays((int) config('rc_stats.key_ttl_days', 8));

        return max(1, (int) now()->diffInSeconds($expiresAt, false));
    }

    private function resolveViewerKey(?User $viewer): ?string
    {
        if (! $viewer instanceof User) {
            return null;
        }

        return 'user:'.$viewer->getKey();
    }

    private function pvKey(string $type, int $entityId, string $date): string
    {
        return sprintf('%s:%s:%d:pv:%s', config('rc_stats.key_prefix'), $type, $entityId, $date);
    }

    private function uvKey(string $type, int $entityId, string $date): string
    {
        return sprintf('%s:%s:%d:uv:%s', config('rc_stats.key_prefix'), $type, $entityId, $date);
    }
}
