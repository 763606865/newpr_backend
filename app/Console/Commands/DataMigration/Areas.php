<?php

namespace App\Console\Commands\DataMigration;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

#[Signature('data:migration:areas')]
#[Description('Command description')]
class Areas extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 首先尝试从 JSON 文件重新初始化
        $jsonPath = base_path('database/seeders/data/areas.json');
        if (file_exists($jsonPath)) {
            $this->info("Found JSON: $jsonPath, reinitializing areas from JSON (backup recommended)...");

            $json = file_get_contents($jsonPath);
            $data = json_decode($json, true);
            if (!is_array($data)) {
                $this->error('Invalid JSON structure in areas.json');
                return 1;
            }

            // 备份提示已在外部执行，这里直接清空并导入
            DB::table('areas')->truncate();

            $columns = Schema::getColumnListing('areas');
            $hasCreatedAt = in_array('created_at', $columns, true);
            $hasUpdatedAt = in_array('updated_at', $columns, true);

            $chunkSize = 500;
            $total = count($data);
            $inserted = 0;

            foreach (array_chunk($data, $chunkSize) as $chunk) {
                $batch = [];
                foreach ($chunk as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    // 只保留 areas 表存在的列
                    $filtered = array_intersect_key($row, array_flip($columns));

                    // 如果没有 created_at/updated_at，填充当前时间
                    $now = now();
                    if ($hasCreatedAt && empty($filtered['created_at'])) {
                        $filtered['created_at'] = $now;
                    }
                    if ($hasUpdatedAt && empty($filtered['updated_at'])) {
                        $filtered['updated_at'] = $now;
                    }

                    $batch[] = $filtered;
                }

                if (!empty($batch)) {
                    DB::table('areas')->insert($batch);
                    $inserted += count($batch);
                }
            }

            $this->info("Imported {$inserted}/{$total} rows into areas from JSON");
            Log::info('Reinitialized areas from JSON', ['path' => $jsonPath, 'imported' => $inserted]);

            return 0;
        }

        // JSON 不存在，则回退到 ex_region 导入逻辑（保留原有行为）
        $this->info('areas.json not found, importing from ex_region...');

        // 请务必提前备份 areas 表
        DB::table('areas')->truncate();

        // 判断 areas 表是否包含 created_at 和 depth 列
        $hasCreatedAt = Schema::hasColumn('areas', 'created_at');
        $hasDepth = Schema::hasColumn('areas', 'depth');

        $columns = ['name', 'code', 'level', 'type', 'parent_code'];
        if ($hasDepth) {
            $columns[] = 'depth';
        }
        if ($hasCreatedAt) {
            $columns[] = 'created_at';
            $columns[] = 'updated_at';
        }

        $columnsList = implode(', ', array_map(function ($c) { return "`$c`"; }, $columns));

        $select = 'SELECT r.name, r.zip, r.level, r.type, p.zip AS parent_code';
        if ($hasDepth) {
            $select .= ', r.area_path AS depth';
        }
        if ($hasCreatedAt) {
            $select .= ', NOW(), NOW()';
        }
        $select .= ' FROM ex_region r LEFT JOIN ex_region p ON r.pid = p.id';

        $sql = "INSERT INTO areas ($columnsList) $select";

        DB::statement($sql);

        $this->info('Imported ex_region into areas');

        return 0;
    }
}
