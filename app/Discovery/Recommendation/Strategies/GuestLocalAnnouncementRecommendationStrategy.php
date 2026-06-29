<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\AnnouncementRecommendationContext;
use App\Discovery\Recommendation\AnnouncementRecommendationCriteria;

class GuestLocalAnnouncementRecommendationStrategy implements AnnouncementRecommendationStrategy
{
    public function priority(): int
    {
        return 10;
    }

    public function supports(AnnouncementRecommendationContext $context): bool
    {
        return true;
    }

    public function criteria(AnnouncementRecommendationContext $context): AnnouncementRecommendationCriteria
    {
        $filters = [
            'apply_open' => true,
        ];

        $cityCode = $context->resolvedCityCode();

        if (filled($cityCode)) {
            $filters['city_code'] = $cityCode;
        }

        return new AnnouncementRecommendationCriteria(
            strategy: 'guest_local',
            searchFilters: $filters,
            meta: [
                'city_code' => $cityCode,
            ],
        );
    }
}
