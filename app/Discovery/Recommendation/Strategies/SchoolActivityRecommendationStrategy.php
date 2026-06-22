<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\SchoolActivityRecommendationContext;
use App\Discovery\Recommendation\SchoolActivityRecommendationCriteria;

interface SchoolActivityRecommendationStrategy
{
    public function priority(): int;

    public function supports(SchoolActivityRecommendationContext $context): bool;

    public function criteria(SchoolActivityRecommendationContext $context): SchoolActivityRecommendationCriteria;
}
