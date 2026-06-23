<?php

namespace App\Services;

use App\Models\Cms\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CmsArticleService extends Service
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Article>
     */
    public function paginate(int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = Article::query()
            ->published()
            ->with(['category', 'school'])
            ->orderByDesc('is_top')
            ->orderByDesc('is_recommend')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if (filled($filters['city_code'] ?? null)) {
            $query->forCity((string) $filters['city_code']);
        }

        if (filled($filters['school_code'] ?? null)) {
            $query->forSchool((string) $filters['school_code']);
        }

        if (filled($filters['category_id'] ?? null)) {
            $query->forCategory((int) $filters['category_id']);
        }

        if (filled($filters['category_slug'] ?? null)) {
            $query->forCategorySlug((string) $filters['category_slug']);
        }

        $tagIds = $filters['tag_ids'] ?? [];

        if (is_array($tagIds) && $tagIds !== []) {
            $query->withArticleTags($tagIds, (bool) ($filters['tags_match_all'] ?? true));
        }

        if (filled($filters['keyword'] ?? null)) {
            $keyword = (string) $filters['keyword'];
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('title', 'like', "%{$keyword}%")
                    ->orWhere('summary', 'like', "%{$keyword}%");
            });
        }

        if (array_key_exists('is_recommend', $filters) && $filters['is_recommend'] !== null) {
            $query->where('is_recommend', (bool) $filters['is_recommend']);
        }

        return $query->paginate($perPage);
    }

    public function findPublished(int $articleId, ?string $cityCode = null): ?Article
    {
        $query = Article::query()
            ->published()
            ->with(['category', 'content', 'tags', 'school'])
            ->whereKey($articleId);

        if ($cityCode !== null) {
            $query->forCity($cityCode);
        }

        $article = $query->first();

        if ($article instanceof Article) {
            $article->increment('view_count');
        }

        return $article;
    }
}
