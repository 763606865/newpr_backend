<?php

namespace App\Discovery\Search;

use App\Models\Rc\Job;
use Elastic\Adapter\Indices\IndexManager;

final class JobSearchIndex
{
    /**
     * @return list<string>
     */
    public static function scoutSortableFields(): array
    {
        return [
            'is_urgent',
            'published_at',
        ];
    }

    public static function indexName(): string
    {
        $prefix = (string) config('scout.prefix', '');

        return $prefix.(new Job)->searchableAs();
    }

    /**
     * 追加到现有索引的 mapping 字段。
     *
     * @return array<string, array<string, mixed>>
     */
    public static function mappingProperties(): array
    {
        return [
            'is_urgent' => ['type' => 'byte'],
        ];
    }

    public static function syncMapping(IndexManager $indexManager): void
    {
        $indexManager->putMappingRaw(self::indexName(), [
            'properties' => self::mappingProperties(),
        ]);
    }
}
