<?php

namespace Tests\Unit\Resources\Cms;

use App\Enums\CmsLinkType;
use App\Enums\CmsOpenTarget;
use App\Enums\CmsStatus;
use App\Models\Cms\Menu;
use App\Resources\Cms\CmsMenuCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CmsMenuCollectionTest extends TestCase
{
    public function test_it_transforms_flat_menu_collection_to_tree(): void
    {
        $menus = new Collection([
            $this->makeMenu(id: 1, parentId: 0, sort: 2, name: 'Root B'),
            $this->makeMenu(id: 3, parentId: 1, sort: 1, name: 'Child B-1'),
            $this->makeMenu(id: 4, parentId: 3, sort: 1, name: 'Grandchild B-1-1'),
            $this->makeMenu(id: 2, parentId: 0, sort: 1, name: 'Root A'),
        ]);

        $payload = (new CmsMenuCollection($menus))->toArray(Request::create('/home', 'GET'));

        $this->assertCount(2, $payload);
        $this->assertSame(2, $payload[0]['id']);
        $this->assertSame(1, $payload[1]['id']);
        $this->assertCount(1, $payload[1]['children']);
        $this->assertSame(3, $payload[1]['children'][0]['id']);
        $this->assertCount(1, $payload[1]['children'][0]['children']);
        $this->assertSame(4, $payload[1]['children'][0]['children'][0]['id']);
    }

    private function makeMenu(int $id, int $parentId, int $sort, string $name): Menu
    {
        $menu = new Menu;
        $menu->id = $id;
        $menu->parent_id = $parentId;
        $menu->name = $name;
        $menu->code = 'menu-'.$id;
        $menu->link_type = CmsLinkType::Internal;
        $menu->link_url = '/'.$id;
        $menu->icon = 'icon-'.$id;
        $menu->image = null;
        $menu->target = CmsOpenTarget::Self;
        $menu->is_show = true;
        $menu->status = CmsStatus::Enabled;
        $menu->sort = $sort;
        $menu->extra = [];

        return $menu;
    }
}
