<?php

namespace App\Services;

use App\Discovery\Search\ResumeSearchFilterApplier;
use App\Models\Rc\Resume;
use App\Support\ScoutQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Laravel\Scout\Builder;

class RcResumeSearchService extends Service
{
    public function __construct(
        private readonly ResumeSearchFilterApplier $filterApplier = new ResumeSearchFilterApplier,
    ) {}

    /**
     * 招聘方搜索候选人简历。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Resume>
     */
    public function search(
        int $perPage,
        array $filters = [],
        string $sortColumn = 'updated_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        $keyword = filled($filters['keyword'] ?? null)
            ? ScoutQuery::escape((string) $filters['keyword'])
            : '';

        $builder = $this->makeSearchBuilder($keyword, $filters);

        return $builder
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function makeSearchBuilder(string $keyword, array $filters): Builder
    {
        if (config('scout.driver') === 'collection') {
            return Resume::search($keyword, function ($query) use ($filters): void {
                $this->filterApplier->applyDatabaseConstraints($query, $filters);
            });
        }

        $builder = Resume::search($keyword);

        $this->filterApplier->applyIndexedFilters($builder, $filters);

        return $builder;
    }
}
