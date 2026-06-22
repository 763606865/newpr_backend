<?php

namespace App\Discovery\Search;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Models\Rc\SchoolActivity;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Builder as ScoutBuilder;

class SchoolActivitySearchFilterApplier
{
    /**
     * @param  Builder<SchoolActivity>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyDatabaseConstraints(Builder $query, array $filters = []): void
    {
        match ($filters['context'] ?? null) {
            'available' => $query->availableForRecruiter(),
            'public' => $query->published(),
            'school_organizer' => $query
                ->forOrganizer(
                    RcSchoolActivityOrganizerType::School,
                    (int) ($filters['organizer_id'] ?? 0),
                ),
            default => null,
        };

        if (($filters['context'] ?? null) === 'public') {
            return;
        }

        $this->applyDatabaseFilters($query, $filters);
    }

    /**
     * @param  Builder<SchoolActivity>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyPublicFilters(Builder $query, array $filters = []): void
    {
        if (filled($filters['region_code'] ?? null)) {
            $query->forRegion((string) $filters['region_code']);
        }

        $this->applyCommonFilters($query, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyIndexedFilters(ScoutBuilder $builder, array $filters): void
    {
        match ($filters['context'] ?? null) {
            'available' => $builder->where('is_available', 1),
            'public' => $builder->where('is_public', 1),
            'school_organizer' => $builder
                ->where('organizer_type', RcSchoolActivityOrganizerType::School->value)
                ->where('organizer_id', (int) ($filters['organizer_id'] ?? 0)),
            default => null,
        };

        if (filled($filters['types'] ?? null) && is_array($filters['types'])) {
            $builder->whereIn('type', array_map(static fn (mixed $type): int => (int) $type, $filters['types']));
        }

        if (filled($filters['organizer_types'] ?? null) && is_array($filters['organizer_types'])) {
            $builder->whereIn('organizer_type', array_map(static fn (mixed $type): string => (string) $type, $filters['organizer_types']));
        }

        $this->applyIndexedCommonFilters($builder, $filters);
    }

    /**
     * @param  Builder<SchoolActivity>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyDatabaseFilters(Builder $query, array $filters): void
    {
        $this->applyCommonFilters($query, $filters);
    }

    /**
     * @param  Builder<SchoolActivity>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyCommonFilters(Builder $query, array $filters): void
    {
        if (filled($filters['status'] ?? null)) {
            $query->where('status', (int) $filters['status']);
        }

        if (filled($filters['activity_status'] ?? null)) {
            $query->where('status', (int) $filters['activity_status']);
        }

        if (filled($filters['types'] ?? null) && is_array($filters['types'])) {
            $query->whereIn('type', array_map(static fn (mixed $type): int => (int) $type, $filters['types']));
        } elseif (filled($filters['type'] ?? null)) {
            $query->where('type', (int) $filters['type']);
        }

        if (filled($filters['organizer_types'] ?? null) && is_array($filters['organizer_types'])) {
            $query->whereIn('organizer_type', $filters['organizer_types']);
        } elseif (filled($filters['organizer_type'] ?? null)) {
            $query->where('organizer_type', (string) $filters['organizer_type']);
        }

        if (filled($filters['city_code'] ?? null)) {
            $query->where('city_code', (string) $filters['city_code']);
        }

        if (filled($filters['province_code'] ?? null)) {
            $query->where('province_code', (string) $filters['province_code']);
        }

        if (filled($filters['district_code'] ?? null)) {
            $query->where('district_code', (string) $filters['district_code']);
        }

        if (filled($filters['is_hot'] ?? null)) {
            $query->where('is_hot', (bool) $filters['is_hot']);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyIndexedCommonFilters(ScoutBuilder $builder, array $filters): void
    {
        if (filled($filters['status'] ?? null)) {
            $builder->where('status', (int) $filters['status']);
        }

        if (filled($filters['activity_status'] ?? null)) {
            $builder->where('status', (int) $filters['activity_status']);
        }

        if (filled($filters['type'] ?? null)) {
            $builder->where('type', (int) $filters['type']);
        }

        if (filled($filters['city_code'] ?? null)) {
            $builder->where('city_code', (string) $filters['city_code']);
        }

        if (filled($filters['province_code'] ?? null)) {
            $builder->where('province_code', (string) $filters['province_code']);
        }

        if (filled($filters['is_hot'] ?? null)) {
            $builder->where('is_hot', (int) $filters['is_hot']);
        }
    }
}
