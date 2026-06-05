<?php

namespace App\Discovery\Recommendation;

final class ResumeRecommendationCriteria
{
    /**
     * @param  array<string, mixed>  $searchFilters
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $strategy,
        public readonly array $searchFilters = [],
        public readonly array $meta = [],
        public readonly string $sortColumn = 'updated_at',
        public readonly string $sortDirection = 'desc',
    ) {}

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
            'sort' => [
                'column' => $this->sortColumn,
                'direction' => $this->sortDirection,
            ],
        ], $this->meta);
    }
}
