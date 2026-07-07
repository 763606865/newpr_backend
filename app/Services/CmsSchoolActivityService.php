<?php

namespace App\Services;

use App\Discovery\Search\SchoolActivitySearchFilterApplier;
use App\Models\Rc\SchoolActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CmsSchoolActivityService extends Service
{
    public function __construct(
        private readonly SchoolActivitySearchFilterApplier $filterApplier = new SchoolActivitySearchFilterApplier,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SchoolActivity>
     */
    public function paginate(int $perPage, array $filters = []): LengthAwarePaginator
    {
        if (filled($filters['keyword'] ?? null)) {
            return RcSchoolActivitySearchService::make()->searchPublic($perPage, $filters);
        }

        $query = SchoolActivity::query()
            ->published()
            ->with([
                'organizer',
                'companyApplications' => fn ($query) => $query
                    ->approved()
                    ->with(['company.profile']),
            ])
            ->withCount([
                'companyApplications as company_applications_count',
                'jobs as jobs_count',
                'activityBooths as activity_booths_count',
            ])
            ->orderByDesc('is_hot')
            ->orderByDesc('sort')
            ->orderByDesc('start_time')
            ->orderByDesc('id');

        $this->filterApplier->applyPublicFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function findPublished(int $activityId, ?string $regionCode = null): ?SchoolActivity
    {
        $query = SchoolActivity::query()
            ->published()
            ->with([
                'organizer',
                'schools.profile',
                'companyApplications' => fn ($query) => $query
                    ->approved()
                    ->with(['company.profile']),
            ])
            ->withCount([
                'companyApplications as company_applications_count',
                'jobs as jobs_count',
                'activityBooths as activity_booths_count',
            ])
            ->whereKey($activityId);

        if ($regionCode !== null) {
            $query->forRegion($regionCode);
        }

        return $query->first();
    }
}
