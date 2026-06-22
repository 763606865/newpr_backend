<?php

namespace App\Services;

use App\Discovery\Search\SchoolActivitySearchFilterApplier;
use App\Models\Rc\SchoolActivity;
use App\Support\ScoutQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Laravel\Scout\Builder;

class RcSchoolActivitySearchService extends Service
{
    public function __construct(
        private readonly SchoolActivitySearchFilterApplier $filterApplier = new SchoolActivitySearchFilterApplier,
    ) {}

    /**
     * 招聘方可报名活动搜索。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SchoolActivity>
     */
    public function searchAvailable(int $perPage, array $filters = []): LengthAwarePaginator
    {
        $filters['context'] = 'available';

        return $this->search($perPage, $filters);
    }

    /**
     * 校招负责人主办活动搜索。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SchoolActivity>
     */
    public function searchForSchoolOrganizer(int $schoolId, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $filters['context'] = 'school_organizer';
        $filters['organizer_id'] = $schoolId;

        return $this->search($perPage, $filters);
    }

    /**
     * 门户公开活动搜索。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SchoolActivity>
     */
    public function searchPublic(int $perPage, array $filters = []): LengthAwarePaginator
    {
        $filters['context'] = 'public';

        return $this->search($perPage, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SchoolActivity>
     */
    public function search(int $perPage, array $filters = []): LengthAwarePaginator
    {
        $keyword = filled($filters['keyword'] ?? null)
            ? ScoutQuery::escape((string) $filters['keyword'])
            : '';

        $builder = $this->makeSearchBuilder($keyword, $filters);

        return $builder
            ->orderBy('sort', 'desc')
            ->orderBy('start_time', 'desc')
            ->orderBy('updated_at', 'desc')
            ->query(function ($query) use ($filters): void {
                if (($filters['context'] ?? null) === 'school_organizer') {
                    $query->withCount(['companyApplications', 'jobs', 'activityBooths']);
                }

                if (($filters['context'] ?? null) === 'public') {
                    $query->with(['organizer']);
                    $this->filterApplier->applyPublicFilters($query, $filters);

                    return;
                }

                $this->filterApplier->applyDatabaseFilters($query, $filters);
            })
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function makeSearchBuilder(string $keyword, array $filters): Builder
    {
        if (config('scout.driver') === 'collection') {
            return SchoolActivity::search($keyword, function ($query) use ($filters): void {
                $this->filterApplier->applyDatabaseConstraints($query, $filters);
            });
        }

        $builder = SchoolActivity::search($keyword);

        $this->filterApplier->applyIndexedFilters($builder, $filters);

        return $builder;
    }
}
