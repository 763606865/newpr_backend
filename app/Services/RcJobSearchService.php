<?php

namespace App\Services;

use App\Discovery\Search\JobSearchFilterApplier;
use App\Models\Rc\Job;
use App\Support\ScoutQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Laravel\Scout\Builder;

class RcJobSearchService extends Service
{
    public function __construct(
        private readonly JobSearchFilterApplier $filterApplier = new JobSearchFilterApplier,
    ) {}

    /**
     * 求职者搜索已发布职位。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Job>
     */
    public function search(
        int $perPage,
        array $filters = [],
        string $sortColumn = 'published_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        $keyword = filled($filters['keyword'] ?? null)
            ? ScoutQuery::escape((string) $filters['keyword'])
            : '';

        $builder = $this->makeSearchBuilder($keyword, $filters);

        return $builder
            ->orderBy($sortColumn, $sortDirection)
            ->query(fn ($query) => $query->with(['position', 'company']))
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function makeSearchBuilder(string $keyword, array $filters): Builder
    {
        if (config('scout.driver') === 'collection') {
            return Job::search($keyword, function ($query) use ($filters): void {
                $this->filterApplier->applyDatabaseConstraints($query, $filters);
            });
        }

        $builder = Job::search($keyword);

        $this->filterApplier->applyIndexedFilters($builder, $filters);

        return $builder;
    }
}
