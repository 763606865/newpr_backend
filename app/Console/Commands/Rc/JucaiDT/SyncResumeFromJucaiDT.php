<?php

namespace App\Console\Commands\Rc\JucaiDT;

use App\Jobs\Rc\SyncResumeFromJucaiDTJob;
use App\Libs\Facades\JucaiDT;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

#[Signature('rc:resumes:sync-from:jucai-dt')]
#[Description('从聚才数据中台同步简历过来')]
class SyncResumeFromJucaiDT extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('同步字典项，检测联通性...');
        $this->syncJucaiDTDicts();

        $this->info('开始从聚才数据中台同步简历过来');

        $response = JucaiDT::resume()->list();

        $list = Collection::wrap($response['data']['list'] ?? []);
        $total = $list->count();

        if ($total === 0) {
            $this->info('没有可同步的简历');
            return 0;
        }

        $this->info(sprintf('共找到 %d 条简历，按 100 条/批 次分发到队列', $total));

        $dispatched = 0;
        $list->chunk(100)->each(function (Collection $group) use (&$dispatched) {
            try {
                // 将集合转换为数组并重建索引，确保任务接收可序列化的数据
                $payload = $group->values()->all();
                SyncResumeFromJucaiDTJob::dispatch($payload);
                $dispatched += count($payload);
            } catch (\Throwable $e) {
                // 记录失败但继续处理剩余批次
                logger()->error('Dispatch SyncResumeFromJucaiDTJob failed', ['error' => $e->getMessage()]);
            }
        });

        $this->info(sprintf('已分发 %d/%d 条简历到队列', $dispatched, $total));

        return 0;
    }

    public function syncJucaiDTDicts()
    {
        $dictionaries = [
            'sex',
            'education',
            'degree',
            'marital_status',
            'nation',
            'politics',
            'salary',
            'experience',
            'nature',
        ];
        foreach ($dictionaries as $dict) {
            if (!$this->hasMapCache($dict)) {
                $response = JucaiDT::resume()->dict(['type' => $dict]);
                $this->setMapCache($dict, $response['data']['list']);
                usleep(5000000);
            }
        }
    }

    public function setMapCache(string $type, array $data = [])
    {
        $cacheKey = 'JucaiDT:Dict:'.$type;
        Cache::put($cacheKey, $data, now()->addHours(12));
    }

    public function hasMapCache(string $type): bool
    {
        $cacheKey = 'JucaiDT:Dict:'.$type;
        return Cache::has($cacheKey);
    }
}
