<?php

namespace Tests\Feature;

use App\Enums\CmsMenuAudienceType;
use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Cms\Menu;
use App\Models\Cms\MenuIdentity;
use App\Models\Company;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\UserIdentity;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsHomeMenuAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_home_index_returns_menus_visible_to_current_identity(): void
    {
        Menu::query()->create([
            'name' => '公开首页',
            'code' => 'home.public',
        ]);

        $seekerMenu = Menu::query()->create([
            'name' => '求职者首页',
            'code' => 'home.seeker',
        ]);

        MenuIdentity::query()->create([
            'menu_id' => $seekerMenu->id,
            'identity_type' => CmsMenuAudienceType::JobSeeker,
        ]);

        $this->getJson('/cms/home')
            ->assertOk()
            ->assertJsonPath('data.menus.0.name', '公开首页')
            ->assertJsonMissing(['name' => '求职者首页']);

        $user = User::factory()->create();
        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => RcIdentityType::JobSeeker->getLabel(),
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/cms/home')
            ->assertOk()
            ->assertJsonFragment(['name' => '公开首页'])
            ->assertJsonFragment(['name' => '求职者首页']);
    }

    public function test_home_school_route_is_forbidden_for_unauthorized_audience(): void
    {
        SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::DualSelection,
            'title' => '2026 春季双选会',
            'city_code' => '360100',
            'status' => RcSchoolActivityStatus::Published,
            'register_start_date' => now()->subDay(),
            'register_end_date' => now()->addMonth(),
            'start_time' => now()->addMonth(),
        ]);

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

        $this->getJson('/cms/home/schools?city_code=360100')
            ->assertForbidden();

        $recruiter = User::factory()->create();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        UserIdentity::query()->create([
            'user_id' => $recruiter->id,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => RcIdentityType::Recruiter->getLabel(),
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $this->actingAs($recruiter, 'rc')
            ->getJson('/cms/home/schools?city_code=360100')
            ->assertOk()
            ->assertJsonPath('data.dual_selections.0.title', '2026 春季双选会');
    }

    public function test_home_school_route_is_forbidden_for_campus_manager(): void
    {
        Menu::query()->create([
            'name' => '中测校园',
            'code' => 'home.school',
        ])->menuIdentities()->createMany([
            ['identity_type' => CmsMenuAudienceType::JobSeeker],
            ['identity_type' => CmsMenuAudienceType::Recruiter],
        ]);

        $user = User::factory()->create();
        $school = School::query()->create([
            'school_code' => '4136010403',
            'name' => '南昌大学',
        ]);

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::CampusManager,
            'identity_name' => RcIdentityType::CampusManager->getLabel(),
            'organization_type' => 'school',
            'organization_id' => $school->id,
            'organization_name' => $school->name,
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/cms/home/schools')
            ->assertForbidden();
    }
}
