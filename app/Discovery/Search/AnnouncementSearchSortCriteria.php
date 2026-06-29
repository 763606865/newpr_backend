<?php

namespace App\Discovery\Search;

use App\Models\Rc\Announcement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder as ScoutBuilder;

final class AnnouncementSearchSortCriteria
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
            ['column' => 'is_top', 'direction' => 'desc'],
            ['column' => 'sort', 'direction' => 'asc'],
            ['column' => 'published_at', 'direction' => 'desc'],
        ]);
    }

    /**
     * @return list<array{column: string, direction: string}>
     */
    public function scoutSortRules(): array
    {
        $sortableFields = AnnouncementSearchIndex::scoutSortableFields();

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
            $query->orderBy($rule['column'], $rule['direction']);
        }

        return $query;
    }

    /**
     * @param  Collection<int, Announcement>  $announcements
     * @return Collection<int, Announcement>
     */
    public function sortAnnouncementCollection(Collection $announcements): Collection
    {
        return $announcements->sort(function (Announcement $left, Announcement $right): int {
            foreach ($this->rules as $rule) {
                $leftValue = $left->getAttribute($rule['column']);
                $rightValue = $right->getAttribute($rule['column']);
                $comparison = $leftValue <=> $rightValue;

                if ($comparison !== 0) {
                    return strtolower($rule['direction']) === 'desc' ? -$comparison : $comparison;
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
}
