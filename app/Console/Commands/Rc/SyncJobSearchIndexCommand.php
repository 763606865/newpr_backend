<?php

namespace App\Console\Commands\Rc;

use App\Discovery\Search\JobSearchIndex;
use App\Models\Rc\Job;
use Elastic\Adapter\Indices\IndexManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('rc:jobs:sync-search-index
    {--import : 同步 mapping 后重新导入全部职位索引}')]
#[Description('同步 rc_jobs Elasticsearch 索引 mapping（含 is_urgent 排序字段）')]
class SyncJobSearchIndexCommand extends Command
{
    public function handle(IndexManager $indexManager): int
    {
        try {
            JobSearchIndex::syncMapping($indexManager);
        } catch (Throwable $exception) {
            $this->error('同步 rc_jobs 索引 mapping 失败：'.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('已更新索引 mapping：'.JobSearchIndex::indexName());
        $this->line('新增/更新字段：'.implode(', ', array_keys(JobSearchIndex::mappingProperties())));

        if ($this->option('import')) {
            $this->info('开始重新导入职位索引…');
            $exitCode = $this->call('scout:import', [
                'model' => Job::class,
            ]);

            if ($exitCode !== self::SUCCESS) {
                return $exitCode;
            }

            $this->info('职位索引导入完成。');
        } else {
            $this->comment('如需回填 is_urgent 等字段，请追加 --import 参数。');
        }

        return self::SUCCESS;
    }
}
