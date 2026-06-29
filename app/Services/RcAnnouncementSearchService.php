<?php

namespace App\Services;

use App\Discovery\Search\AnnouncementSearchFilterApplier;
use App\Discovery\Search\AnnouncementSearchSortCriteria;
use App\Models\Rc\Announcement;
use App\Support\ScoutQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Builder as ScoutBuilder;

class RcAnnouncementSearchService extends Service
{
    public function __construct(
        private readonly AnnouncementSearchFilterApplier $filterApplier = new AnnouncementSearchFilterApplier,
    ) {}

    /**
     * 求职者搜索已发布招聘公告。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Announcement>
     */
    public function search(
        int $perPage,
        array $filters = [],
        ?AnnouncementSearchSortCriteria $sortCriteria = null,
    ): LengthAwarePaginator {
        $sortCriteria ??= AnnouncementSearchSortCriteria::default();

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
     * @return LengthAwarePaginator<int, Announcement>
     */
    private function searchViaDatabase(
        int $perPage,
        array $filters,
        AnnouncementSearchSortCriteria $sortCriteria,
    ): LengthAwarePaginator {
        $query = Announcement::query();
        $this->filterApplier->applyDatabaseConstraints($query, $filters);
        $sortCriteria->applyToQuery($query);

        return $query
            ->with(Announcement::discoveryRelations())
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Announcement>
     */
    private function searchViaElasticsearch(
        int $perPage,
        array $filters,
        AnnouncementSearchSortCriteria $sortCriteria,
    ): LengthAwarePaginator {
        $keyword = ScoutQuery::escape((string) ($filters['keyword'] ?? ''));

        $builder = $this->makeSearchBuilder($keyword, $filters);
        $sortCriteria->applyToScoutBuilder($builder);

        $paginator = $builder
            ->query(function (Builder $query) use ($filters): void {
                $query->with(Announcement::discoveryRelations());
                $this->filterApplier->applyDatabaseFilters($query, $filters);
            })
            ->paginate($perPage);

        $paginator->setCollection(
            $sortCriteria->sortAnnouncementCollection($paginator->getCollection()),
        );

        return $paginator;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function makeSearchBuilder(string $keyword, array $filters): ScoutBuilder
    {
        if (config('scout.driver') === 'collection') {
            return Announcement::search($keyword, function ($query) use ($filters): void {
                $this->filterApplier->applyDatabaseConstraints($query, $filters);
            });
        }

        $builder = Announcement::search($keyword);

        $this->filterApplier->applyIndexedFilters($builder, $filters);

        return $builder;
    }
}
