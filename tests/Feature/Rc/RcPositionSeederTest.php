<?php

namespace Tests\Feature\Rc;

use Database\Seeders\RcPositionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RcPositionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_positions_with_parent_child_hierarchy(): void
    {
        $this->seed(RcPositionSeeder::class);

        $this->assertGreaterThan(0, DB::table('rc_positions')->count());

        $root = DB::table('rc_positions')->where('code', '117a91T')->first();
        $this->assertNotNull($root);
        $this->assertNull($root->parent_id);
        $this->assertSame(1, $root->depth);

        $child = DB::table('rc_positions')->where('code', '115oBXHb')->first();
        $this->assertNotNull($child);
        $this->assertSame($root->id, $child->parent_id);
        $this->assertSame(2, $child->depth);

        $grandChild = DB::table('rc_positions')->where('code', '114dFk6e')->first();
        $this->assertNotNull($grandChild);
        $this->assertSame($child->id, $grandChild->parent_id);
        $this->assertSame(3, $grandChild->depth);
    }
}
