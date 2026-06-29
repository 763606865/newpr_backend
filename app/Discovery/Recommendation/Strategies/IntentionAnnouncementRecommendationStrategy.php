<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\AnnouncementRecommendationContext;
use App\Discovery\Recommendation\AnnouncementRecommendationCriteria;
use App\Models\Rc\ResumeIntention;
use App\Models\User;

class IntentionAnnouncementRecommendationStrategy implements AnnouncementRecommendationStrategy
{
    public function priority(): int
    {
        return 100;
    }

    public function supports(AnnouncementRecommendationContext $context): bool
    {
        if (! $context->user instanceof User) {
            return false;
        }

        return $this->resolveIntention($context) instanceof ResumeIntention;
    }

    public function criteria(AnnouncementRecommendationContext $context): AnnouncementRecommendationCriteria
    {
        $intention = $this->resolveIntention($context);

        if (! $intention instanceof ResumeIntention) {
            return (new GuestLocalAnnouncementRecommendationStrategy)->criteria($context);
        }

        $filters = [
            'apply_open' => true,
        ];
        $meta = [
            'intention_id' => $intention->id,
            'resume_id' => $intention->resume_id,
        ];

        if (filled($intention->expected_city_code)) {
            $filters['city_code'] = (string) $intention->expected_city_code;
            $meta['city_code'] = $filters['city_code'];
        }

        if ($intention->employment_type !== null) {
            $filters['employment_type'] = $intention->employment_type->value;
            $meta['employment_type'] = $filters['employment_type'];
        }

        $resume = $intention->resume;

        if ($resume !== null && $resume->highest_education_level !== null) {
            $filters['education_level'] = $resume->highest_education_level->value;
            $meta['education_level'] = $filters['education_level'];
        }

        if ($resume !== null && $resume->is_fresh_graduate) {
            $filters['graduation_year'] = (int) now()->format('Y');
            $meta['graduation_year'] = $filters['graduation_year'];
        }

        return new AnnouncementRecommendationCriteria(
            strategy: 'intention',
            searchFilters: $filters,
            meta: $meta,
        );
    }

    private function resolveIntention(AnnouncementRecommendationContext $context): ?ResumeIntention
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

        return $this->isMeaningfulIntention($intention) ? $intention : null;
    }

    private function isMeaningfulIntention(ResumeIntention $intention): bool
    {
        if (filled($intention->expected_city_code)) {
            return true;
        }

        if ($intention->employment_type !== null) {
            return true;
        }

        return false;
    }
}
