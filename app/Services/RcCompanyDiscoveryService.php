<?php

namespace App\Services;

use App\Discovery\Search\JobSearchFilterApplier;
use App\Models\Company;
use App\Models\Rc\Job;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RcCompanyDiscoveryService extends Service
{
    public function findDiscoverableCompany(int $companyId): ?Company
    {
        $company = Company::query()
            ->enabled()
            ->with([
                'profile',
                'albums' => fn ($query) => $query->enabled()->ordered(),
            ])
            ->find($companyId);

        if (! $company instanceof Company) {
            return null;
        }

        $company->setAttribute('public_job_count', $this->countPublicJobs($company));

        return $company;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Job>
     */
    public function paginatePublicJobs(Company $company, int $perPage, array $filters = []): LengthAwarePaginator
    {
        return $this->publicJobsQuery($company, $filters)
            ->with(['position', 'creator.recruiterCompanyIdentities'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function countPublicJobs(Company $company): int
    {
        return $this->publicJobsQuery($company)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Job>
     */
    private function publicJobsQuery(Company $company, array $filters = []): Builder
    {
        $query = Job::query()->where('company_id', $company->id);

        (new JobSearchFilterApplier)->applyDatabaseConstraints($query, $filters);

        return $query;
    }
}
