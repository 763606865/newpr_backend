<?php

namespace App\Discovery\Search;

use App\Models\Rc\Announcement;
use Elastic\Adapter\Indices\IndexManager;

final class AnnouncementSearchIndex
{
    /**
     * @return list<string>
     */
    public static function scoutSortableFields(): array
    {
        return [
            'is_top',
            'sort',
            'published_at',
        ];
    }

    public static function indexName(): string
    {
        $prefix = (string) config('scout.prefix', '');

        return $prefix.(new Announcement)->searchableAs();
    }

    /**
     * 追加到现有索引的 mapping 字段（避免覆盖 Scout 自动推断的类型）。
     *
     * @return array<string, array<string, mixed>>
     */
    public static function mappingProperties(): array
    {
        return [
            'city_codes' => ['type' => 'keyword'],
            'major_codes' => ['type' => 'keyword'],
            'city_names' => ['type' => 'text'],
            'major_names' => ['type' => 'text'],
        ];
    }

    public static function syncMapping(IndexManager $indexManager): void
    {
        $indexManager->putMappingRaw(self::indexName(), [
            'properties' => self::mappingProperties(),
        ]);
    }
}
