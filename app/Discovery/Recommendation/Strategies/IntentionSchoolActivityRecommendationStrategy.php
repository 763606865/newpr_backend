<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\SchoolActivityRecommendationContext;
use App\Discovery\Recommendation\SchoolActivityRecommendationCriteria;
use App\Models\Rc\ResumeIntention;
use App\Models\User;

class IntentionSchoolActivityRecommendationStrategy implements SchoolActivityRecommendationStrategy
{
    public function priority(): int
    {
        return 100;
    }

    public function supports(SchoolActivityRecommendationContext $context): bool
    {
        if (! $context->user instanceof User) {
            return false;
        }

        return $this->resolveIntention($context) instanceof ResumeIntention;
    }

    public function criteria(SchoolActivityRecommendationContext $context): SchoolActivityRecommendationCriteria
    {
        $intention = $this->resolveIntention($context);

        if (! $intention instanceof ResumeIntention) {
            return (new GuestLocalSchoolActivityRecommendationStrategy)->criteria($context);
        }

        $filters = [
            'context' => 'available',
        ];
        $meta = [
            'intention_id' => $intention->id,
            'resume_id' => $intention->resume_id,
        ];

        if (filled($intention->expected_city_code)) {
            $filters['city_code'] = (string) $intention->expected_city_code;
            $meta['city_code'] = $filters['city_code'];
        }

        return new SchoolActivityRecommendationCriteria(
            strategy: 'intention',
            searchFilters: $filters,
            meta: $meta,
        );
    }

    private function resolveIntention(SchoolActivityRecommendationContext $context): ?ResumeIntention
    {
        if (! $context->user instanceof User) {
            return null;
        }

        $resume = $context->user->resumes()
            ->orderByDesc('is_primary')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if ($resume === null) {
            return null;
        }

        $intention = $resume->intentions()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (! $intention instanceof ResumeIntention) {
            return null;
        }

        return filled($intention->expected_city_code) ? $intention : null;
    }
}
