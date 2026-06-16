<?php

namespace Tests\Unit\Models\Cms;

use App\Models\Cms\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuParentIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_parent_id_defaults_to_zero_for_top_level(): void
    {
        $menu = Menu::query()->create([
            'name' => '首页',
            'code' => 'home',
        ]);

        $this->assertSame(0, $menu->parent_id);
        $this->assertSame(0, $menu->fresh()->getRawOriginal('parent_id'));
    }
}
