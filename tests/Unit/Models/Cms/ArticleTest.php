<?php

namespace Tests\Unit\Models\Cms;

use App\Enums\CmsPublishStatus;
use App\Models\Cms\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_city_code_can_be_null_for_global_scope(): void
    {
        $article = Article::query()->create([
            'title' => '全站文章',
            'status' => CmsPublishStatus::Draft,
        ]);

        $this->assertNull($article->city_code);
    }
}
