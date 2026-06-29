<?php

namespace App\Services;

use App\Discovery\Recommendation\JobRecommendationContext;
use App\Discovery\Recommendation\JobRecommendationCriteria;
use App\Discovery\Recommendation\JobRecommendationCriteriaResolver;
use App\Models\Rc\Job;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RcJobRecommendationService extends Service
{
    /**
     * @return array{
     *     criteria: JobRecommendationCriteria,
     *     paginator: LengthAwarePaginator<int, Job>
     * }
     */
    public function recommend(JobRecommendationContext $context, int $perPage): array
    {
        $criteria = (new JobRecommendationCriteriaResolver)->resolve($context);
        $filters = $criteria->toSearchFilters();

        if ($context->user instanceof User) {
            $filters['exclude_applied_candidate_user_id'] = $context->user->id;
        }

        $paginator = RcJobSearchService::make()->search(
            $perPage,
            $filters,
            $criteria->resolvedSortCriteria(),
        );

        return [
            'criteria' => $criteria,
            'paginator' => $paginator,
        ];
    }
}
