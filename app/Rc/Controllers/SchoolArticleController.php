<?php

namespace App\Rc\Controllers;

use App\Models\Cms\Article;
use App\Models\School;
use App\Rc\Controllers\Concerns\ResolvesRcOrganizations;
use App\Rc\Requests\SchoolArticleIndexRequest;
use App\Rc\Requests\SchoolArticleStoreRequest;
use App\Rc\Requests\SchoolArticleUpdateRequest;
use App\Resources\Rc\RcArticleResource;
use App\Services\RcSchoolArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class SchoolArticleController extends Controller
{
    use ResolvesRcOrganizations;

    /**
     * 校招负责人-校园资讯列表
     *
     * GET /rc/schools/articles
     */
    public function index(SchoolArticleIndexRequest $request): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paginator = RcSchoolArticleService::make()->paginateForSchool(
            $school,
            $this->getPerPage($request),
            $request->validated(),
        );

        $paginator->getCollection()->transform(
            fn (Article $article): array => (new RcArticleResource($article))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 校招负责人-校园资讯详情
     *
     * GET /rc/schools/articles/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $article = RcSchoolArticleService::make()->findForSchool($school, $id);

        if (! $article instanceof Article) {
            return $this->error('资讯不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'article' => (new RcArticleResource($article))->resolve($request),
        ]);
    }

    /**
     * 创建校园资讯
     *
     * POST /rc/schools/articles
     */
    public function store(SchoolArticleStoreRequest $request): JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $article = RcSchoolArticleService::make()->createForSchool($school, $request->validated());

        return $this->success([
            'article' => (new RcArticleResource($article))->resolve($request),
        ]);
    }

    /**
     * 更新校园资讯
     *
     * PUT /rc/schools/articles/{id}
     */
    public function update(SchoolArticleUpdateRequest $request, int $id): JsonResponse
    {
        $article = $this->resolveOwnedArticle($id);

        if ($article instanceof JsonResponse) {
            return $article;
        }

        try {
            $article = RcSchoolArticleService::make()->update($article, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'article' => (new RcArticleResource($article))->resolve($request),
        ]);
    }

    /**
     * 删除校园资讯
     *
     * DELETE /rc/schools/articles/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $article = $this->resolveOwnedArticle($id);

        if ($article instanceof JsonResponse) {
            return $article;
        }

        try {
            RcSchoolArticleService::make()->delete($article);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success();
    }

    /**
     * 发布校园资讯
     *
     * POST /rc/schools/articles/{id}/publish
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $article = $this->resolveOwnedArticle($id);

        if ($article instanceof JsonResponse) {
            return $article;
        }

        try {
            $article = RcSchoolArticleService::make()->publish($article);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'article' => (new RcArticleResource($article))->resolve($request),
        ]);
    }

    /**
     * 下线校园资讯
     *
     * POST /rc/schools/articles/{id}/offline
     */
    public function offline(Request $request, int $id): JsonResponse
    {
        $article = $this->resolveOwnedArticle($id);

        if ($article instanceof JsonResponse) {
            return $article;
        }

        try {
            $article = RcSchoolArticleService::make()->offline($article);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'article' => (new RcArticleResource($article))->resolve($request),
        ]);
    }

    private function resolveOwnedArticle(int $articleId): Article|JsonResponse
    {
        $school = $this->resolveCampusManagerSchool();

        if (! $school instanceof School) {
            return $this->error('请先切换为校招负责人身份并绑定学校。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $article = RcSchoolArticleService::make()->findForSchool($school, $articleId);

        if (! $article instanceof Article) {
            return $this->error('资讯不存在。', Response::HTTP_NOT_FOUND);
        }

        return $article;
    }
}
