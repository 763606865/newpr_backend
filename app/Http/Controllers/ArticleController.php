<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleIndexRequest;
use App\Models\Cms\Article;
use App\Resources\Cms\CmsArticleResource;
use App\Services\CmsArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * 校园资讯列表（分页）
     *
     * GET /cms/articles
     *
     * @throws \Exception
     */
    public function index(ArticleIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 50));

        $paginator = CmsArticleService::make()->paginate(
            $perPage,
            $request->searchFilters(),
        );

        $paginator->getCollection()->transform(
            fn (Article $article): array => (new CmsArticleResource($article))->resolve($request),
        );

        return api_response($paginator);
    }

    /**
     * 校园资讯详情
     *
     * GET /cms/articles/{id}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $cityCode = $request->string('city_code')->toString();
        $cityCode = $cityCode !== '' ? $cityCode : null;

        $article = CmsArticleService::make()->findPublished($id, $cityCode);

        if (! $article instanceof Article) {
            abort(404);
        }

        return api_response((new CmsArticleResource($article))->resolve($request));
    }
}
