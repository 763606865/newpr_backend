<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RcIndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('rc_industries')) {
            $this->command?->warn('rc_industries 表不存在，已跳过 RcIndustrySeeder。');

            return;
        }

        $industries = $this->normalizeIndustryTree($this->industryTree());

        if ($industries === []) {
            $this->command?->warn('rc_industries 数据为空，已跳过 RcIndustrySeeder。');

            return;
        }

        // 使用 delete 避免某些环境下 truncate 受外键限制报错。
        DB::table('rc_industries')->delete();

        $now = now();

        foreach ($industries as $index => $industry) {
            $this->insertIndustry($industry, null, $index + 1, 1, $now);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $sourceTree
     * @return array<int, array{name:string, code:string, children:array<int, array<string, mixed>>}>
     */
    private function normalizeIndustryTree(array $sourceTree): array
    {
        $normalized = [];

        foreach ($sourceTree as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = isset($item['name']) ? trim((string) $item['name']) : '';
            $code = isset($item['code']) ? trim((string) $item['code']) : '';

            if ($name === '' || $code === '') {
                continue;
            }

            $children = $item['children'] ?? null;

            $normalized[] = [
                'name' => $name,
                'code' => $code,
                'children' => is_array($children) ? $this->normalizeIndustryTree($children) : [],
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function industryTree(): array
    {
        $dataFilePath = database_path('seeders/data/rc_industries.json');

        if (! is_file($dataFilePath)) {
            $this->command?->warn('未找到 rc_industries 数据文件，已跳过 RcIndustrySeeder。');

            return [];
        }

        $contents = file_get_contents($dataFilePath);

        if ($contents === false) {
            $this->command?->warn('读取 rc_industries 数据文件失败，已跳过 RcIndustrySeeder。');

            return [];
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            $this->command?->warn('rc_industries 数据文件不是合法数组，已跳过 RcIndustrySeeder。');

            return [];
        }

        return $decoded;
    }

    /**
     * @param  array{name:string, code:string, children:array<int, array<string, mixed>>}  $industry
     */
    private function insertIndustry(array $industry, ?int $parentId, int $sort, int $depth, mixed $now): void
    {
        $dbId = DB::table('rc_industries')->insertGetId([
            'name' => $industry['name'],
            'code' => $industry['code'],
            'parent_id' => $parentId,
            'sort' => $sort,
            'depth' => $depth,
            'extra' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($industry['children'] as $childSort => $child) {
            $this->insertIndustry($child, $dbId, $childSort + 1, $depth + 1, $now);
        }
    }
}
