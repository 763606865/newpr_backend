<?php

namespace App\Http\Controllers;

use App\Enums\CmsHomeRecommendationModuleType;
use App\Enums\CompanyStatus;
use App\Enums\RcJobStatus;
use App\Models\Cms\HomeRecommendation;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Resources\Cms\CmsHomeRecommendationResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecommendationController extends Controller
{
    /**
     * 首页推荐位
     *
     * GET /cms/home/recommendations
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $cityCode = $this->resolveCityCode($request);
        $validated = $request->validate([
            'module_type' => ['nullable', 'integer', Rule::in(array_column(CmsHomeRecommendationModuleType::cases(), 'value'))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ], []);

        $moduleType = isset($validated['module_type'])
            ? CmsHomeRecommendationModuleType::tryFrom((int) $validated['module_type'])
            : null;
        $perPage = $this->resolvePerPage($validated);

        $query = HomeRecommendation::query()
            ->enabled()
            ->active()
            ->forCity($cityCode)
            ->where(function (Builder $builder): void {
                $builder
                    ->whereHasMorph('recommendable', [Job::class], function (Builder $jobQuery): void {
                        $jobQuery->where('status', RcJobStatus::Published->value)
                            ->where(function (Builder $innerQuery): void {
                                $innerQuery->whereNull('expired_at')
                                    ->orWhere('expired_at', '>=', now());
                            });
                    })
                    ->orWhereHasMorph('recommendable', [Company::class], function (Builder $companyQuery): void {
                        $companyQuery->where('status', CompanyStatus::Enabled->value);
                    });
            })
            ->with('recommendable')
            ->orderBy('sort')
            ->orderByDesc('id');

        if ($moduleType instanceof CmsHomeRecommendationModuleType) {
            $query->forModule($moduleType);
        }

        $paginator = $query->paginate($perPage);

        $recommendations = $paginator->getCollection();
        $recommendations->loadMorph('recommendable', [
            Job::class => Job::discoveryRelations(),
            Company::class => [
                'profile',
                'albums' => fn ($albumsQuery) => $albumsQuery->enabled()->ordered(),
            ],
        ]);
        $recommendations->transform(
            fn (HomeRecommendation $recommendation): array => (new CmsHomeRecommendationResource($recommendation))->resolve($request),
        );

        return $this->success($paginator);
    }
}
