<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Company;
use App\Models\Rc\UserCompanyBlacklist;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCompanyBlacklistControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_requires_job_seeker_identity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'rc')
            ->getJson('/rc/users/company-blacklists')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_index_returns_current_user_company_blacklists(): void
    {
        $user = $this->createJobSeekerContext();
        $otherUser = $this->createJobSeekerContext();
        $company = $this->createCompany(['name' => '南昌示例科技有限公司']);
        $otherCompany = $this->createCompany([
            'name' => '上海未来科技有限公司',
            'credit_code' => '91360100MA0000000A',
        ]);

        UserCompanyBlacklist::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'remark' => '老东家',
        ]);
        UserCompanyBlacklist::query()->create([
            'user_id' => $otherUser->id,
            'company_id' => $otherCompany->id,
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/users/company-blacklists?keyword='.urlencode('示例'))
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.company_id', $company->id)
            ->assertJsonPath('data.data.0.company.name', '南昌示例科技有限公司')
            ->assertJsonPath('data.data.0.remark', '老东家');
    }

    public function test_job_seeker_can_create_show_update_and_delete_company_blacklist(): void
    {
        $user = $this->createJobSeekerContext();
        $company = $this->createCompany();

        $createResponse = $this->actingAs($user, 'rc')
            ->postJson('/rc/users/company-blacklists', [
                'company_id' => $company->id,
                'remark' => '老东家',
            ])
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.blacklist.company_id', $company->id)
            ->assertJsonPath('data.blacklist.company.name', $company->name)
            ->assertJsonPath('data.blacklist.remark', '老东家');

        $blacklistId = (int) $createResponse->json('data.blacklist.id');

        $this->assertDatabaseHas('rc_user_company_blacklists', [
            'id' => $blacklistId,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'remark' => '老东家',
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/users/company-blacklists/'.$blacklistId)
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.blacklist.id', $blacklistId);

        $this->actingAs($user, 'rc')
            ->putJson('/rc/users/company-blacklists/'.$blacklistId, [
                'remark' => '更新备注',
            ])
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.blacklist.remark', '更新备注');

        $this->assertDatabaseHas('rc_user_company_blacklists', [
            'id' => $blacklistId,
            'remark' => '更新备注',
        ]);

        $this->actingAs($user, 'rc')
            ->deleteJson('/rc/users/company-blacklists/'.$blacklistId)
            ->assertOk()
            ->assertJsonPath('code', 200);

        $this->assertDatabaseMissing('rc_user_company_blacklists', [
            'id' => $blacklistId,
        ]);
    }

    public function test_store_rejects_duplicate_company(): void
    {
        $user = $this->createJobSeekerContext();
        $company = $this->createCompany();

        UserCompanyBlacklist::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/users/company-blacklists', [
                'company_id' => $company->id,
            ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '企业已在黑名单中。');
    }

    public function test_user_cannot_access_other_users_blacklist_record(): void
    {
        $user = $this->createJobSeekerContext();
        $otherUser = $this->createJobSeekerContext();
        $company = $this->createCompany();

        $blacklist = UserCompanyBlacklist::query()->create([
            'user_id' => $otherUser->id,
            'company_id' => $company->id,
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/users/company-blacklists/'.$blacklist->id)
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '企业黑名单记录不存在。');

        $this->actingAs($user, 'rc')
            ->deleteJson('/rc/users/company-blacklists/'.$blacklist->id)
            ->assertOk()
            ->assertJsonPath('code', 404);
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCompany(array $attributes = []): Company
    {
        return Company::query()->create(array_merge([
            'name' => '示例科技有限公司',
            'credit_code' => '91360100MA'.strtoupper(substr(uniqid(), -8)),
            'status' => CompanyStatus::Enabled,
        ], $attributes));
    }
}
