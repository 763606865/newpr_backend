<?php

namespace App\Console\Commands\Rc;

use App\Discovery\Search\AnnouncementSearchIndex;
use App\Models\Rc\Announcement;
use Elastic\Adapter\Indices\IndexManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('rc:announcements:sync-search-index
    {--import : 同步 mapping 后重新导入全部招聘公告索引}')]
#[Description('同步 rc_announcements Elasticsearch 索引 mapping')]
class SyncAnnouncementSearchIndexCommand extends Command
{
    public function handle(IndexManager $indexManager): int
    {
        try {
            AnnouncementSearchIndex::syncMapping($indexManager);
        } catch (Throwable $exception) {
            $this->error('同步 rc_announcements 索引 mapping 失败：'.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('已更新索引 mapping：'.AnnouncementSearchIndex::indexName());
        $this->line('新增/更新字段：'.implode(', ', array_keys(AnnouncementSearchIndex::mappingProperties())));

        if ($this->option('import')) {
            $this->info('开始重新导入招聘公告索引…');
            $exitCode = $this->call('scout:import', [
                'model' => Announcement::class,
            ]);

            if ($exitCode !== self::SUCCESS) {
                return $exitCode;
            }

            $this->info('招聘公告索引导入完成。');
        } else {
            $this->comment('如需回填城市/专业等字段，请追加 --import 参数。');
        }

        return self::SUCCESS;
    }
}
