<?php

namespace Tests\Feature\Rc;

use Database\Seeders\RcIndustrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RcIndustrySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_industries_with_parent_child_hierarchy(): void
    {
        $this->seed(RcIndustrySeeder::class);

        $this->assertGreaterThan(0, DB::table('rc_industries')->count());

        $root = DB::table('rc_industries')->where('code', 'A')->first();
        $this->assertNotNull($root);
        $this->assertNull($root->parent_id);
        $this->assertSame(1, $root->depth);

        $child = DB::table('rc_industries')->where('code', '01')->first();
        $this->assertNotNull($child);
        $this->assertSame($root->id, $child->parent_id);
        $this->assertSame(2, $child->depth);

        $grandChild = DB::table('rc_industries')->where('code', '011')->first();
        $this->assertNotNull($grandChild);
        $this->assertSame($child->id, $grandChild->parent_id);
        $this->assertSame(3, $grandChild->depth);

        $greatGrandChild = DB::table('rc_industries')->where('code', '0111')->first();
        $this->assertNotNull($greatGrandChild);
        $this->assertSame($grandChild->id, $greatGrandChild->parent_id);
        $this->assertSame(4, $greatGrandChild->depth);
    }
}
