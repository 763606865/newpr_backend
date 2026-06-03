<?php

namespace Tests\Feature\Rc;

use App\Models\Area;
use App\Models\Rc\Industry;
use App\Models\Rc\Position;
use App\Models\User;
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

    public function test_index_returns_areas_industries_and_positions(): void
    {
        $user = User::factory()->create();
        $this->seedMetaData();

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/meta');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.areas.0.name', 'Province A')
            ->assertJsonPath('data.areas.0.children.0.name', 'City A')
            ->assertJsonPath('data.industries.0.name', 'Internet/IT')
            ->assertJsonPath('data.positions.0.name', 'Engineering');

        $this->assertSame([
            '000001' => 'Province A',
            '000001001' => 'City A',
        ], app(MetaService::class)->getAreaNameMap());
    }

    public function test_areas_endpoint_returns_area_tree(): void
    {
        $user = User::factory()->create();
        $this->seedAreas();

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/meta/areas');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.areas.0.name', 'Province A')
            ->assertJsonPath('data.areas.0.children.0.name', 'City A');
    }

    public function test_industries_endpoint_returns_tree(): void
    {
        $user = User::factory()->create();
        $this->seedIndustries();

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/meta/industries');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.industries.0.name', 'Internet/IT')
            ->assertJsonPath('data.industries.0.children.0.name', 'E-commerce');
    }

    public function test_positions_endpoint_returns_tree(): void
    {
        $user = User::factory()->create();
        $this->seedPositions();

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/meta/positions');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.positions.0.name', 'Engineering')
            ->assertJsonPath('data.positions.0.children.0.name', 'Backend Developer');
    }

    private function seedMetaData(): void
    {
        $this->seedAreas();
        $this->seedIndustries();
        $this->seedPositions();
    }

    private function seedAreas(): void
    {
        Area::query()->delete();

        Area::query()->insert([
            [
                'name' => 'Province A',
                'code' => '000001',
                'parent_code' => null,
                'level' => 1,
                'type' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'City A',
                'code' => '000001001',
                'parent_code' => '000001',
                'level' => 2,
                'type' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedIndustries(): void
    {
        Industry::query()->delete();

        $parentId = Industry::query()->insertGetId([
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
            'parent_id' => $parentId,
            'sort' => 1,
            'extra' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPositions(): void
    {
        Position::query()->delete();

        $parentId = Position::query()->insertGetId([
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
            'parent_id' => $parentId,
            'sort' => 1,
            'extra' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
