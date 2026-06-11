<?php

namespace App\Console\Commands\Rc;

use App\Jobs\Rc\SyncViewStatsBatchJob;
use App\Services\RcViewStatsArchiveService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('rc:sync-view-stats
    {date? : 统计日期，格式 Y-m-d，默认昨日}
    {--type=all : 同步类型：all、job、resume}
    {--batch= : 每批处理的实体数量，默认读取配置}')]
#[Description('将 Redis 中的浏览量日统计同步至 MySQL')]
class SyncViewStatsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RcViewStatsArchiveService $archive): int
    {
        try {
            $statDate = $this->argument('date')
                ? Carbon::parse((string) $this->argument('date'))->toDateString()
                : Carbon::yesterday()->toDateString();
        } catch (\Throwable) {
            $this->error('date 参数无效，请使用 Y-m-d 格式。');

            return self::FAILURE;
        }

        $type = (string) $this->option('type');

        if (! in_array($type, ['all', 'job', 'resume'], true)) {
            $this->error('type 参数无效，可选值：all、job、resume。');

            return self::FAILURE;
        }

        $batchSize = $this->option('batch') !== null && $this->option('batch') !== ''
            ? max(1, (int) $this->option('batch'))
            : max(1, (int) config('rc_stats.sync_batch_size', 100));

        $types = $type === 'all' ? ['job', 'resume'] : [$type];
        $dispatchedJobs = 0;
        $dispatchedEntities = 0;

        foreach ($types as $entityType) {
            $entityIds = $archive->discoverEntityIds($entityType, $statDate);

            if ($entityIds === []) {
                $this->line(sprintf('[%s] 未发现 %s 浏览量 Key。', $statDate, $entityType));

                continue;
            }

            foreach (array_chunk($entityIds, $batchSize) as $chunk) {
                SyncViewStatsBatchJob::dispatch($entityType, $statDate, $chunk);
                $dispatchedJobs++;
                $dispatchedEntities += count($chunk);
            }

            $this->info(sprintf(
                '[%s] 已派发 %s 同步任务：%d 个实体，%d 个批次（每批 %d）。',
                $statDate,
                $entityType,
                count($entityIds),
                (int) ceil(count($entityIds) / $batchSize),
                $batchSize,
            ));
        }

        if ($dispatchedJobs === 0) {
            $this->warn(sprintf('[%s] 无需同步的浏览量数据。', $statDate));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '[%s] 共同步派发 %d 个队列任务，覆盖 %d 个实体。',
            $statDate,
            $dispatchedJobs,
            $dispatchedEntities,
        ));

        return self::SUCCESS;
    }
}
