<?php

namespace App\Discovery\Recommendation;

use App\Models\Rc\Resume;
use App\Services\RcResumeSearchService;
use App\Services\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RcResumeRecommendationService extends Service
{
    /**
     * @return array{
     *     criteria: ResumeRecommendationCriteria,
     *     paginator: LengthAwarePaginator<int, Resume>
     * }
     */
    public function recommend(ResumeRecommendationContext $context, int $perPage): array
    {
        $criteria = (new ResumeRecommendationCriteriaResolver)->resolve($context);

        $paginator = RcResumeSearchService::make()->search(
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
