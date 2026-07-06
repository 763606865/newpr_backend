<?php

namespace App\Filament\Resources\Cms\HomeRecommendations\Concerns;

use App\Enums\CmsHomeRecommendationModuleType;
use App\Models\Cms\HomeRecommendation;

trait InteractsWithHomeRecommendationForm
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mergeRecommendableIntoFormData(array $data, HomeRecommendation $recommendation): array
    {
        if ($recommendation->recommendable_type === 'job') {
            $data['job_id'] = $recommendation->recommendable_id;
            $data['campus_job_id'] = $recommendation->recommendable_id;
        }

        if ($recommendation->recommendable_type === 'company') {
            $data['company_id'] = $recommendation->recommendable_id;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyRecommendableToFormData(array $data): array
    {
        $moduleType = $this->resolveModuleType($data['module_type'] ?? null);

        if ($moduleType === null) {
            return $data;
        }

        if ($moduleType->isJobModule()) {
            $data['recommendable_type'] = 'job';
            $data['recommendable_id'] = (int) match ($moduleType) {
                CmsHomeRecommendationModuleType::CampusHotJob => ($data['campus_job_id'] ?? 0),
                default => ($data['job_id'] ?? 0),
            };
        }

        if ($moduleType->isCompanyModule()) {
            $data['recommendable_type'] = 'company';
            $data['recommendable_id'] = (int) ($data['company_id'] ?? 0);
        }

        unset($data['job_id'], $data['campus_job_id'], $data['company_id']);

        return $data;
    }

    private function resolveModuleType(mixed $moduleType): ?CmsHomeRecommendationModuleType
    {
        if ($moduleType instanceof CmsHomeRecommendationModuleType) {
            return $moduleType;
        }

        return CmsHomeRecommendationModuleType::tryFrom((int) $moduleType);
    }
}
