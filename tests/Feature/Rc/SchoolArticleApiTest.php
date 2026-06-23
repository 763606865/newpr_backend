<?php

namespace Tests\Feature\Rc;

use App\Enums\CmsArticleContentType;
use App\Enums\CmsPublishStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Cms\Article;
use App\Models\Cms\ArticleContent;
use App\Models\Rc\UserIdentity;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolArticleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_campus_manager_can_manage_school_articles(): void
    {
        $school = $this->createSchool();
        $user = User::factory()->create();
        $this->createCampusManagerIdentity($user, $school);

        $createResponse = $this->actingAs($user, 'rc')
            ->postJson('/rc/schools/articles', [
                'title' => '2026 就业政策解读',
                'summary' => '摘要内容',
                'content' => '<p>正文内容</p>',
                'content_type' => CmsArticleContentType::Html->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.article.title', '2026 就业政策解读')
            ->assertJsonPath('data.article.status', CmsPublishStatus::Draft->value)
            ->assertJsonPath('data.article.school_code', $school->school_code);

        $articleId = (int) $createResponse->json('data.article.id');

        $this->actingAs($user, 'rc')
            ->getJson('/rc/schools/articles')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $articleId);

        $this->actingAs($user, 'rc')
            ->putJson("/rc/schools/articles/{$articleId}", [
                'summary' => '更新后的摘要',
            ])
            ->assertOk()
            ->assertJsonPath('data.article.summary', '更新后的摘要');

        $this->actingAs($user, 'rc')
            ->postJson("/rc/schools/articles/{$articleId}/publish")
            ->assertOk()
            ->assertJsonPath('data.article.status', CmsPublishStatus::Published->value);

        $this->getJson("/cms/articles/{$articleId}")
            ->assertOk()
            ->assertJsonPath('data.title', '2026 就业政策解读');

        $this->actingAs($user, 'rc')
            ->postJson("/rc/schools/articles/{$articleId}/offline")
            ->assertOk()
            ->assertJsonPath('data.article.status', CmsPublishStatus::Offline->value);

        $this->getJson("/cms/articles/{$articleId}")
            ->assertNotFound();
    }

    public function test_published_article_cannot_be_updated(): void
    {
        $school = $this->createSchool();
        $user = User::factory()->create();
        $this->createCampusManagerIdentity($user, $school);

        $article = Article::query()->create([
            'school_code' => $school->school_code,
            'title' => '已发布资讯',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        ArticleContent::query()->create([
            'article_id' => $article->id,
            'content' => '<p>正文</p>',
            'content_type' => CmsArticleContentType::Html,
        ]);

        $this->actingAs($user, 'rc')
            ->putJson("/rc/schools/articles/{$article->id}", [
                'title' => '尝试修改',
            ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '已发布资讯请先下线后再编辑。');
    }

    public function test_only_draft_article_can_be_deleted(): void
    {
        $school = $this->createSchool();
        $user = User::factory()->create();
        $this->createCampusManagerIdentity($user, $school);

        $draft = Article::query()->create([
            'school_code' => $school->school_code,
            'title' => '草稿资讯',
            'status' => CmsPublishStatus::Draft,
        ]);

        $published = Article::query()->create([
            'school_code' => $school->school_code,
            'title' => '已发布资讯',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($user, 'rc')
            ->deleteJson("/rc/schools/articles/{$draft->id}")
            ->assertOk();

        $this->actingAs($user, 'rc')
            ->deleteJson("/rc/schools/articles/{$published->id}")
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '仅草稿状态的资讯可删除。');
    }

    private function createSchool(): School
    {
        return School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);
    }

    private function createCampusManagerIdentity(User $user, School $school): UserIdentity
    {
        return UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::CampusManager,
            'identity_name' => RcIdentityType::CampusManager->getLabel(),
            'organization_type' => 'school',
            'organization_id' => $school->id,
            'organization_name' => $school->name,
            'job_title' => '就业办主任',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);
    }
}
