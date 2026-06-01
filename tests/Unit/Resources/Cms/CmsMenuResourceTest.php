<?php

namespace Tests\Unit\Resources\Cms;

use App\Enums\CmsLinkType;
use App\Enums\CmsOpenTarget;
use App\Enums\CmsStatus;
use App\Models\Cms\Menu;
use App\Resources\Cms\CmsMenuResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class CmsMenuResourceTest extends TestCase
{
    public function test_it_transforms_single_menu_payload(): void
    {
        $menu = $this->makeMenu(id: 10, parentId: 0, sort: 1, name: 'Root');

        $payload = (new CmsMenuResource($menu))->toArray(Request::create('/home', 'GET'));

        $this->assertSame(10, $payload['id']);
        $this->assertSame(0, $payload['parent_id']);
        $this->assertSame('Root', $payload['name']);
        $this->assertSame(CmsLinkType::Internal->value, $payload['link_type']);
        $this->assertSame(CmsOpenTarget::Self->value, $payload['target']);
        $this->assertSame(CmsStatus::Enabled->value, $payload['status']);
        $this->assertArrayNotHasKey('children', $payload);
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
