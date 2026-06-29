<?php

namespace Tests\Unit\Discovery\Search;

use App\Discovery\Search\JobSearchSortCriteria;
use App\Models\Rc\Job;
use Tests\TestCase;

class JobSearchSortCriteriaTest extends TestCase
{
    public function test_default_profile_prioritizes_urgent_jobs_then_published_at(): void
    {
        $criteria = JobSearchSortCriteria::default();

        $this->assertSame('default', $criteria->profile);
        $this->assertSame([
            ['column' => 'is_urgent', 'direction' => 'desc'],
            ['column' => 'published_at', 'direction' => 'desc'],
        ], $criteria->rules);
        $this->assertSame([
            'profile' => 'default',
            'rules' => [
                ['column' => 'is_urgent', 'direction' => 'desc'],
                ['column' => 'published_at', 'direction' => 'desc'],
            ],
            'scout_sort_rules' => [
                ['column' => 'is_urgent', 'direction' => 'desc'],
                ['column' => 'published_at', 'direction' => 'desc'],
            ],
        ], $criteria->toMeta());
    }

    public function test_scout_sort_rules_only_include_indexed_sortable_fields(): void
    {
        $criteria = new JobSearchSortCriteria('custom', [
            ['column' => 'is_recommended', 'direction' => 'desc'],
            ['column' => 'is_urgent', 'direction' => 'desc'],
            ['column' => 'published_at', 'direction' => 'desc'],
        ]);

        $this->assertSame([
            ['column' => 'is_urgent', 'direction' => 'desc'],
            ['column' => 'published_at', 'direction' => 'desc'],
        ], $criteria->scoutSortRules());
    }

    public function test_sort_job_collection_respects_active_urgent_and_published_at(): void
    {
        $criteria = JobSearchSortCriteria::default();

        $newerNonUrgent = new Job([
            'is_urgent' => false,
            'published_at' => now(),
        ]);
        $newerNonUrgent->id = 1;

        $olderActiveUrgent = new Job([
            'is_urgent' => true,
            'urgent_until' => now()->addDay(),
            'published_at' => now()->subDay(),
        ]);
        $olderActiveUrgent->id = 2;

        $sorted = $criteria->sortJobCollection(collect([$newerNonUrgent, $olderActiveUrgent]));

        $this->assertSame(2, $sorted->first()?->id);
    }
}
