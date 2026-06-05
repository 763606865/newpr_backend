<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\JobRecommendationContext;
use App\Discovery\Recommendation\JobRecommendationCriteria;
use App\Models\Rc\Position;
use App\Models\Rc\ResumeIntention;
use App\Models\User;

class IntentionJobRecommendationStrategy implements JobRecommendationStrategy
{
    public function priority(): int
    {
        return 100;
    }

    public function supports(JobRecommendationContext $context): bool
    {
        if (! $context->user instanceof User) {
            return false;
        }

        return $this->resolveIntention($context) instanceof ResumeIntention;
    }

    public function criteria(JobRecommendationContext $context): JobRecommendationCriteria
    {
        $intention = $this->resolveIntention($context);

        if (! $intention instanceof ResumeIntention) {
            return (new GuestLocalJobRecommendationStrategy)->criteria($context);
        }

        $filters = [];
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

        if (filled($intention->expected_position_id)) {
            $positionCode = Position::query()
                ->whereKey($intention->expected_position_id)
                ->value('code');

            if (filled($positionCode)) {
                $filters['position_code'] = (string) $positionCode;
                $meta['position_code'] = $filters['position_code'];
            }
        }

        if ($intention->salary_min !== null) {
            $filters['salary_min'] = (float) $intention->salary_min;
            $meta['salary_min'] = $filters['salary_min'];
        }

        if ($intention->salary_max !== null) {
            $filters['salary_max'] = (float) $intention->salary_max;
            $meta['salary_max'] = $filters['salary_max'];
        }

        $industryCodes = collect($intention->expected_industry_codes ?? [])
            ->filter(static fn (mixed $code): bool => filled($code))
            ->map(static fn (mixed $code): string => (string) $code)
            ->values()
            ->all();

        if ($industryCodes !== []) {
            $meta['industry_codes'] = $industryCodes;
            $meta['industry_filter_pending'] = true;
        }

        return new JobRecommendationCriteria(
            strategy: 'intention',
            searchFilters: $filters,
            meta: $meta,
        );
    }

    private function resolveIntention(JobRecommendationContext $context): ?ResumeIntention
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

        if (filled($intention->expected_position_id)) {
            return true;
        }

        if (filled($intention->expected_industry_codes)) {
            return true;
        }

        if ($intention->employment_type !== null) {
            return true;
        }

        if ($intention->salary_min !== null || $intention->salary_max !== null) {
            return true;
        }

        return false;
    }
}
