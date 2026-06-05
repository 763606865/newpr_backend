<?php

namespace App\Discovery\Recommendation\Strategies;

use App\Discovery\Recommendation\ResumeRecommendationContext;
use App\Discovery\Recommendation\ResumeRecommendationCriteria;
use App\Models\Rc\Job;

class JobBasedResumeRecommendationStrategy implements ResumeRecommendationStrategy
{
    public function priority(): int
    {
        return 100;
    }

    public function supports(ResumeRecommendationContext $context): bool
    {
        return $context->resolvedJob() instanceof Job;
    }

    public function criteria(ResumeRecommendationContext $context): ResumeRecommendationCriteria
    {
        $job = $context->resolvedJob();

        if (! $job instanceof Job) {
            return (new DefaultResumeRecommendationStrategy)->criteria($context);
        }

        $filters = [];
        $meta = [
            'job_id' => $job->id,
            'job_title' => $job->title,
            'job_code' => $job->code,
        ];

        if (filled($job->city_code)) {
            $filters['current_city_code'] = (string) $job->city_code;
            $meta['current_city_code'] = $filters['current_city_code'];
        }

        if ($job->education_level !== null) {
            $filters['highest_education_level'] = (int) $job->education_level;
            $meta['highest_education_level'] = $filters['highest_education_level'];
        }

        if ($job->experience_min !== null) {
            $filters['work_years_min'] = (int) $job->experience_min;
            $meta['work_years_min'] = $filters['work_years_min'];
        }

        if ($job->experience_max !== null) {
            $filters['work_years_max'] = (int) $job->experience_max;
            $meta['work_years_max'] = $filters['work_years_max'];
        }

        if (filled($job->title)) {
            $filters['keyword'] = (string) $job->title;
            $meta['keyword'] = $filters['keyword'];
        }

        return new ResumeRecommendationCriteria(
            strategy: 'job',
            searchFilters: $filters,
            meta: $meta,
        );
    }
}
