<?php

namespace App\Discovery\Recommendation;

use App\Discovery\Recommendation\Strategies\GuestLocalSchoolActivityRecommendationStrategy;
use App\Discovery\Recommendation\Strategies\IntentionSchoolActivityRecommendationStrategy;
use App\Discovery\Recommendation\Strategies\SchoolActivityRecommendationStrategy;

class SchoolActivityRecommendationCriteriaResolver
{
    /**
     * @var list<SchoolActivityRecommendationStrategy>
     */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            new IntentionSchoolActivityRecommendationStrategy,
            new GuestLocalSchoolActivityRecommendationStrategy,
        ];

        usort(
            $this->strategies,
            static fn (SchoolActivityRecommendationStrategy $left, SchoolActivityRecommendationStrategy $right): int => $right->priority() <=> $left->priority(),
        );
    }

    public function resolve(SchoolActivityRecommendationContext $context): SchoolActivityRecommendationCriteria
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($context)) {
                return $strategy->criteria($context);
            }
        }

        return (new GuestLocalSchoolActivityRecommendationStrategy)->criteria($context);
    }
}
