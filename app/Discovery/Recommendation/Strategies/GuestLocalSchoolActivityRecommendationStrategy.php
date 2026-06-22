<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\SchoolActivityRecommendationContext;
use App\Discovery\Recommendation\SchoolActivityRecommendationCriteria;

class GuestLocalSchoolActivityRecommendationStrategy implements SchoolActivityRecommendationStrategy
{
    public function priority(): int
    {
        return 10;
    }

    public function supports(SchoolActivityRecommendationContext $context): bool
    {
        return true;
    }

    public function criteria(SchoolActivityRecommendationContext $context): SchoolActivityRecommendationCriteria
    {
        $filters = [
            'context' => 'available',
        ];

        $cityCode = $context->resolvedCityCode();

        if (filled($cityCode)) {
            $filters['city_code'] = $cityCode;
        }

        return new SchoolActivityRecommendationCriteria(
            strategy: 'guest_local',
            searchFilters: $filters,
            meta: [
                'city_code' => $cityCode,
            ],
        );
    }
}
