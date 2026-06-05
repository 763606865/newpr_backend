<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\ResumeRecommendationContext;
use App\Discovery\Recommendation\ResumeRecommendationCriteria;

interface ResumeRecommendationStrategy
{
    public function priority(): int;

    public function supports(ResumeRecommendationContext $context): bool;

    public function criteria(ResumeRecommendationContext $context): ResumeRecommendationCriteria;
}
