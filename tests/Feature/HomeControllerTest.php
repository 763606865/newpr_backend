<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
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
