<?php

namespace App\Discovery\Recommendation;

use App\Discovery\Recommendation\Strategies\AnnouncementRecommendationStrategy;
use App\Discovery\Recommendation\Strategies\GuestLocalAnnouncementRecommendationStrategy;
use App\Discovery\Recommendation\Strategies\IntentionAnnouncementRecommendationStrategy;

class AnnouncementRecommendationCriteriaResolver
{
    /**
     * @var list<AnnouncementRecommendationStrategy>
     */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            new IntentionAnnouncementRecommendationStrategy,
            new GuestLocalAnnouncementRecommendationStrategy,
        ];

        usort(
            $this->strategies,
            static fn (AnnouncementRecommendationStrategy $left, AnnouncementRecommendationStrategy $right): int => $right->priority() <=> $left->priority(),
        );
    }

    public function resolve(AnnouncementRecommendationContext $context): AnnouncementRecommendationCriteria
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($context)) {
                return $strategy->criteria($context);
            }
        }

        return (new GuestLocalAnnouncementRecommendationStrategy)->criteria($context);
    }
}
