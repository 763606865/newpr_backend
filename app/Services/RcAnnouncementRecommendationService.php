<?php

namespace App\Services;

use App\Discovery\Recommendation\AnnouncementRecommendationContext;
use App\Discovery\Recommendation\AnnouncementRecommendationCriteria;
use App\Discovery\Recommendation\AnnouncementRecommendationCriteriaResolver;
use App\Models\Rc\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RcAnnouncementRecommendationService extends Service
{
    /**
     * @return array{
     *     criteria: AnnouncementRecommendationCriteria,
     *     paginator: LengthAwarePaginator<int, Announcement>
     * }
     */
    public function recommend(AnnouncementRecommendationContext $context, int $perPage): array
    {
        $criteria = (new AnnouncementRecommendationCriteriaResolver)->resolve($context);

        $paginator = RcAnnouncementSearchService::make()->search(
            $perPage,
            $criteria->toSearchFilters(),
            $criteria->resolvedSortCriteria(),
        );

        return [
            'criteria' => $criteria,
            'paginator' => $paginator,
        ];
    }
}
