<?php

namespace Tests\Unit;

use App\Models\Area;
use App\Models\Rc\Industry;
use App\Models\Rc\Position;
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
}
