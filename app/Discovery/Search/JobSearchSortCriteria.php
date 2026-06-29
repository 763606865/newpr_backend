<?php

namespace App\Discovery\Search;

use App\Models\Rc\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder as ScoutBuilder;

final class JobSearchSortCriteria
{
    /**
     * @param  list<array{column: string, direction: string}>  $rules
     */
    public function __construct(
        public readonly string $profile,
        public readonly array $rules,
    ) {}

    public static function default(): self
    {
        return new self('default', [
            ['column' => 'is_urgent', 'direction' => 'desc'],
            ['column' => 'published_at', 'direction' => 'desc'],
        ]);
    }

    /**
     * @return list<array{column: string, direction: string}>
     */
    public function scoutSortRules(): array
    {
        $sortableFields = JobSearchIndex::scoutSortableFields();

        return array_values(array_filter(
            $this->rules,
            static fn (array $rule): bool => in_array($rule['column'], $sortableFields, true),
        ));
    }

    public function applyToScoutBuilder(ScoutBuilder $builder): ScoutBuilder
    {
        foreach ($this->scoutSortRules() as $rule) {
            $builder->orderBy($rule['column'], $rule['direction']);
        }

        return $builder;
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function applyToQuery(Builder $query): Builder
    {
        foreach ($this->rules as $rule) {
            if ($rule['column'] === 'is_urgent') {
                $this->applyActiveUrgentOrder($query, $rule['direction']);

                continue;
            }

            $query->orderBy($rule['column'], $rule['direction']);
        }

        return $query;
    }

    /**
     * @param  Collection<int, Job>  $jobs
     * @return Collection<int, Job>
     */
    public function sortJobCollection(Collection $jobs): Collection
    {
        return $jobs->sort(function (Job $left, Job $right): int {
            foreach ($this->rules as $rule) {
                $comparison = $this->compareJobsByRule($left, $right, $rule);

                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return 0;
        })->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function toMeta(): array
    {
        return [
            'profile' => $this->profile,
            'rules' => $this->rules,
            'scout_sort_rules' => $this->scoutSortRules(),
        ];
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function applyActiveUrgentOrder(Builder $query, string $direction): void
    {
        $expression = 'CASE WHEN is_urgent = 1 AND (urgent_until IS NULL OR urgent_until >= ?) THEN 1 ELSE 0 END';
        $sortDirection = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';

        $query->orderByRaw("{$expression} {$sortDirection}", [now()]);
    }

    private function compareJobsByRule(Job $left, Job $right, array $rule): int
    {
        $leftValue = $this->resolveSortValue($left, $rule['column']);
        $rightValue = $this->resolveSortValue($right, $rule['column']);

        $comparison = $leftValue <=> $rightValue;

        return strtolower($rule['direction']) === 'desc' ? -$comparison : $comparison;
    }

    private function resolveSortValue(Job $job, string $column): mixed
    {
        if ($column === 'is_urgent') {
            return $job->hasActiveUrgentHighlight() ? 1 : 0;
        }

        return $job->getAttribute($column);
    }
}
