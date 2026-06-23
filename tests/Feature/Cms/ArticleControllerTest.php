<?php

namespace Tests\Feature\Cms;

use App\Enums\CmsArticleContentType;
use App\Enums\CmsPublishStatus;
use App\Enums\CmsStatus;
use App\Models\Cms\Article;
use App\Models\Cms\ArticleCategory;
use App\Models\Cms\ArticleContent;
use App\Models\Cms\ArticleTag;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_returns_published_articles(): void
    {
        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);
        $category = ArticleCategory::query()->create([
            'name' => '校园资讯',
            'slug' => 'campus-news',
            'status' => CmsStatus::Enabled,
        ]);

        $published = $this->createPublishedArticle($school, $category, '已发布资讯');
        Article::query()->create([
            'school_code' => $school->school_code,
            'title' => '草稿资讯',
            'status' => CmsPublishStatus::Draft,
        ]);

        $this->getJson('/cms/articles?school_code='.$school->school_code)
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $published->id)
            ->assertJsonPath('data.data.0.school_name', '北京大学')
            ->assertJsonPath('data.data.0.category.slug', 'campus-news');
    }

    public function test_show_returns_published_article_detail_and_increments_view_count(): void
    {
        $school = School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);
        $article = $this->createPublishedArticle($school, null, '详情资讯');

        $this->getJson("/cms/articles/{$article->id}")
            ->assertOk()
            ->assertJsonPath('data.title', '详情资讯')
            ->assertJsonPath('data.content', '<p>正文</p>');

        $this->assertSame(1, (int) $article->fresh()->view_count);
    }

    public function test_index_filters_by_article_tag_ids(): void
    {
        $tag = ArticleTag::query()->create([
            'name' => '就业政策',
            'slug' => 'employment-policy',
            'status' => CmsStatus::Enabled,
        ]);

        $matched = Article::query()->create([
            'title' => '匹配资讯',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);
        $matched->tags()->sync([$tag->id]);

        Article::query()->create([
            'title' => '其他资讯',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $this->getJson('/cms/articles?'.http_build_query([
            'tag_ids' => [$tag->id],
        ]))
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $matched->id);
    }

    private function createPublishedArticle(School $school, ?ArticleCategory $category, string $title): Article
    {
        $article = Article::query()->create([
            'category_id' => $category?->id ?? 0,
            'school_code' => $school->school_code,
            'title' => $title,
            'summary' => '摘要',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        ArticleContent::query()->create([
            'article_id' => $article->id,
            'content' => '<p>正文</p>',
            'content_type' => CmsArticleContentType::Html,
        ]);

        return $article;
    }
}
