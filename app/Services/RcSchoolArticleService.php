<?php

namespace App\Services;

use App\Enums\CmsArticleContentType;
use App\Enums\CmsPublishStatus;
use App\Models\Cms\Article;
use App\Models\Cms\ArticleContent;
use App\Models\School;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RcSchoolArticleService extends Service
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Article>
     */
    public function paginateForSchool(School $school, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = Article::query()
            ->where('school_code', $school->school_code)
            ->with(['category', 'tags'])
            ->orderByDesc('is_top')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if (filled($filters['status'] ?? null)) {
            $query->where('status', (int) $filters['status']);
        }

        if (filled($filters['category_id'] ?? null)) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (filled($filters['keyword'] ?? null)) {
            $keyword = (string) $filters['keyword'];
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('title', 'like', "%{$keyword}%")
                    ->orWhere('summary', 'like', "%{$keyword}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findForSchool(School $school, int $articleId): ?Article
    {
        return Article::query()
            ->where('school_code', $school->school_code)
            ->with(['category', 'content', 'tags'])
            ->find($articleId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForSchool(School $school, array $data): Article
    {
        return DB::transaction(function () use ($school, $data): Article {
            $contentPayload = $this->extractContentPayload($data);

            $article = Article::query()->create([
                ...$this->articleAttributes($data),
                'school_code' => $school->school_code,
                'status' => CmsPublishStatus::Draft,
            ]);

            $this->syncContent($article, $contentPayload);
            $this->syncTags($article, $data['tag_ids'] ?? null);

            return $article->refresh()->load(['category', 'content', 'tags']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Article $article, array $data): Article
    {
        if ($article->status === CmsPublishStatus::Published) {
            throw new InvalidArgumentException('已发布资讯请先下线后再编辑。');
        }

        return DB::transaction(function () use ($article, $data): Article {
            $contentPayload = $this->extractContentPayload($data);

            $article->fill($this->articleAttributes($data))->save();

            if ($contentPayload !== null) {
                $this->syncContent($article, $contentPayload);
            }

            if (array_key_exists('tag_ids', $data)) {
                $this->syncTags($article, $data['tag_ids']);
            }

            return $article->refresh()->load(['category', 'content', 'tags']);
        });
    }

    public function delete(Article $article): void
    {
        if ($article->status !== CmsPublishStatus::Draft) {
            throw new InvalidArgumentException('仅草稿状态的资讯可删除。');
        }

        $article->delete();
    }

    public function publish(Article $article): Article
    {
        if ($article->status === CmsPublishStatus::Published) {
            throw new InvalidArgumentException('资讯已发布，无需重复操作。');
        }

        if (blank($article->title)) {
            throw new InvalidArgumentException('请先填写资讯标题。');
        }

        $article->loadMissing('content');

        if (! $article->content instanceof ArticleContent || blank($article->content->content)) {
            throw new InvalidArgumentException('请先填写资讯正文后再发布。');
        }

        $article->update([
            'status' => CmsPublishStatus::Published,
            'published_at' => $article->published_at ?? now(),
        ]);

        return $article->refresh()->load(['category', 'content', 'tags']);
    }

    public function offline(Article $article): Article
    {
        if ($article->status !== CmsPublishStatus::Published) {
            throw new InvalidArgumentException('仅已发布资讯可下线。');
        }

        $article->update(['status' => CmsPublishStatus::Offline]);

        return $article->refresh()->load(['category', 'content', 'tags']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function articleAttributes(array $data): array
    {
        $attributes = [];

        foreach ([
            'category_id',
            'city_code',
            'title',
            'sub_title',
            'slug',
            'cover',
            'summary',
            'author',
            'source_name',
            'source_url',
            'is_top',
            'is_recommend',
            'seo_keywords',
            'seo_description',
            'extra',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $attributes[$field] = $data[$field];
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{content: ?string, content_type: CmsArticleContentType}|null
     */
    private function extractContentPayload(array $data): ?array
    {
        if (! array_key_exists('content', $data) && ! array_key_exists('content_type', $data)) {
            return null;
        }

        $contentType = $data['content_type'] ?? CmsArticleContentType::Html;

        if (! $contentType instanceof CmsArticleContentType) {
            $contentType = CmsArticleContentType::tryFrom((int) $contentType) ?? CmsArticleContentType::Html;
        }

        return [
            'content' => $data['content'] ?? null,
            'content_type' => $contentType,
        ];
    }

    /**
     * @param  array{content: ?string, content_type: CmsArticleContentType}  $payload
     */
    private function syncContent(Article $article, array $payload): void
    {
        $article->content()->updateOrCreate(
            ['article_id' => $article->id],
            $payload,
        );
    }

    /**
     * @param  array<int, mixed>|null  $tagIds
     */
    private function syncTags(Article $article, mixed $tagIds): void
    {
        if (! is_array($tagIds)) {
            return;
        }

        $article->tags()->sync(array_values(array_map(static fn (mixed $tagId): int => (int) $tagId, $tagIds)));
    }
}
