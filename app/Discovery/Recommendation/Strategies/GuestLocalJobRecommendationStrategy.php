<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\JobRecommendationContext;
use App\Discovery\Recommendation\JobRecommendationCriteria;

class GuestLocalJobRecommendationStrategy implements JobRecommendationStrategy
{
    private const GUEST_MIN_SALARY = 10000;

    public function priority(): int
    {
        return 10;
    }

    public function supports(JobRecommendationContext $context): bool
    {
        return true;
    }

    public function criteria(JobRecommendationContext $context): JobRecommendationCriteria
    {
        $filters = [
            'salary_min' => self::GUEST_MIN_SALARY,
        ];

        $cityCode = $context->resolvedCityCode();

        if (filled($cityCode)) {
            $filters['city_code'] = $cityCode;
        }

        return new JobRecommendationCriteria(
            strategy: 'guest_local',
            searchFilters: $filters,
            meta: [
                'city_code' => $cityCode,
                'salary_min' => self::GUEST_MIN_SALARY,
            ],
        );
    }
}
