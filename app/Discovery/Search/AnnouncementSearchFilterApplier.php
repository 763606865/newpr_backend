<?php

namespace App\Discovery\Search;

use App\Enums\CmsPublishStatus;
use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Models\Rc\Announcement;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Builder as ScoutBuilder;

class AnnouncementSearchFilterApplier
{
    /**
     * @param  Builder<Announcement>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyDatabaseConstraints(Builder $query, array $filters = []): void
    {
        $query
            ->published()
            ->where(function (Builder $subQuery): void {
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

        if (filled($filters['publisher_type'] ?? null)) {
            $builder->where('publisher_type', (int) $filters['publisher_type']);
        }

        if (filled($filters['publisher_types'] ?? null)) {
            $builder->whereIn(
                'publisher_type',
                array_map(static fn (mixed $value): int => (int) $value, (array) $filters['publisher_types']),
            );
        }

        if (filled($filters['employment_type'] ?? null)) {
            $builder->where('employment_types', (int) $filters['employment_type']);
        }

        if (filled($filters['education_level'] ?? null)) {
            $builder->where('education_level', (int) $filters['education_level']);
        }

        if (filled($filters['graduation_year'] ?? null)) {
            $builder->where('graduation_years', (int) $filters['graduation_year']);
        }

        if (filled($filters['major_code'] ?? null)) {
            $builder->where('major_codes', (string) $filters['major_code']);
        }

        if (filled($filters['is_nationwide'] ?? null)) {
            $builder->where('is_nationwide', (int) $filters['is_nationwide']);
        }

        if (filled($filters['apply_open'] ?? null) && (bool) $filters['apply_open']) {
            $builder->where('is_apply_open', 1);
        }
    }

    /**
     * @param  Builder<Announcement>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyDatabaseFilters(Builder $query, array $filters): void
    {
        if (filled($filters['publisher_type'] ?? null)) {
            $query->where('publisher_type', (int) $filters['publisher_type']);
        }

        if (filled($filters['publisher_types'] ?? null)) {
            $query->whereIn(
                'publisher_type',
                array_map(static fn (mixed $value): int => (int) $value, (array) $filters['publisher_types']),
            );
        }

        if (filled($filters['employment_type'] ?? null)) {
            $employmentType = (int) $filters['employment_type'];

            $query->where(function (Builder $subQuery) use ($employmentType): void {
                $subQuery
                    ->whereJsonContains('employment_types', $employmentType)
                    ->orWhereJsonContains('employment_types', (string) $employmentType);
            });
        }

        if (filled($filters['education_level'] ?? null)) {
            $query->where('education_level', (int) $filters['education_level']);
        }

        if (filled($filters['graduation_year'] ?? null)) {
            $graduationYear = (int) $filters['graduation_year'];

            $query->where(function (Builder $subQuery) use ($graduationYear): void {
                $subQuery
                    ->whereJsonContains('graduation_years', $graduationYear)
                    ->orWhereJsonContains('graduation_years', (string) $graduationYear);
            });
        }

        if (filled($filters['major_code'] ?? null)) {
            $majorCode = (string) $filters['major_code'];

            $query->whereHas('majors', function (Builder $majorQuery) use ($majorCode): void {
                $majorQuery->where('major_code', $majorCode);
            });
        }

        if (filled($filters['city_code'] ?? null)) {
            $cityCode = (string) $filters['city_code'];
            $provinceCodePrefix = substr($cityCode, 0, 2).'%';

            $query->where(function (Builder $subQuery) use ($provinceCodePrefix): void {
                $subQuery
                    ->where('is_nationwide', true)
                    ->orWhereHas('cities', function (Builder $cityQuery) use ($provinceCodePrefix): void {
                        $cityQuery->whereLike('city_code', $provinceCodePrefix);
                    });
            });
        }

        if (filled($filters['tag_ids'] ?? null)) {
            $query->withTags(
                array_map(static fn (mixed $tagId): int => (int) $tagId, (array) $filters['tag_ids']),
                (bool) ($filters['tags_match_all'] ?? false),
            );
        }

        if (filled($filters['apply_open'] ?? null) && (bool) $filters['apply_open']) {
            $now = now();

            $query
                ->where('status', CmsPublishStatus::Published->value)
                ->where(function (Builder $subQuery) use ($now): void {
                    $subQuery
                        ->whereNull('apply_start_at')
                        ->orWhere('apply_start_at', '<=', $now);
                })
                ->where(function (Builder $subQuery) use ($now): void {
                    $subQuery
                        ->where('apply_deadline_type', RcAnnouncementApplyDeadlineType::UntilFilled->value)
                        ->orWhereNull('apply_end_at')
                        ->orWhere('apply_end_at', '>=', $now);
                });
        }
    }
}
