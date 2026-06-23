<?php

namespace Tests\Feature\Cms;

use App\Models\Cms\ArticleCategory;
use App\Models\Cms\ArticleTag;
use App\Models\Cms\Tag;
use App\Models\Major;
use App\Services\MetaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        app(MetaService::class)->forgetAll();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_returns_areas_and_majors(): void
    {
        $this->seedMajors();

        $response = $this->getJson('/cms/meta');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.majors.0.name', '装备制造大类')
            ->assertJsonPath('data.majors.0.children.0.name', '机械设计制造类')
            ->assertJsonPath('data.major_levels.0.value', 1);

        $educationTypes = collect($response->json('data.major_education_types'))->pluck('value')->all();

        $this->assertContains('中职', $educationTypes);
    }

    public function test_index_returns_tags_grouped_by_category(): void
    {
        $this->seedTags();

        $response = $this->getJson('/cms/meta');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.tags.0.category', 'rc')
            ->assertJsonPath('data.tags.0.category_label', '招聘类型 (rc)')
            ->assertJsonPath('data.tags.0.children.0.name', '校招')
            ->assertJsonPath('data.tags.0.children.0.slug', 'campus-recruitment')
            ->assertJsonPath('data.tag_categories.0.value', 'rc');
    }

    public function test_index_returns_announcement_publisher_type_dictionary(): void
    {
        $response = $this->getJson('/cms/meta');

        $response
            ->assertOk()
            ->assertJsonPath('data.announcement_publisher_types.0.value', 0)
            ->assertJsonPath('data.announcement_publisher_types.0.label', '系统');
    }

    public function test_index_returns_article_categories_and_tags(): void
    {
        $this->seedArticleMeta();

        $response = $this->getJson('/cms/meta');

        $response
            ->assertOk()
            ->assertJsonPath('data.article_categories.0.name', '校园资讯')
            ->assertJsonPath('data.article_categories.0.children.0.name', '就业动态')
            ->assertJsonPath('data.article_tags.0.name', '校招')
            ->assertJsonPath('data.article_tags.0.slug', 'campus-recruitment');
    }

    public function test_articles_endpoint_returns_article_meta(): void
    {
        $this->seedArticleMeta();

        $response = $this->getJson('/cms/meta/articles');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.article_categories.0.name', '校园资讯')
            ->assertJsonPath('data.article_tags.0.name', '校招');
    }

    public function test_tags_endpoint_returns_tag_tree(): void
    {
        $this->seedTags();

        $response = $this->getJson('/cms/meta/tags');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.tags.0.children.0.name', '校招')
            ->assertJsonPath('data.tag_categories.0.value', 'rc');
    }

    public function test_majors_endpoint_returns_major_tree(): void
    {
        $this->seedMajors();

        $response = $this->getJson('/cms/meta/majors');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.majors.0.full_code', '55')
            ->assertJsonPath('data.majors.0.children.0.children.0.name', '数控技术应用');
    }

    private function seedMajors(): void
    {
        Major::query()->create([
            'full_code' => '55',
            'name' => '装备制造大类',
            'level' => 1,
            'parent_code' => null,
            'type' => '中职',
            'sort' => 1,
        ]);

        Major::query()->create([
            'full_code' => '5501',
            'name' => '机械设计制造类',
            'level' => 2,
            'parent_code' => '55',
            'type' => '中职',
            'sort' => 1,
        ]);

        Major::query()->create([
            'full_code' => '550101',
            'name' => '数控技术应用',
            'level' => 3,
            'parent_code' => '5501',
            'type' => '中职',
            'sort' => 1,
        ]);
    }

    private function seedTags(): void
    {
        Tag::query()->create([
            'category' => 'rc',
            'name' => '校招',
            'slug' => 'campus-recruitment',
            'status' => 1,
            'sort' => 1,
        ]);

        Tag::query()->create([
            'category' => 'school_exam',
            'name' => '高考',
            'slug' => 'gaokao',
            'status' => 1,
            'sort' => 1,
        ]);
    }

    private function seedArticleMeta(): void
    {
        $parent = ArticleCategory::query()->create([
            'name' => '校园资讯',
            'slug' => 'campus-news',
            'sort' => 1,
        ]);

        ArticleCategory::query()->create([
            'parent_id' => $parent->id,
            'name' => '就业动态',
            'slug' => 'employment-news',
            'sort' => 1,
        ]);

        ArticleTag::query()->create([
            'name' => '校招',
            'slug' => 'campus-recruitment',
            'sort' => 1,
        ]);

        ArticleTag::query()->create([
            'name' => '政策解读',
            'slug' => 'policy',
            'sort' => 2,
        ]);

        app(MetaService::class)->forgetArticleCategories();
        app(MetaService::class)->forgetArticleTags();
    }
}
