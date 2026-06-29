<?php

namespace App\Services;

use App\Enums\CmsHomeRecommendationModuleType;
use App\Enums\CompanyStatus;
use App\Enums\RcJobStatus;
use App\Models\Cms\HomeRecommendation;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Resources\Cms\CmsHomeRecommendationResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CmsHomeRecommendationService extends Service
{
    /**
     * @return array{
     *     urgent_jobs: list<array<string, mixed>>,
     *     hot_jobs: list<array<string, mixed>>,
     *     famous_companies: list<array<string, mixed>>
     * }
     */
    public function groupedForHome(?string $cityCode, Request $request): array
    {
        return [
            'urgent_jobs' => $this->resolveModulePayloads(
                CmsHomeRecommendationModuleType::UrgentJob,
                $cityCode,
                $request,
            ),
            'hot_jobs' => $this->resolveModulePayloads(
                CmsHomeRecommendationModuleType::HotJob,
                $cityCode,
                $request,
            ),
            'famous_companies' => $this->resolveModulePayloads(
                CmsHomeRecommendationModuleType::FamousCompany,
                $cityCode,
                $request,
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveModulePayloads(
        CmsHomeRecommendationModuleType $moduleType,
        ?string $cityCode,
        Request $request,
    ): array {
        $recommendations = $this->queryActiveRecommendations($moduleType, $cityCode);

        return CmsHomeRecommendationResource::collection(
            $recommendations
                ->filter(fn (HomeRecommendation $recommendation): bool => $this->isRecommendableAvailable($recommendation))
                ->values(),
        )->resolve($request);
    }

    /**
     * @return Collection<int, HomeRecommendation>
     */
    private function queryActiveRecommendations(
        CmsHomeRecommendationModuleType $moduleType,
        ?string $cityCode,
    ): Collection {
        return HomeRecommendation::query()
            ->enabled()
            ->active()
            ->forCity($cityCode)
            ->forModule($moduleType)
            ->with($this->recommendationRelations($moduleType))
            ->orderBy('sort')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return list<string>
     */
    private function recommendationRelations(CmsHomeRecommendationModuleType $moduleType): array
    {
        if ($moduleType->isJobModule()) {
            return Job::discoveryRelationsWithPrefix('recommendable');
        }

        return [
            'recommendable.profile',
        ];
    }

    private function isRecommendableAvailable(HomeRecommendation $recommendation): bool
    {
        $recommendable = $recommendation->recommendable;

        if ($recommendable instanceof Job) {
            if ($recommendable->status !== RcJobStatus::Published) {
                return false;
            }

            if ($recommendable->expired_at !== null && $recommendable->expired_at->isPast()) {
                return false;
            }

            return true;
        }

        if ($recommendable instanceof Company) {
            return $recommendable->status === CompanyStatus::Enabled;
        }

        return false;
    }
}
