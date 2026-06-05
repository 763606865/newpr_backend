<?php

namespace App\Discovery\Search;

use App\Enums\RcResumeStatus;
use App\Models\Rc\Resume;
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

        if (filled($filters['highest_education_level'] ?? null)) {
            $builder->where('highest_education_level', (int) $filters['highest_education_level']);
        }

        if (filled($filters['current_city_code'] ?? null)) {
            $builder->where('current_city_code', (string) $filters['current_city_code']);
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
        if (filled($filters['highest_education_level'] ?? null)) {
            $query->where('highest_education_level', (int) $filters['highest_education_level']);
        }

        if (filled($filters['current_city_code'] ?? null)) {
            $query->where('current_city_code', (string) $filters['current_city_code']);
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
    }
}
