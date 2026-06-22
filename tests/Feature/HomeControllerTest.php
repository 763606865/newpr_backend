<?php

namespace Tests\Feature;

use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Rc\SchoolActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_school_home_returns_recommended_activities_by_type(): void
    {
        SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::DualSelection,
            'title' => '2026 春季双选会',
            'city_code' => '360100',
            'sort' => 10,
            'status' => RcSchoolActivityStatus::Published,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

        SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::Presentation,
            'title' => '示例科技宣讲会',
            'city_code' => '360100',
            'status' => RcSchoolActivityStatus::Published,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

        SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::JobFair,
            'title' => '春季招聘会',
            'city_code' => '360100',
            'status' => RcSchoolActivityStatus::Published,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

        $this->getJson('/cms/home/schools?city_code=360100')
            ->assertOk()
            ->assertJsonPath('data.dual_selections.0.title', '2026 春季双选会')
            ->assertJsonPath('data.presentations.0.title', '示例科技宣讲会')
            ->assertJsonPath('data.job_fairs.0.title', '春季招聘会')
            ->assertJsonPath('data.recommendation.strategy', 'guest_local')
            ->assertJsonPath('data.recommendation.applied_filters.city_code', '360100');
    }

    public function test_position_returns_tree_payload(): void
    {
        if (! Schema::hasTable('rc_positions')) {
            $this->markTestSkipped('rc_positions table is not available in current test database.');
        }

        DB::table('rc_positions')->delete();

        $parentId = DB::table('rc_positions')->insertGetId([
            'name' => 'Engineering',
            'code' => 'tech',
            'parent_id' => null,
            'sort' => 1,
            'extra' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rc_positions')->insert([
            'name' => 'Backend Developer',
            'code' => 'backend-dev',
            'parent_id' => $parentId,
            'sort' => 1,
            'extra' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/cms/home/rc/positions');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'code',
                'data' => [
                    'positions',
                ],
                'meta' => [
                    'timestamp',
                    'response_time',
                ],
            ])
            ->assertJsonPath('data.positions.0.name', 'Engineering')
            ->assertJsonPath('data.positions.0.children.0.name', 'Backend Developer');
    }

    public function test_industry_returns_tree_payload(): void
    {
        if (! Schema::hasTable('rc_industries')) {
            $this->markTestSkipped('rc_industries table is not available in current test database.');
        }

        DB::table('rc_industries')->delete();

        $parentId = DB::table('rc_industries')->insertGetId([
            'name' => 'Internet/IT',
            'code' => 'it',
            'parent_id' => null,
            'sort' => 1,
            'extra' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rc_industries')->insert([
            'name' => 'E-commerce',
            'code' => 'ecommerce',
            'parent_id' => $parentId,
            'sort' => 1,
            'extra' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/cms/home/rc/industries');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'code',
                'data' => [
                    'industries',
                ],
                'meta' => [
                    'timestamp',
                    'response_time',
                ],
            ])
            ->assertJsonPath('data.industries.0.name', 'Internet/IT')
            ->assertJsonPath('data.industries.0.children.0.name', 'E-commerce');
    }
}
