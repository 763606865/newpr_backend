<?php

namespace Tests\Unit\Discovery\Recommendation;

use App\Discovery\Recommendation\JobRecommendationContext;
use App\Discovery\Recommendation\Strategies\GuestLocalJobRecommendationStrategy;
use Tests\TestCase;

class GuestLocalJobRecommendationStrategyTest extends TestCase
{
    public function test_it_builds_guest_local_criteria_with_city_and_salary_floor(): void
    {
        $criteria = (new GuestLocalJobRecommendationStrategy)->criteria(
            new JobRecommendationContext(cityHint: '360100'),
        );

        $this->assertSame('guest_local', $criteria->strategy);
        $this->assertSame('360100', $criteria->searchFilters['city_code']);
        $this->assertSame(10000, $criteria->searchFilters['salary_min']);
    }

    public function test_it_builds_guest_local_criteria_without_city_when_missing(): void
    {
        $criteria = (new GuestLocalJobRecommendationStrategy)->criteria(
            new JobRecommendationContext,
        );

        $this->assertSame('guest_local', $criteria->strategy);
        $this->assertArrayNotHasKey('city_code', $criteria->searchFilters);
        $this->assertSame(10000, $criteria->searchFilters['salary_min']);
    }
}
