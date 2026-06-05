<?php

namespace App\Discovery\Search;

use App\Enums\RcJobStatus;
use App\Models\Rc\Job;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Builder as ScoutBuilder;

class JobSearchFilterApplier
{
    /**
     * @param  Builder<Job>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyDatabaseConstraints(Builder $query, array $filters = []): void
    {
        $query
            ->where('status', RcJobStatus::Published->value)
            ->where(function ($subQuery): void {
                $subQuery
                    ->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            });

        $this->applyDatabaseFilters($query, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyIndexedFilters(ScoutBuilder $builder, array $filters): void
    {
        $builder->where('is_public', 1);

        if (filled($filters['city_codes'] ?? null)) {
            $builder->whereIn('city_code', array_values((array) $filters['city_codes']));
        } elseif (filled($filters['city_code'] ?? null)) {
            $builder->where('city_code', (string) $filters['city_code']);
        }

        if (filled($filters['employment_type'] ?? null)) {
            $builder->where('employment_type', (int) $filters['employment_type']);
        }

        if (filled($filters['education_level'] ?? null)) {
            $builder->where('education_level', (int) $filters['education_level']);
        }

        if (filled($filters['position_code'] ?? null)) {
            $builder->where('position_code', (string) $filters['position_code']);
        }

        if (filled($filters['company_id'] ?? null)) {
            $builder->where('company_id', (int) $filters['company_id']);
        }

        if (filled($filters['experience_min'] ?? null)) {
            $builder->where('experience_min', '>=', (int) $filters['experience_min']);
        }

        if (filled($filters['experience_max'] ?? null)) {
            $builder->where('experience_max', '<=', (int) $filters['experience_max']);
        }

        if (filled($filters['salary_min'] ?? null)) {
            $builder->where('salary_max', '>=', (float) $filters['salary_min']);
        }

        if (filled($filters['salary_max'] ?? null)) {
            $builder->where('salary_min', '<=', (float) $filters['salary_max']);
        }
    }

    /**
     * @param  Builder<Job>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyDatabaseFilters(Builder $query, array $filters): void
    {
        if (filled($filters['city_codes'] ?? null)) {
            $query->whereIn('city_code', array_values((array) $filters['city_codes']));
        } elseif (filled($filters['city_code'] ?? null)) {
            $query->where('city_code', (string) $filters['city_code']);
        }

        if (filled($filters['employment_type'] ?? null)) {
            $query->where('employment_type', (int) $filters['employment_type']);
        }

        if (filled($filters['education_level'] ?? null)) {
            $query->where('education_level', (int) $filters['education_level']);
        }

        if (filled($filters['position_code'] ?? null)) {
            $query->where('position_code', (string) $filters['position_code']);
        }

        if (filled($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (filled($filters['experience_min'] ?? null)) {
            $query->where('experience_min', '>=', (int) $filters['experience_min']);
        }

        if (filled($filters['experience_max'] ?? null)) {
            $query->where('experience_max', '<=', (int) $filters['experience_max']);
        }

        if (filled($filters['salary_min'] ?? null)) {
            $query->where('salary_max', '>=', (float) $filters['salary_min']);
        }

        if (filled($filters['salary_max'] ?? null)) {
            $query->where('salary_min', '<=', (float) $filters['salary_max']);
        }
    }
}
