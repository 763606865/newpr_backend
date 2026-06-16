<?php

namespace App\Filament\Resources\Cms\Articles\Concerns;

use App\Enums\CmsArticleContentType;
use App\Models\Cms\ArticleContent;

trait InteractsWithArticleContentForm
{
    /**
     * @var array{content: ?string, content_type: CmsArticleContentType}
     */
    protected array $pendingArticleContent = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mergeArticleContentIntoFormData(array $data, ?ArticleContent $content): array
    {
        $contentType = $content?->content_type ?? CmsArticleContentType::Html;
        $body = $content?->content;

        return array_merge($data, [
            'content_type' => $contentType,
            'body_html' => $contentType === CmsArticleContentType::Html ? $body : null,
            'body_markdown' => $contentType === CmsArticleContentType::Markdown ? $body : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractArticleContentFromFormData(array $data): array
    {
        $contentType = $data['content_type'] ?? CmsArticleContentType::Html;

        if (! $contentType instanceof CmsArticleContentType) {
            $contentType = CmsArticleContentType::tryFrom((int) $contentType) ?? CmsArticleContentType::Html;
        }

        $content = $contentType === CmsArticleContentType::Markdown
          ? ($data['body_markdown'] ?? null)
          : ($data['body_html'] ?? null);

        return [
            'content' => $content,
            'content_type' => $contentType,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function removeArticleContentFromFormData(array $data): array
    {
        unset($data['content_type'], $data['body_html'], $data['body_markdown']);

        return $data;
    }

    protected function persistArticleContent(): void
    {
        if ($this->pendingArticleContent === []) {
            return;
        }

        $this->getRecord()->content()->updateOrCreate(
            ['article_id' => $this->getRecord()->id],
            $this->pendingArticleContent,
        );
    }
}
