<?php

namespace App\Discovery\Recommendation;

use App\Discovery\Recommendation\Strategies\DefaultResumeRecommendationStrategy;
use App\Discovery\Recommendation\Strategies\JobBasedResumeRecommendationStrategy;
use App\Discovery\Recommendation\Strategies\ResumeRecommendationStrategy;

class ResumeRecommendationCriteriaResolver
{
    /**
     * @var list<ResumeRecommendationStrategy>
     */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            new JobBasedResumeRecommendationStrategy,
            new DefaultResumeRecommendationStrategy,
        ];

        usort(
            $this->strategies,
            static fn (ResumeRecommendationStrategy $left, ResumeRecommendationStrategy $right): int => $right->priority() <=> $left->priority(),
        );
    }

    public function resolve(ResumeRecommendationContext $context): ResumeRecommendationCriteria
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($context)) {
                return $strategy->criteria($context);
            }
        }

        return (new DefaultResumeRecommendationStrategy)->criteria($context);
    }
}
