<?php

namespace Tests\Unit\Models\Cms;

use App\Enums\CmsMenuAudienceType;
use App\Enums\RcIdentityType;
use App\Models\Cms\Menu;
use App\Models\Cms\MenuIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_without_identities_is_visible_to_all_audiences(): void
    {
        $menu = Menu::query()->create([
            'name' => '公开菜单',
        ]);

        $this->assertTrue($menu->isVisibleToAudience(CmsMenuAudienceType::Guest));
        $this->assertTrue($menu->isVisibleToAudience(CmsMenuAudienceType::Recruiter));
    }

    public function test_menu_with_identities_is_only_visible_to_allowed_audiences(): void
    {
        $menu = Menu::query()->create([
            'name' => '校园菜单',
        ]);

        MenuIdentity::query()->create([
            'menu_id' => $menu->id,
            'identity_type' => CmsMenuAudienceType::JobSeeker,
        ]);

        MenuIdentity::query()->create([
            'menu_id' => $menu->id,
            'identity_type' => CmsMenuAudienceType::Recruiter,
        ]);

        $menu->load('menuIdentities');

        $this->assertFalse($menu->isVisibleToAudience(CmsMenuAudienceType::Guest));
        $this->assertTrue($menu->isVisibleToAudience(CmsMenuAudienceType::JobSeeker));
        $this->assertTrue($menu->isVisibleToAudience(CmsMenuAudienceType::Recruiter));
        $this->assertFalse($menu->isVisibleToAudience(CmsMenuAudienceType::CampusManager));
    }

    public function test_visible_to_audience_scope_filters_menus_by_identity(): void
    {
        $publicMenu = Menu::query()->create(['name' => '公开菜单']);
        $seekerMenu = Menu::query()->create(['name' => '求职者菜单']);

        MenuIdentity::query()->create([
            'menu_id' => $seekerMenu->id,
            'identity_type' => CmsMenuAudienceType::JobSeeker,
        ]);

        $titles = Menu::query()
            ->visibleToAudience(CmsMenuAudienceType::JobSeeker)
            ->orderBy('id')
            ->pluck('name')
            ->all();

        $this->assertSame(['公开菜单', '求职者菜单'], $titles);

        $guestTitles = Menu::query()
            ->visibleToAudience(CmsMenuAudienceType::Guest)
            ->pluck('name')
            ->all();

        $this->assertSame(['公开菜单'], $guestTitles);
    }

    public function test_for_identity_scope_filters_menus_by_rc_identity_type(): void
    {
        $publicMenu = Menu::query()->create(['name' => '公开菜单']);
        $recruiterMenu = Menu::query()->create(['name' => '招聘方菜单']);

        MenuIdentity::query()->create([
            'menu_id' => $recruiterMenu->id,
            'identity_type' => CmsMenuAudienceType::Recruiter,
        ]);

        $recruiterTitles = Menu::query()
            ->forIdentity(RcIdentityType::Recruiter)
            ->orderBy('id')
            ->pluck('name')
            ->all();

        $this->assertSame(['公开菜单', '招聘方菜单'], $recruiterTitles);

        $guestTitles = Menu::query()
            ->forIdentity(null)
            ->pluck('name')
            ->all();

        $this->assertSame(['公开菜单'], $guestTitles);
    }
}
