<?php

namespace Tests\Unit\Models\Cms;

use App\Enums\CmsStatus;
use App\Models\Cms\Menu;
use Tests\TestCase;

class MenuTest extends TestCase
{
    public function test_shown_scope_filters_visible_menu_records(): void
    {
        $query = Menu::query()->shown();

        $this->assertStringContainsString('"cms_menus"."is_show" = ?', $query->toSql());
        $this->assertSame([true], $query->getBindings());
    }

    public function test_enabled_scope_filters_enabled_status_records(): void
    {
        $query = Menu::query()->enabled();

        $this->assertStringContainsString('"cms_menus"."status" = ?', $query->toSql());
        $this->assertSame([CmsStatus::Enabled->value], $query->getBindings());
    }
}
