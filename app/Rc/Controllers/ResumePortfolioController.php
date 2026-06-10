<?php

namespace App\Rc\Controllers;

use App\Models\Rc\Resume;
use App\Models\Rc\ResumePortfolio;
use App\Models\User;
use App\Rc\Controllers\Concerns\FindsOwnedResume;
use App\Rc\Requests\ResumePortfolioStoreRequest;
use App\Rc\Requests\ResumePortfolioUpdateRequest;
use App\Resources\Rc\RcResumePortfolioResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ResumePortfolioController extends Controller
{
    use FindsOwnedResume;

    /**
     * 简历个人作品列表
     *
     * GET /rc/resumes/{id}/portfolios
     *
     * @throws \Exception
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        /** @var Collection<int, array<string, mixed>> $portfolios */
        $portfolios = RcResumePortfolioResource::collection(
            $resume->portfolios()->orderByDesc('sort')->orderByDesc('id')->get(),
        )->resolve($request);

        return $this->success(['portfolios' => $portfolios]);
    }

    /**
     * 简历个人作品详情
     *
     * GET /rc/resumes/{id}/portfolios/{portfolioId}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id, int $portfolioId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $portfolio = $this->findOwnedResumeItem(ResumePortfolio::class, $resume->id, $portfolioId);

        if (! $portfolio instanceof ResumePortfolio) {
            return $this->error('个人作品不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcResumePortfolioResource($portfolio))->resolve($request));
    }

    /**
     * 新增简历个人作品
     *
     * POST /rc/resumes/{id}/portfolios
     *
     * @throws \Exception
     */
    public function store(ResumePortfolioStoreRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $portfolio = new ResumePortfolio;
        $portfolio->fill($request->validated());
        $portfolio->resume_id = $resume->id;
        $portfolio->user_id = $user->id;
        $portfolio->save();

        return $this->success((new RcResumePortfolioResource($portfolio->fresh()))->resolve($request));
    }

    /**
     * 编辑简历个人作品
     *
     * PUT /rc/resumes/{id}/portfolios/{portfolioId}
     *
     * @throws \Exception
     */
    public function update(ResumePortfolioUpdateRequest $request, int $id, int $portfolioId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $portfolio = $this->findOwnedResumeItem(ResumePortfolio::class, $resume->id, $portfolioId);

        if (! $portfolio instanceof ResumePortfolio) {
            return $this->error('个人作品不存在。', Response::HTTP_NOT_FOUND);
        }

        $portfolio->fill($request->validated());
        $portfolio->save();

        return $this->success((new RcResumePortfolioResource($portfolio->fresh()))->resolve($request));
    }

    /**
     * 删除简历个人作品
     *
     * DELETE /rc/resumes/{id}/portfolios/{portfolioId}
     *
     * @throws \Exception
     */
    public function destroy(Request $request, int $id, int $portfolioId): JsonResponse
    {
        $resume = $this->findOwnedResume($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $portfolio = $this->findOwnedResumeItem(ResumePortfolio::class, $resume->id, $portfolioId);

        if (! $portfolio instanceof ResumePortfolio) {
            return $this->error('个人作品不存在。', Response::HTTP_NOT_FOUND);
        }

        $portfolio->delete();

        return $this->success((object) []);
    }
}
