<?php

namespace App\Services;

use App\Discovery\Recommendation\ResumeRecommendationContext;
use App\Discovery\Recommendation\ResumeRecommendationCriteria;
use App\Discovery\Recommendation\ResumeRecommendationCriteriaResolver;
use App\Models\Rc\Resume;
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
        $filters = $criteria->toSearchFilters();
        $filters['exclude_blacklisted_users_for_company_id'] = $context->company->id;

        $paginator = RcResumeSearchService::make()->search(
            $perPage,
            $filters,
            $criteria->sortColumn,
            $criteria->sortDirection,
        );
        $paginator = RcResumePromotionService::make()->promote(
            $paginator,
            $filters,
            $context->company,
        );

        return [
            'criteria' => $criteria,
            'paginator' => $paginator,
        ];
    }
}
