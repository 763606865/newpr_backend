<?php

namespace Tests\Feature\Filament;

use App\Enums\CmsArticleContentType;
use App\Enums\CmsPublishStatus;
use App\Enums\CmsStatus;
use App\Filament\Resources\Cms\Articles\Pages\CreateArticle;
use App\Filament\Resources\Cms\Articles\Pages\EditArticle;
use App\Models\Cms\Article;
use App\Models\Cms\ArticleCategory;
use App\Models\Cms\ArticleContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class ArticleContentFormTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    private function articlePermissions(): array
    {
        return [
            'ViewAny:Article',
            'View:Article',
            'Create:Article',
            'Update:Article',
            'Delete:Article',
            'DeleteAny:Article',
            'Restore:Article',
            'ForceDelete:Article',
            'ForceDeleteAny:Article',
            'RestoreAny:Article',
        ];
    }

    public function test_create_article_saves_html_content(): void
    {
        $this->actingAsFilamentAdmin($this->articlePermissions());
        $category = $this->createArticleCategory();

        Livewire::test(CreateArticle::class)
            ->fillForm([
                'category_id' => $category->id,
                'title' => '测试文章',
                'status' => CmsPublishStatus::Draft,
                'content_type' => CmsArticleContentType::Html,
                'body_html' => '<p>文章正文 HTML</p>',
            ])
            ->call('create')
            ->assertNotified();

        $article = Article::query()->where('title', '测试文章')->first();

        $this->assertNotNull($article);
        $this->assertDatabaseHas('cms_article_contents', [
            'article_id' => $article->id,
            'content' => '<p>文章正文 HTML</p>',
            'content_type' => CmsArticleContentType::Html->value,
        ]);
    }

    public function test_create_article_saves_markdown_content(): void
    {
        $this->actingAsFilamentAdmin($this->articlePermissions());
        $category = $this->createArticleCategory();

        Livewire::test(CreateArticle::class)
            ->fillForm([
                'category_id' => $category->id,
                'title' => 'Markdown 文章',
                'status' => CmsPublishStatus::Draft,
                'content_type' => CmsArticleContentType::Markdown,
                'body_markdown' => '# 标题正文',
            ])
            ->call('create')
            ->assertNotified();

        $article = Article::query()->where('title', 'Markdown 文章')->first();

        $this->assertNotNull($article);
        $this->assertDatabaseHas('cms_article_contents', [
            'article_id' => $article->id,
            'content' => '# 标题正文',
            'content_type' => CmsArticleContentType::Markdown->value,
        ]);
    }

    public function test_edit_article_loads_and_updates_content(): void
    {
        $this->actingAsFilamentAdmin($this->articlePermissions());
        $category = $this->createArticleCategory();
        $article = Article::query()->create([
            'category_id' => $category->id,
            'title' => '待编辑文章',
            'status' => CmsPublishStatus::Draft,
        ]);
        ArticleContent::query()->create([
            'article_id' => $article->id,
            'content' => '<p>旧正文</p>',
            'content_type' => CmsArticleContentType::Html,
        ]);

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->assertFormSet([
                'content_type' => CmsArticleContentType::Html,
                'body_html' => '<p>旧正文</p>',
            ])
            ->fillForm([
                'body_html' => '<p>新正文</p>',
            ])
            ->call('save')
            ->assertNotified();

        $this->assertDatabaseHas('cms_article_contents', [
            'article_id' => $article->id,
            'content' => '<p>新正文</p>',
            'content_type' => CmsArticleContentType::Html->value,
        ]);
    }

    private function createArticleCategory(): ArticleCategory
    {
        return ArticleCategory::query()->create([
            'name' => '测试分类',
            'status' => CmsStatus::Enabled,
        ]);
    }
}
