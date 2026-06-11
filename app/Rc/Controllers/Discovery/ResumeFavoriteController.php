<?php

namespace App\Rc\Controllers\Discovery;

use App\Models\Company;
use App\Models\Rc\ResumeFavorite;
use App\Rc\Controllers\Controller;
use App\Resources\Rc\RcResumePreviewResource;
use App\Services\RcJobService;
use App\Services\RcResumeFavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class ResumeFavoriteController extends Controller
{
    /**
     * 我收藏的简历列表
     *
     * GET /rc/talent/favorites/resumes
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcResumeFavoriteService::make()->paginateForUser(
            $this->user(),
            $company,
            $this->getPerPage($request),
        );

        $paginator->getCollection()->transform(
            function (ResumeFavorite $favorite) use ($request): array {
                $data = (new RcResumePreviewResource($favorite->resume))->resolve($request);
                $data['is_favorited'] = true;
                $data['favorited_at'] = $favorite->created_at;

                return $data;
            },
        );

        return $this->success($paginator);
    }

    /**
     * 收藏简历
     *
     * POST /rc/talent/resumes/{id}/favorite
     */
    public function store(int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $resume = RcResumeFavoriteService::make()->resolveDiscoverableResumeOrFail($id);
            RcResumeFavoriteService::make()->favorite($this->user(), $company, $resume);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'is_favorited' => true,
        ]);
    }

    /**
     * 取消收藏简历
     *
     * DELETE /rc/talent/resumes/{id}/favorite
     */
    public function destroy(int $id): JsonResponse
    {
        $company = $this->resolveCompany();

        if (! $company instanceof Company) {
            return $this->error('请先切换为招聘方身份并绑定企业。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $resume = RcResumeFavoriteService::make()->resolveDiscoverableResumeOrFail($id);
            RcResumeFavoriteService::make()->unfavorite($this->user(), $company, $resume);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'is_favorited' => false,
        ]);
    }

    private function resolveCompany(): ?Company
    {
        return RcJobService::make()->resolveRecruiterCompany($this->user());
    }
}
