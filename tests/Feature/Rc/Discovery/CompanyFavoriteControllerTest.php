<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Company;
use App\Models\Rc\CompanyFavorite;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyFavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_returns_favorited_companies_for_job_seeker(): void
    {
        $user = $this->createJobSeekerContext();
        $company = $this->createEnabledCompany();

        CompanyFavorite::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/talent/favorites/companies')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $company->id)
            ->assertJsonPath('data.data.0.is_favorited', true);
    }

    public function test_favorite_requires_job_seeker_identity(): void
    {
        $user = User::factory()->create();
        $company = $this->createEnabledCompany();

        $this->actingAs($user, 'rc')
            ->postJson('/rc/talent/companies/'.$company->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_job_seeker_can_favorite_and_unfavorite_company(): void
    {
        $user = $this->createJobSeekerContext();
        $company = $this->createEnabledCompany();

        $this->actingAs($user, 'rc')
            ->postJson('/rc/talent/companies/'.$company->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.is_favorited', true);

        $this->assertDatabaseHas('rc_company_favorites', [
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/talent/companies/'.$company->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('data.is_favorited', true);

        $this->assertSame(1, CompanyFavorite::query()->count());

        $this->actingAs($user, 'rc')
            ->deleteJson('/rc/talent/companies/'.$company->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('data.is_favorited', false);

        $this->assertDatabaseMissing('rc_company_favorites', [
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_favorite_disabled_company_returns_not_found(): void
    {
        $user = $this->createJobSeekerContext();
        $company = Company::query()->create([
            'name' => '禁用企业',
            'credit_code' => '91360100MA0000000F',
            'status' => CompanyStatus::Disabled,
        ]);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/talent/companies/'.$company->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '企业不存在或不可查看。');
    }

    private function createEnabledCompany(): Company
    {
        return Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);
    }

    private function createJobSeekerContext(): User
    {
        $user = User::factory()->create();

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        return $user;
    }
}
