<?php

namespace Tests\Unit\Discovery\Recommendation;

use App\Discovery\Recommendation\SchoolActivityRecommendationContext;
use App\Discovery\Recommendation\Strategies\GuestLocalSchoolActivityRecommendationStrategy;
use Tests\TestCase;

class GuestLocalSchoolActivityRecommendationStrategyTest extends TestCase
{
    public function test_criteria_includes_city_and_available_context(): void
    {
        $criteria = (new GuestLocalSchoolActivityRecommendationStrategy)->criteria(
            new SchoolActivityRecommendationContext(cityHint: '360100'),
        );

        $this->assertSame('guest_local', $criteria->strategy);
        $this->assertSame('available', $criteria->searchFilters['context']);
        $this->assertSame('360100', $criteria->searchFilters['city_code']);
        $this->assertSame('360100', $criteria->meta['city_code']);
    }

    public function test_criteria_works_without_city_hint(): void
    {
        $criteria = (new GuestLocalSchoolActivityRecommendationStrategy)->criteria(
            new SchoolActivityRecommendationContext,
        );

        $this->assertSame('available', $criteria->searchFilters['context']);
        $this->assertArrayNotHasKey('city_code', $criteria->searchFilters);
    }
}
