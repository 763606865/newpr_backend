<?php

namespace App\Services;

use App\Models\Rc\Job;
use App\Models\Rc\JobStatsDaily;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeStatsDaily;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

class RcViewStatsArchiveService extends Service
{
    /**
     * @return list<int>
     */
    public function discoverEntityIds(string $type, string $statDate): array
    {
        if (! in_array($type, ['job', 'resume'], true)) {
            return [];
        }

        $connection = $this->redis();
        $redisPrefix = $this->redisKeyPrefix($connection);
        $matchPattern = $redisPrefix.$this->pvScanPattern($type, $statDate);
        $entityIds = [];
        $cursor = null;

        do {
            $result = $connection->scan($cursor, [
                'match' => $matchPattern,
                'count' => 1000,
            ]);

            if ($result === false) {
                break;
            }

            [$cursor, $keys] = $result;

            foreach ($keys as $key) {
                $entityId = $this->parseEntityIdFromPvKey(
                    $this->stripRedisPrefix((string) $key, $redisPrefix),
                    $type,
                    $statDate,
                );

                if ($entityId !== null) {
                    $entityIds[$entityId] = $entityId;
                }
            }
        } while ($cursor !== 0);

        return array_values($entityIds);
    }

    /**
     * @param  list<int>  $jobIds
     */
    public function syncJobBatch(array $jobIds, string $statDate): int
    {
        if ($jobIds === []) {
            return 0;
        }

        $viewStats = RcViewStatsService::make();
        $rows = [];

        $jobs = Job::query()
            ->whereIn('id', $jobIds)
            ->get(['id', 'company_id', 'creator_user_id']);

        foreach ($jobs as $job) {
            $views = $viewStats->getJobDailyViews((int) $job->id, $statDate);

            if ($views['views_total'] === 0 && $views['views_uv'] === 0) {
                continue;
            }

            $rows[] = [
                'company_id' => (int) $job->company_id,
                'user_id' => $job->creator_user_id,
                'job_id' => (int) $job->id,
                'stat_date' => $statDate,
                'views_total' => $views['views_total'],
                'views_uv' => $views['views_uv'],
            ];
        }

        if ($rows === []) {
            return 0;
        }

        foreach ($rows as $row) {
            $updated = JobStatsDaily::query()
                ->where('job_id', $row['job_id'])
                ->whereDate('stat_date', $row['stat_date'])
                ->update([
                    'company_id' => $row['company_id'],
                    'user_id' => $row['user_id'],
                    'views_total' => $row['views_total'],
                    'views_uv' => $row['views_uv'],
                ]);

            if ($updated === 0) {
                JobStatsDaily::query()->create($row);
            }
        }

        return count($rows);
    }

    /**
     * @param  list<int>  $resumeIds
     */
    public function syncResumeBatch(array $resumeIds, string $statDate): int
    {
        if ($resumeIds === []) {
            return 0;
        }

        $viewStats = RcViewStatsService::make();
        $rows = [];

        $resumes = Resume::query()
            ->whereIn('id', $resumeIds)
            ->get(['id', 'user_id']);

        foreach ($resumes as $resume) {
            $views = $viewStats->getResumeDailyViews((int) $resume->id, $statDate);

            if ($views['views_total'] === 0 && $views['views_uv'] === 0) {
                continue;
            }

            $rows[] = [
                'user_id' => (int) $resume->user_id,
                'resume_id' => (int) $resume->id,
                'stat_date' => $statDate,
                'views_total' => $views['views_total'],
                'views_uv' => $views['views_uv'],
            ];
        }

        if ($rows === []) {
            return 0;
        }

        foreach ($rows as $row) {
            $updated = ResumeStatsDaily::query()
                ->where('resume_id', $row['resume_id'])
                ->whereDate('stat_date', $row['stat_date'])
                ->update([
                    'user_id' => $row['user_id'],
                    'views_total' => $row['views_total'],
                    'views_uv' => $row['views_uv'],
                ]);

            if ($updated === 0) {
                ResumeStatsDaily::query()->create($row);
            }
        }

        return count($rows);
    }

    private function redis(): Connection
    {
        /** @var Connection $connection */
        $connection = Redis::connection((string) config('rc_stats.redis_connection'));

        return $connection;
    }

    private function redisKeyPrefix(Connection $connection): string
    {
        $connectionName = (string) config('rc_stats.redis_connection', 'default');

        $configuredPrefix = config("database.redis.{$connectionName}.prefix")
            ?? config('database.redis.options.prefix')
            ?? '';

        if (is_string($configuredPrefix) && $configuredPrefix !== '') {
            return $configuredPrefix;
        }

        $client = $connection->client();

        if ($client instanceof \Redis) {
            $optionPrefix = $client->getOption(\Redis::OPT_PREFIX);

            return is_string($optionPrefix) ? $optionPrefix : '';
        }

        return '';
    }

    private function pvScanPattern(string $type, string $statDate): string
    {
        return sprintf('%s:%s:*:pv:%s', config('rc_stats.key_prefix'), $type, $statDate);
    }

    private function stripRedisPrefix(string $key, string $prefix): string
    {
        if ($prefix !== '' && str_starts_with($key, $prefix)) {
            return substr($key, strlen($prefix));
        }

        return $key;
    }

    private function parseEntityIdFromPvKey(string $key, string $type, string $statDate): ?int
    {
        $keyPrefix = preg_quote((string) config('rc_stats.key_prefix'), '#');
        $typePattern = preg_quote($type, '#');
        $datePattern = preg_quote($statDate, '#');
        $pattern = "#^{$keyPrefix}:{$typePattern}:(\d+):pv:{$datePattern}$#";

        if (! preg_match($pattern, $key, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
