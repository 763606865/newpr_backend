<?php

namespace App\Services;

use App\Discovery\Search\JobSearchFilterApplier;
use App\Discovery\Search\JobSearchSortCriteria;
use App\Models\Rc\Job;
use App\Support\ScoutQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Builder as ScoutBuilder;

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
    public function search(int $perPage, array $filters = [], ?JobSearchSortCriteria $sortCriteria = null): LengthAwarePaginator
    {
        $sortCriteria ??= JobSearchSortCriteria::default();

        if ($this->shouldSearchViaDatabase($filters)) {
            return $this->searchViaDatabase($perPage, $filters, $sortCriteria);
        }

        return $this->searchViaElasticsearch($perPage, $filters, $sortCriteria);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function shouldSearchViaDatabase(array $filters): bool
    {
        if (config('scout.driver') !== 'elastic') {
            return false;
        }

        return blank($filters['keyword'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Job>
     */
    private function searchViaDatabase(int $perPage, array $filters, JobSearchSortCriteria $sortCriteria): LengthAwarePaginator
    {
        $query = Job::query();
        $this->filterApplier->applyDatabaseConstraints($query, $filters);
        $sortCriteria->applyToQuery($query);

        return $query
            ->with(Job::discoveryRelations())
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Job>
     */
    private function searchViaElasticsearch(int $perPage, array $filters, JobSearchSortCriteria $sortCriteria): LengthAwarePaginator
    {
        $keyword = ScoutQuery::escape((string) ($filters['keyword'] ?? ''));

        $builder = $this->makeSearchBuilder($keyword, $filters);
        $sortCriteria->applyToScoutBuilder($builder);

        $paginator = $builder
            ->query(function (Builder $query) use ($filters): void {
                $query->with(Job::discoveryRelations());
                $this->filterApplier->applyDatabaseFilters($query, $filters);
            })
            ->paginate($perPage);

        $paginator->setCollection(
            $sortCriteria->sortJobCollection($paginator->getCollection()),
        );

        return $paginator;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function makeSearchBuilder(string $keyword, array $filters): ScoutBuilder
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
