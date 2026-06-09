<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RcPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('rc_positions')) {
            $this->command?->warn('rc_positions 表不存在，已跳过 RcPositionSeeder。');

            return;
        }

        $positions = $this->normalizePositionTree($this->positionTree());

        if ($positions === []) {
            $this->command?->warn('rc_positions 数据为空，已跳过 RcPositionSeeder。');

            return;
        }

        // 使用 delete 避免某些环境下 truncate 受外键限制报错。
        DB::table('rc_positions')->delete();

        $now = now();

        foreach ($positions as $index => $position) {
            $this->insertPosition($position, null, $index + 1, 1, $now);
        }
    }

    /**
     * 将外部字段（id/value/label/children）映射为本地字段（name/code/children）。
     *
     * @param  array<int, array<string, mixed>>  $sourceTree
     * @return array<int, array{name:string, code:string, children:array<int, array<string, mixed>>}>
     */
    private function normalizePositionTree(array $sourceTree): array
    {
        $normalized = [];

        foreach ($sourceTree as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = isset($item['label']) ? trim((string) $item['label']) : '';
            $code = isset($item['value']) ? trim((string) $item['value']) : '';

            if ($name === '' || $code === '') {
                continue;
            }

            $children = $item['children'] ?? null;

            $normalized[] = [
                'name' => $name,
                'code' => $code,
                // 明确忽略外部 id/parentid，层级关系使用本表自增 id 建立。
                'children' => is_array($children) ? $this->normalizePositionTree($children) : [],
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function positionTree(): array
    {
        $dataFilePath = database_path('seeders/data/rc_positions.json');

        if (! is_file($dataFilePath)) {
            $this->command?->warn('未找到 rc_positions 数据文件，已跳过 RcPositionSeeder。');

            return [];
        }

        $contents = file_get_contents($dataFilePath);

        if ($contents === false) {
            $this->command?->warn('读取 rc_positions 数据文件失败，已跳过 RcPositionSeeder。');

            return [];
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            $this->command?->warn('rc_positions 数据文件不是合法数组，已跳过 RcPositionSeeder。');

            return [];
        }

        return $decoded;
    }

    /**
     * @param  array{name:string, code:string, children:array<int, array<string, mixed>>}  $position
     */
    private function insertPosition(array $position, ?int $parentId, int $sort, int $depth, mixed $now): void
    {
        $dbId = DB::table('rc_positions')->insertGetId([
            'name' => $position['name'],
            'code' => $position['code'],
            'parent_id' => $parentId,
            'sort' => $sort,
            'depth' => $depth,
            // 仅使用本表自增 ID 建立层级关系，不依赖外部来源 ID。
            'extra' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($position['children'] as $childSort => $child) {
            $this->insertPosition($child, $dbId, $childSort + 1, $depth + 1, $now);
        }
    }
}
