<?php

namespace Tests\Unit\Services;

use App\Enums\CmsMenuAudienceType;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Cms\Menu;
use App\Models\Cms\MenuIdentity;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Services\CmsMenuAudienceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CmsMenuAudienceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_audience_returns_guest_for_unauthenticated_request(): void
    {
        $audience = CmsMenuAudienceService::make()->resolveAudience(Request::create('/cms/home', 'GET'));

        $this->assertSame(CmsMenuAudienceType::Guest, $audience);
    }

    public function test_resolve_audience_uses_default_identity_when_token_has_no_responsible(): void
    {
        $user = User::factory()->create();

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => RcIdentityType::Recruiter->getLabel(),
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $this->actingAs($user, 'rc');

        $request = Request::create('/cms/home', 'GET');
        $request->setUserResolver(fn (): ?User => auth('rc')->user());

        $audience = CmsMenuAudienceService::make()->resolveAudience($request);

        $this->assertSame(CmsMenuAudienceType::Recruiter, $audience);
    }

    public function test_is_route_accessible_allows_unconfigured_menu_code(): void
    {
        $accessible = CmsMenuAudienceService::make()->isRouteAccessible(
            'home.position',
            CmsMenuAudienceType::Guest,
        );

        $this->assertTrue($accessible);
    }

    public function test_is_route_accessible_respects_menu_identity_restrictions(): void
    {
        $menu = Menu::query()->create([
            'name' => '中测校园',
            'code' => 'home.school',
        ]);

        MenuIdentity::query()->create([
            'menu_id' => $menu->id,
            'identity_type' => CmsMenuAudienceType::JobSeeker,
        ]);

        MenuIdentity::query()->create([
            'menu_id' => $menu->id,
            'identity_type' => CmsMenuAudienceType::Recruiter,
        ]);

        $service = CmsMenuAudienceService::make();

        $this->assertTrue($service->isRouteAccessible('home.school', CmsMenuAudienceType::Recruiter));
        $this->assertFalse($service->isRouteAccessible('home.school', CmsMenuAudienceType::Guest));
        $this->assertFalse($service->isRouteAccessible('home.school', CmsMenuAudienceType::CampusManager));
    }
}
