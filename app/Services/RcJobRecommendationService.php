<?php

namespace App\Services;

use App\Discovery\Recommendation\JobRecommendationContext;
use App\Discovery\Recommendation\JobRecommendationCriteria;
use App\Discovery\Recommendation\JobRecommendationCriteriaResolver;
use App\Models\Rc\Job;
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

        $paginator = RcJobSearchService::make()->search(
            $perPage,
            $criteria->toSearchFilters(),
            $criteria->sortColumn,
            $criteria->sortDirection,
        );

        return [
            'criteria' => $criteria,
            'paginator' => $paginator,
        ];
    }
}
