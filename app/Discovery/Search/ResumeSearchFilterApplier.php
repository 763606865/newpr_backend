<?php

namespace App\Discovery\Search;

use App\Enums\RcResumeStatus;
use App\Models\Rc\Resume;
use App\Models\Rc\UserCompanyBlacklist;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Builder as ScoutBuilder;

class ResumeSearchFilterApplier
{
    /**
     * @param  Builder<Resume>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyDatabaseConstraints(Builder $query, array $filters = []): void
    {
        $query->where('status', RcResumeStatus::Normal->value);

        $this->applyDatabaseFilters($query, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyIndexedFilters(ScoutBuilder $builder, array $filters): void
    {
        $builder->where('status', RcResumeStatus::Normal->value);

        if (! empty($filters['resume_ids'])) {
            $builder->whereIn('id', array_map('intval', $filters['resume_ids']));
        }

        if (filled($filters['highest_education_level'] ?? null)) {
            $builder->where('highest_education_level', (int) $filters['highest_education_level']);
        }

        if (filled($filters['current_city_code'] ?? null)) {
            $builder->where(
                'current_city_code_prefix',
                $this->cityCodePrefix((string) $filters['current_city_code']),
            );
        }

        if (filled($filters['is_fresh_graduate'] ?? null)) {
            $builder->where('is_fresh_graduate', (int) $filters['is_fresh_graduate']);
        }

        if (filled($filters['work_years_min'] ?? null)) {
            $builder->where('work_years', '>=', (int) $filters['work_years_min']);
        }

        if (filled($filters['work_years_max'] ?? null)) {
            $builder->where('work_years', '<=', (int) $filters['work_years_max']);
        }
    }

    /**
     * @param  Builder<Resume>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyDatabaseFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['resume_ids'])) {
            $query->whereIn('id', array_map('intval', $filters['resume_ids']));
        }

        if (filled($filters['highest_education_level'] ?? null)) {
            $query->where('highest_education_level', (int) $filters['highest_education_level']);
        }

        if (filled($filters['current_city_code'] ?? null)) {
            $query->where(
                'current_city_code',
                'like',
                $this->cityCodePrefix((string) $filters['current_city_code']).'%',
            );
        }

        if (filled($filters['is_fresh_graduate'] ?? null)) {
            $query->where('is_fresh_graduate', (int) $filters['is_fresh_graduate']);
        }

        if (filled($filters['work_years_min'] ?? null)) {
            $query->where('work_years', '>=', (int) $filters['work_years_min']);
        }

        if (filled($filters['work_years_max'] ?? null)) {
            $query->where('work_years', '<=', (int) $filters['work_years_max']);
        }

        $this->applyExclusionFilters($query, $filters);
    }

    /**
     * @param  Builder<Resume>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyExclusionFilters(Builder $query, array $filters): void
    {
        if (filled($filters['exclude_blacklisted_users_for_company_id'] ?? null)) {
            $companyId = (int) $filters['exclude_blacklisted_users_for_company_id'];

            $query->whereNotIn('user_id', UserCompanyBlacklist::query()
                ->select('user_id')
                ->where('company_id', $companyId));
        }
    }

    private function cityCodePrefix(string $cityCode): string
    {
        $cityCode = trim($cityCode);

        if (strlen($cityCode) >= 4) {
            return substr($cityCode, 0, 4);
        }

        return $cityCode;
    }
}
