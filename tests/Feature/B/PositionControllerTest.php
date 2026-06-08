<?php

namespace Tests\Feature\B;

use App\Models\BUser;
use App\Models\Company;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use Tests\TestCase;

class PositionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_store_persists_is_leader_field(): void
    {
        $company = $this->createCompany();
        $this->authenticateForCompany($company);

        $response = $this->postJson('/b/positions', [
            'name' => '部门经理',
            'code' => 'DEPT_MANAGER',
            'is_leader' => true,
            'sort' => 10,
            'remark' => '管理岗',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.is_leader', true)
            ->assertJsonPath('data.code', 'DEPT_MANAGER');

        $this->assertDatabaseHas('positions', [
            'company_id' => $company->id,
            'code' => 'DEPT_MANAGER',
            'is_leader' => 1,
        ]);
    }

    public function test_store_defaults_is_leader_to_false(): void
    {
        $company = $this->createCompany();
        $this->authenticateForCompany($company);

        $response = $this->postJson('/b/positions', [
            'name' => '普通员工岗',
            'code' => 'STAFF',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_leader', false);

        $this->assertDatabaseHas('positions', [
            'company_id' => $company->id,
            'code' => 'STAFF',
            'is_leader' => 0,
        ]);
    }

    public function test_update_can_change_is_leader_field(): void
    {
        $company = $this->createCompany();
        $this->authenticateForCompany($company);

        $position = Position::query()->create([
            'company_id' => $company->id,
            'name' => '专员',
            'code' => 'SPECIALIST',
            'is_leader' => false,
        ]);

        $response = $this->putJson('/b/positions/'.$position->id, [
            'name' => '专员',
            'code' => 'SPECIALIST',
            'is_leader' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_leader', true);

        $this->assertTrue($position->fresh()->is_leader);
    }

    public function test_index_can_filter_by_is_leader(): void
    {
        $company = $this->createCompany();
        $this->authenticateForCompany($company);

        Position::query()->create([
            'company_id' => $company->id,
            'name' => '经理岗',
            'code' => 'MANAGER',
            'is_leader' => true,
        ]);

        Position::query()->create([
            'company_id' => $company->id,
            'name' => '执行岗',
            'code' => 'EXECUTOR',
            'is_leader' => false,
        ]);

        $response = $this->getJson('/b/positions?is_leader=1');

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.code', 'MANAGER');
    }

    private function createCompany(): Company
    {
        return Company::query()->create([
            'name' => '测试企业有限公司',
            'credit_code' => '91360100MA'.random_int(10000000, 99999999),
        ]);
    }

    private function authenticateForCompany(Company $company): BUser
    {
        $user = BUser::query()->create([
            'name' => '测试B用户',
            'phone' => '13'.str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT),
            'email' => 'buser'.random_int(1000, 9999).'@example.com',
            'password' => 'secret',
            'status' => 'active',
        ]);

        $token = new class($company) implements ScopeAuthorizable
        {
            public string $id;

            public string $responsible_type;

            public int $responsible_id;

            public Company $responsible;

            public function __construct(Company $company)
            {
                $this->id = (string) Str::uuid();
                $this->responsible_type = Company::class;
                $this->responsible_id = $company->id;
                $this->responsible = $company;
            }

            public function can(string $scope): bool
            {
                return true;
            }

            public function cant(string $scope): bool
            {
                return false;
            }
        };

        $this->actingAs($user->withAccessToken($token), 'b');

        return $user;
    }
}
