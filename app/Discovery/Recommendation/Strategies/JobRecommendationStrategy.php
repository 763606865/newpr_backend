<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\JobRecommendationContext;
use App\Discovery\Recommendation\JobRecommendationCriteria;

interface JobRecommendationStrategy
{
    public function priority(): int;

    public function supports(JobRecommendationContext $context): bool;

    public function criteria(JobRecommendationContext $context): JobRecommendationCriteria;
}
