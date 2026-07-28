<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeExposure;
use App\Models\Rc\UserCompanyBlacklist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RcResumePromotionService extends Service
{
    /**
     * @param  LengthAwarePaginator<int, Resume>  $paginator
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Resume>
     */
    public function promote(
        LengthAwarePaginator $paginator,
        array $filters,
        Company $company,
    ): LengthAwarePaginator {
        if ($paginator->currentPage() !== 1 || $paginator->isEmpty()) {
            return $paginator;
        }

        $exposures = ResumeExposure::query()
            ->active()
            ->whereHas('resume', fn ($query) => $query->whereNotIn(
                'user_id',
                UserCompanyBlacklist::query()
                    ->select('user_id')
                    ->where('company_id', $company->id),
            ))
            ->orderBy('expired_at')
            ->orderBy('id')
            ->limit(100)
            ->get(['id', 'resume_id']);

        if ($exposures->isEmpty()) {
            return $paginator;
        }

        $filters['resume_ids'] = $exposures->pluck('resume_id')->all();
        $candidatePaginator = RcResumeSearchService::make()->search(
            min(100, max(1, $exposures->count())),
            $filters,
            'refreshed_at',
        );

        $exposureByResumeId = $exposures->keyBy('resume_id');
        $promotedResumes = $candidatePaginator->getCollection()
            ->take(count($this->promotionPositions($paginator->perPage())))
            ->each(function (Resume $resume) use ($exposureByResumeId): void {
                $resume->setAttribute('is_promoted', true);
                $resume->setAttribute('promotion_id', $exposureByResumeId->get($resume->id)?->id);
            })
            ->values();

        if ($promotedResumes->isEmpty()) {
            return $paginator;
        }

        $paginator->setCollection($this->mix(
            $paginator->getCollection(),
            $promotedResumes,
            $paginator->perPage(),
        ));

        RcResumeExposureStatsService::make()->recordImpressions(
            $paginator->getCollection()
                ->filter(fn (Resume $resume): bool => (bool) $resume->getAttribute('is_promoted'))
                ->values(),
            $company,
        );

        return $paginator;
    }

    /**
     * @param  Collection<int, Resume>  $organicResumes
     * @param  Collection<int, Resume>  $promotedResumes
     * @return Collection<int, Resume>
     */
    private function mix(Collection $organicResumes, Collection $promotedResumes, int $perPage): Collection
    {
        $positions = $this->promotionPositions($perPage);
        $promotedIds = $promotedResumes->pluck('id')->all();
        $organicQueue = $organicResumes
            ->reject(fn (Resume $resume): bool => in_array($resume->id, $promotedIds, true))
            ->values();
        $results = collect();

        for ($index = 0; $index < $perPage; $index++) {
            if (in_array($index, $positions, true) && $promotedResumes->isNotEmpty()) {
                $results->push($promotedResumes->shift());

                continue;
            }

            if ($organicQueue->isNotEmpty()) {
                $results->push($organicQueue->shift());
            }
        }

        return $results;
    }

    /**
     * @return list<int>
     */
    private function promotionPositions(int $perPage): array
    {
        return match (true) {
            $perPage >= 8 => [2, 7],
            $perPage >= 3 => [2],
            $perPage >= 1 => [0],
            default => [],
        };
    }
}
