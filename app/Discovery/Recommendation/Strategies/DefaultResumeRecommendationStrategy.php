<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\ResumeRecommendationContext;
use App\Discovery\Recommendation\ResumeRecommendationCriteria;

class DefaultResumeRecommendationStrategy implements ResumeRecommendationStrategy
{
    public function priority(): int
    {
        return 10;
    }

    public function supports(ResumeRecommendationContext $context): bool
    {
        return true;
    }

    public function criteria(ResumeRecommendationContext $context): ResumeRecommendationCriteria
    {
        return new ResumeRecommendationCriteria(
            strategy: 'default',
            searchFilters: [],
            meta: [
                'company_id' => $context->company->id,
            ],
        );
    }
}
