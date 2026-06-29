<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\AnnouncementRecommendationContext;
use App\Discovery\Recommendation\AnnouncementRecommendationCriteria;

interface AnnouncementRecommendationStrategy
{
    public function priority(): int;

    public function supports(AnnouncementRecommendationContext $context): bool;

    public function criteria(AnnouncementRecommendationContext $context): AnnouncementRecommendationCriteria;
}
