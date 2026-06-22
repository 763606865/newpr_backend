<?php

namespace App\Services;

use App\Discovery\Recommendation\SchoolActivityRecommendationContext;
use App\Discovery\Recommendation\SchoolActivityRecommendationCriteria;
use App\Discovery\Recommendation\SchoolActivityRecommendationCriteriaResolver;
use App\Enums\RcSchoolActivityType;
use App\Models\Rc\SchoolActivity;

class RcSchoolActivityRecommendationService extends Service
{
    /**
     * @return array{
     *     dual_selections: list<SchoolActivity>,
     *     presentations: list<SchoolActivity>,
     *     job_fairs: list<SchoolActivity>,
     *     criteria: SchoolActivityRecommendationCriteria
     * }
     */
    public function recommendGrouped(SchoolActivityRecommendationContext $context, int $perType = 5): array
    {
        $criteria = (new SchoolActivityRecommendationCriteriaResolver)->resolve($context);
        $baseFilters = $criteria->toSearchFilters();

        $dualSelections = $this->recommendByType($baseFilters, RcSchoolActivityType::DualSelection, $perType);
        $presentations = $this->recommendByType($baseFilters, RcSchoolActivityType::Presentation, $perType);
        $jobFairs = $this->recommendByType($baseFilters, RcSchoolActivityType::JobFair, $perType);

        return [
            'dual_selections' => $dualSelections,
            'presentations' => $presentations,
            'job_fairs' => $jobFairs,
            'criteria' => $criteria,
        ];
    }

    /**
     * @param  array<string, mixed>  $baseFilters
     * @return list<SchoolActivity>
     */
    private function recommendByType(array $baseFilters, RcSchoolActivityType $type, int $perType): array
    {
        return RcSchoolActivitySearchService::make()
            ->search($perType, [
                ...$baseFilters,
                'type' => $type->value,
            ])
            ->items();
    }
}
