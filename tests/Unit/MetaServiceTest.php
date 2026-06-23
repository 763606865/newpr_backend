<?php

namespace Tests\Unit;

use App\Models\Area;
use App\Models\Cms\ArticleCategory;
use App\Models\Cms\ArticleTag;
use App\Models\Major;
use App\Models\Rc\Industry;
use App\Models\Rc\Position;
use App\Models\School;
use App\Services\MetaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        app(MetaService::class)->forgetAll();
    }

    public function test_it_resolves_city_full_name_from_city_code(): void
    {
        Area::query()->delete();
        Area::query()->insert([
            [
                'name' => '江西省',
                'code' => '360000',
                'parent_code' => '000000',
                'level' => 1,
                'type' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '南昌市',
                'code' => '360100',
                'parent_code' => '360000',
                'level' => 2,
                'type' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        app(MetaService::class)->forgetAreas();

        $this->assertSame('中国江西省南昌市', app(MetaService::class)->getCityFullName('360100'));
        $this->assertNull(app(MetaService::class)->getCityFullName('999999'));
    }

    public function test_it_builds_cached_areas_tree_and_code_name_map(): void
    {
        Area::query()->create([
            'name' => 'Province A',
            'code' => '000001',
            'parent_code' => null,
            'level' => 1,
            'type' => null,
        ]);
        Area::query()->create([
            'name' => 'City A',
            'code' => '000001001',
            'parent_code' => '000001',
            'level' => 2,
            'type' => null,
        ]);

        $service = app(MetaService::class);

        $this->assertSame('Province A', $service->getAreasTree()[0]['name']);
        $this->assertSame('City A', $service->getAreasTree()[0]['children'][0]['name']);
        $this->assertSame([
            '000001' => 'Province A',
            '000001001' => 'City A',
        ], $service->getAreaNameMap());
    }

    public function test_area_observer_invalidates_both_tree_and_map_cache(): void
    {
        $service = app(MetaService::class);

        Area::query()->create([
            'name' => 'Province A',
            'code' => '000001',
            'parent_code' => null,
            'level' => 1,
            'type' => null,
        ]);

        $this->assertSame(['000001' => 'Province A'], $service->getAreaNameMap());

        Area::query()->create([
            'name' => 'City A',
            'code' => '000001001',
            'parent_code' => '000001',
            'level' => 2,
            'type' => null,
        ]);

        $this->assertSame('City A', $service->getAreasTree()[0]['children'][0]['name']);
        $this->assertSame('City A', $service->getAreaNameMap()['000001001']);
    }

    public function test_it_builds_cached_industry_and_position_trees(): void
    {
        $industryParentId = Industry::query()->insertGetId([
            'name' => 'Internet/IT',
            'code' => 'it',
            'parent_id' => null,
            'sort' => 1,
            'extra' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Industry::query()->insert([
            'name' => 'E-commerce',
            'code' => 'ecommerce',
            'parent_id' => $industryParentId,
            'sort' => 1,
            'extra' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $positionParentId = Position::query()->insertGetId([
            'name' => 'Engineering',
            'code' => 'engineering',
            'parent_id' => null,
            'sort' => 1,
            'extra' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Position::query()->insert([
            'name' => 'Backend Developer',
            'code' => 'backend-dev',
            'parent_id' => $positionParentId,
            'sort' => 1,
            'extra' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(MetaService::class);

        $this->assertSame('Internet/IT', $service->getIndustriesTree()[0]['name']);
        $this->assertSame('E-commerce', $service->getIndustriesTree()[0]['children'][0]['name']);
        $this->assertSame('Engineering', $service->getPositionsTree()[0]['name']);
        $this->assertSame('Backend Developer', $service->getPositionsTree()[0]['children'][0]['name']);
    }

    public function test_it_builds_cached_majors_tree_and_invalidates_on_update(): void
    {
        Major::query()->create([
            'full_code' => '55',
            'name' => '装备制造大类',
            'level' => 1,
            'parent_code' => null,
            'type' => '中职',
            'sort' => 1,
        ]);

        $service = app(MetaService::class);

        $this->assertSame('装备制造大类', $service->getMajorsTree()[0]['name']);

        Major::query()->create([
            'full_code' => '5501',
            'name' => '机械设计制造类',
            'level' => 2,
            'parent_code' => '55',
            'type' => '中职',
            'sort' => 1,
        ]);

        $this->assertSame('机械设计制造类', $service->getMajorsTree()[0]['children'][0]['name']);
    }

    public function test_majors_tree_excludes_disabled_records(): void
    {
        Major::query()->create([
            'full_code' => '99',
            'name' => '禁用大类',
            'level' => 1,
            'parent_code' => null,
            'type' => '中职',
            'status' => 0,
        ]);

        Major::query()->create([
            'full_code' => '55',
            'name' => '装备制造大类',
            'level' => 1,
            'parent_code' => null,
            'type' => '中职',
            'status' => 1,
        ]);

        $tree = app(MetaService::class)->getMajorsTree();

        $this->assertCount(1, $tree);
        $this->assertSame('装备制造大类', $tree[0]['name']);
    }

    public function test_it_builds_cached_flat_school_list(): void
    {
        School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
            'province' => '北京市',
            'city' => '北京市',
            'type' => '本科',
        ]);
        School::query()->create([
            'school_code' => '4131010003',
            'name' => '复旦大学',
            'province' => '上海市',
            'city' => '上海市',
            'type' => '本科',
        ]);

        $schools = app(MetaService::class)->getSchools();

        $this->assertCount(2, $schools['schools']);
        $this->assertSame('4111010001', $schools['schools'][0]['value']);
        $this->assertSame('北京大学', $schools['schools'][0]['label']);
        $this->assertSame('4131010003', $schools['schools'][1]['value']);
        $this->assertSame('复旦大学', $schools['schools'][1]['label']);
    }

    public function test_it_builds_cached_article_categories_tree_and_tags_list(): void
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

        $service = app(MetaService::class);

        $this->assertSame('校园资讯', $service->getArticleCategoriesTree()[0]['name']);
        $this->assertSame('就业动态', $service->getArticleCategoriesTree()[0]['children'][0]['name']);
        $this->assertSame('校招', $service->getArticleTagsList()[0]['name']);

        ArticleTag::query()->create([
            'name' => '政策解读',
            'slug' => 'policy',
            'sort' => 2,
        ]);

        $this->assertSame('政策解读', $service->getArticleTagsList()[1]['name']);
    }

    public function test_article_meta_observer_invalidates_cache(): void
    {
        $service = app(MetaService::class);

        ArticleCategory::query()->create([
            'name' => '校园资讯',
            'slug' => 'campus-news',
        ]);

        $this->assertSame('校园资讯', $service->getArticleCategoriesTree()[0]['name']);

        ArticleCategory::query()->create([
            'name' => '新闻时事',
            'slug' => 'news',
        ]);

        $this->assertSame('新闻时事', $service->getArticleCategoriesTree()[1]['name']);
    }
}
