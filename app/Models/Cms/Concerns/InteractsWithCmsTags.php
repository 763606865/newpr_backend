<?php

namespace App\Models\Cms\Concerns;

use App\Models\Cms\Tag;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait InteractsWithCmsTags
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'cms_tag_relations', 'taggable_id', 'tag_id')
            ->withTimestamps();
    }

    /**
     * 按标签筛选（关联 cms_tag_relations）。
     *
     * @param  array<int|string|array{id?: int, category?: string, name?: string}>  $tags
     */
    #[Scope]
    protected function withTags(Builder $query, array $tags, bool $matchAll = true): void
    {
        if ($tags === []) {
            return;
        }

        $tagIds = $this->resolveTagIdsForScope($tags);

        if ($tagIds === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        if ($matchAll) {
            $query->whereHas('tags', function (Builder $tagQuery) use ($tagIds): void {
                $tagQuery->whereIn($tagQuery->getModel()->getTable().'.id', $tagIds);
            }, '=', count($tagIds));

            return;
        }

        $query->whereHas('tags', function (Builder $tagQuery) use ($tagIds): void {
            $tagQuery->whereIn($tagQuery->getModel()->getTable().'.id', $tagIds);
        });
    }

    /**
     * @param  array<int|string|array{id?: int, category?: string, name?: string}>  $tags
     * @return array<int, int>
     */
    private function resolveTagIdsForScope(array $tags): array
    {
        $tagIds = [];

        foreach ($tags as $tag) {
            if (is_int($tag)) {
                $tagIds[] = $tag;

                continue;
            }

            if (is_string($tag)) {
                $resolvedId = Tag::query()
                    ->where('name', $tag)
                    ->value('id');

                if ($resolvedId !== null) {
                    $tagIds[] = (int) $resolvedId;
                }

                continue;
            }

            if (! is_array($tag)) {
                continue;
            }

            if (filled($tag['id'] ?? null)) {
                $tagIds[] = (int) $tag['id'];

                continue;
            }

            $name = $tag['name'] ?? null;

            if (blank($name)) {
                continue;
            }

            $tagQuery = Tag::query()->where('name', $name);

            if (filled($tag['category'] ?? null)) {
                $tagQuery->where('category', $tag['category']);
            }

            $resolvedId = $tagQuery->value('id');

            if ($resolvedId !== null) {
                $tagIds[] = (int) $resolvedId;
            }
        }

        return array_values(array_unique($tagIds));
    }
}
