<?php

namespace App\Discovery\Recommendation;

use App\Discovery\Recommendation\Strategies\GuestLocalJobRecommendationStrategy;
use App\Discovery\Recommendation\Strategies\IntentionJobRecommendationStrategy;
use App\Discovery\Recommendation\Strategies\JobRecommendationStrategy;

class JobRecommendationCriteriaResolver
{
    /**
     * @var list<JobRecommendationStrategy>
     */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            new IntentionJobRecommendationStrategy,
            new GuestLocalJobRecommendationStrategy,
        ];

        usort(
            $this->strategies,
            static fn (JobRecommendationStrategy $left, JobRecommendationStrategy $right): int => $right->priority() <=> $left->priority(),
        );
    }

    public function resolve(JobRecommendationContext $context): JobRecommendationCriteria
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($context)) {
                return $strategy->criteria($context);
            }
        }

        return (new GuestLocalJobRecommendationStrategy)->criteria($context);
    }
}
