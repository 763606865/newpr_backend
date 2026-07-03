<?php

namespace App\Services;

use App\Discovery\Search\JobSearchFilterApplier;
use App\Models\Company;
use App\Models\Rc\UserCompanyBlacklist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RcCompanySearchService extends Service
{
    /**
     * 搜索企业列表。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Company>
     */
    public function search(int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseDiscoverableQuery($filters);

        if (filled($filters['keyword'] ?? null)) {
            $keyword = trim((string) $filters['keyword']);

            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhereHas('profile', function (Builder $query) use ($keyword): void {
                        $query->where('short_name', 'like', "%{$keyword}%")
                            ->orWhere('introduction', 'like', "%{$keyword}%");
                    });
            });
        }

        $this->applyProfileFilters($query, $filters);

        return $query
            ->orderByDesc('public_job_count')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * 推荐企业列表。
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Company>
     */
    public function recommend(int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseDiscoverableQuery($filters)
            ->whereHas('jobs', function (Builder $query): void {
                (new JobSearchFilterApplier)->applyDatabaseConstraints($query);
            });

        $this->applyProfileFilters($query, $filters);

        return $query
            ->orderByDesc('profile_is_brand')
            ->orderByDesc('profile_brand_sort')
            ->orderByDesc('public_job_count')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Company>
     */
    private function baseDiscoverableQuery(array $filters = []): Builder
    {
        $query = Company::query()
            ->enabled()
            ->with([
                'profile',
                'albums' => fn ($query) => $query->enabled()->ordered(),
            ])
            ->withMax('profile as profile_is_brand', 'is_brand')
            ->withMax('profile as profile_brand_sort', 'brand_sort')
            ->withCount([
                'jobs as public_job_count' => function (Builder $query): void {
                    (new JobSearchFilterApplier)->applyDatabaseConstraints($query);
                },
            ]);

        if (filled($filters['exclude_blacklisted_company_for_user_id'] ?? null)) {
            $userId = (int) $filters['exclude_blacklisted_company_for_user_id'];

            $query->whereNotIn('companies.id', UserCompanyBlacklist::query()
                ->select('company_id')
                ->where('user_id', $userId));
        }

        return $query;
    }

    /**
     * @param  Builder<Company>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyProfileFilters(Builder $query, array $filters): void
    {
        if (filled($filters['city_code'] ?? null)) {
            $query->whereHas('profile', fn (Builder $query): mixed => $query->where('city_code', (string) $filters['city_code']));
        }

        if (filled($filters['industry_code'] ?? null)) {
            $query->whereHas('profile', fn (Builder $query): mixed => $query->whereJsonContains('industry_codes', (string) $filters['industry_code']));
        }

        if (filled($filters['scale_type'] ?? null)) {
            $query->whereHas('profile', fn (Builder $query): mixed => $query->where('scale_type', (int) $filters['scale_type']));
        }

        if (filled($filters['nature_type'] ?? null)) {
            $query->whereHas('profile', fn (Builder $query): mixed => $query->where('nature_type', (int) $filters['nature_type']));
        }
    }
}
