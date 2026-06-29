<?php

namespace App\Discovery\Recommendation;

use App\Discovery\Search\AnnouncementSearchSortCriteria;

final class AnnouncementRecommendationCriteria
{
    /**
     * @param  array<string, mixed>  $searchFilters
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $strategy,
        public readonly array $searchFilters = [],
        public readonly array $meta = [],
        public readonly ?AnnouncementSearchSortCriteria $sortCriteria = null,
    ) {}

    public function resolvedSortCriteria(): AnnouncementSearchSortCriteria
    {
        return $this->sortCriteria ?? AnnouncementSearchSortCriteria::default();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchFilters(): array
    {
        return $this->searchFilters;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRecommendationMeta(): array
    {
        return array_merge([
            'strategy' => $this->strategy,
            'applied_filters' => $this->searchFilters,
            'sort' => $this->resolvedSortCriteria()->toMeta(),
        ], $this->meta);
    }
}
