<?php

namespace Database\Seeders;

use App\Enums\MajorEducationType;
use App\Enums\MajorStatus;
use App\Models\Major;
use App\Services\MetaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('majors')) {
            $this->command?->warn('majors 表不存在，已跳过 MajorSeeder。');

            return;
        }

        $records = $this->loadUndergraduateMajors();

        if ($records === []) {
            $this->command?->warn('本科专业数据为空，已跳过 MajorSeeder。');

            return;
        }

        $now = now();
        $type = MajorEducationType::Undergraduate->value;

        Major::withoutEvents(function () use ($records, $now, $type): void {
            Major::query()->where('type', $type)->delete();

            foreach (array_chunk($records, 200) as $chunk) {
                Major::query()->insert(array_map(
                    static fn (array $record): array => [
                        'full_code' => $record['full_code'],
                        'name' => $record['name'],
                        'level' => $record['level'],
                        'parent_code' => $record['parent_code'],
                        'type' => $type,
                        'tag' => $record['tag'] ?? '',
                        'sort' => $record['sort'],
                        'status' => MajorStatus::Enabled->value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $chunk,
                ));
            }
        });

        MetaService::make()->forgetMajors();

        $this->command?->info(sprintf('已导入 %d 条本科专业数据。', count($records)));
    }

    /**
     * @return array<int, array{full_code:string, name:string, level:int, parent_code:?string, tag:string, sort:int}>
     */
    private function loadUndergraduateMajors(): array
    {
        $dataFilePath = database_path('seeders/data/undergraduate_majors_2026.json');

        if (! is_file($dataFilePath)) {
            $this->command?->warn('未找到 undergraduate_majors_2026.json 数据文件，已跳过 MajorSeeder。');

            return [];
        }

        $contents = file_get_contents($dataFilePath);

        if ($contents === false) {
            $this->command?->warn('读取 undergraduate_majors_2026.json 失败，已跳过 MajorSeeder。');

            return [];
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            $this->command?->warn('undergraduate_majors_2026.json 不是合法数组，已跳过 MajorSeeder。');

            return [];
        }

        $normalized = [];

        foreach ($decoded as $record) {
            if (! is_array($record)) {
                continue;
            }

            $fullCode = isset($record['full_code']) ? trim((string) $record['full_code']) : '';
            $name = isset($record['name']) ? trim((string) $record['name']) : '';
            $level = isset($record['level']) ? (int) $record['level'] : 0;

            if ($fullCode === '' || $name === '' || ! in_array($level, [1, 2, 3], true)) {
                continue;
            }

            $parentCode = $record['parent_code'] ?? null;
            $parentCode = is_string($parentCode) ? trim($parentCode) : null;
            $parentCode = $parentCode === '' ? null : $parentCode;

            $normalized[] = [
                'full_code' => $fullCode,
                'name' => $name,
                'level' => $level,
                'parent_code' => $parentCode,
                'tag' => isset($record['tag']) ? trim((string) $record['tag']) : '',
                'sort' => isset($record['sort']) ? (int) $record['sort'] : 0,
            ];
        }

        return $normalized;
    }
}
