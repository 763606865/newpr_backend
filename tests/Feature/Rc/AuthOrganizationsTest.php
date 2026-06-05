<?php

namespace Tests\Feature\Rc;

use App\Enums\AreaLevel;
use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Area;
use App\Models\Company;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\PersonalAccessTokenFactory;
use Tests\TestCase;

class AuthOrganizationsTest extends TestCase
{
    use RefreshDatabase;

    private const CREDIT_CODE_A = '91360100MA0000000A';

    private const CREDIT_CODE_B = '91360100MA0000000B';

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient('RC Organizations Test', 'rc_users');
    }

    public function test_returns_all_bound_companies_for_recruiter_identity(): void
    {
        $user = User::factory()->create();
        $companyA = $this->createCompany(['name' => '甲公司', 'credit_code' => self::CREDIT_CODE_A]);
        $companyB = $this->createCompany(['name' => '乙公司', 'credit_code' => self::CREDIT_CODE_B]);

        $currentIdentity = $this->createRecruiterIdentity($user, [
            'organization_type' => 'company',
            'organization_id' => $companyA->id,
            'organization_name' => $companyA->name,
            'job_title' => '招聘经理',
        ]);

        $this->createRecruiterIdentity($user, [
            'organization_type' => 'company',
            'organization_id' => $companyB->id,
            'organization_name' => $companyB->name,
            'job_title' => 'HR 专员',
            'is_default' => 0,
        ]);

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->rcBearerToken($user, $currentIdentity),
            ])
            ->getJson('/rc/auth/organizations');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.identity_type', RcIdentityType::Recruiter->value)
            ->assertJsonPath('data.organization_type', 'company')
            ->assertJsonCount(2, 'data.items');

        $organizationIds = collect($response->json('data.items'))
            ->pluck('organization_id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([$companyA->id, $companyB->id], $organizationIds);
        $this->assertSame('甲公司', $response->json('data.items.0.organization.name'));
    }

    public function test_returns_bound_schools_for_campus_manager_identity(): void
    {
        $user = User::factory()->create();
        $schoolId = DB::table('schools')->insertGetId([
            'name' => '南昌大学',
            'school_code' => 'NCU001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $identity = UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::CampusManager,
            'identity_name' => RcIdentityType::CampusManager->getLabel(),
            'organization_type' => 'school',
            'organization_id' => $schoolId,
            'organization_name' => '南昌大学',
            'job_title' => '就业办主任',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->rcBearerToken($user, $identity),
            ])
            ->getJson('/rc/auth/organizations');

        $response
            ->assertOk()
            ->assertJsonPath('data.identity_type', RcIdentityType::CampusManager->value)
            ->assertJsonPath('data.organization_type', 'school')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.organization_id', $schoolId)
            ->assertJsonPath('data.items.0.organization.name', '南昌大学');
    }

    public function test_returns_bound_areas_for_government_manager_identity(): void
    {
        $user = User::factory()->create();
        $area = Area::query()->create([
            'name' => '南昌市',
            'code' => '360100',
            'level' => AreaLevel::City,
        ]);

        $identity = UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::GovernmentManager,
            'identity_name' => RcIdentityType::GovernmentManager->getLabel(),
            'organization_type' => 'area',
            'organization_id' => $area->id,
            'organization_name' => $area->name,
            'job_title' => '负责人',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->rcBearerToken($user, $identity),
            ])
            ->getJson('/rc/auth/organizations');

        $response
            ->assertOk()
            ->assertJsonPath('data.identity_type', RcIdentityType::GovernmentManager->value)
            ->assertJsonPath('data.organization_type', 'area')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.organization.code', '360100');
    }

    public function test_returns_empty_items_for_job_seeker_without_organization(): void
    {
        $user = User::factory()->create();
        $identity = UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => RcIdentityType::JobSeeker->getLabel(),
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->rcBearerToken($user, $identity),
            ])
            ->getJson('/rc/auth/organizations');

        $response
            ->assertOk()
            ->assertJsonPath('data.identity_type', RcIdentityType::JobSeeker->value)
            ->assertJsonPath('data.organization_type', null)
            ->assertJsonPath('data.items', []);
    }

    public function test_requires_token_bound_identity(): void
    {
        $user = User::factory()->create();
        $this->createRecruiterIdentity($user);

        $bootstrapToken = app(PersonalAccessTokenFactory::class)->make(
            $user->getAuthIdentifier(),
            'rc',
            [],
            'rc_users',
        )->accessToken;

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$bootstrapToken,
            ])
            ->getJson('/rc/auth/organizations');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换身份。');
    }

    public function test_excludes_disabled_identity_bindings(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany();
        $currentIdentity = $this->createRecruiterIdentity($user, [
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'job_title' => '招聘经理',
        ]);

        $this->createRecruiterIdentity($user, [
            'organization_type' => 'company',
            'organization_id' => $this->createCompany([
                'name' => '已停用绑定',
                'credit_code' => self::CREDIT_CODE_B,
            ])->id,
            'organization_name' => '已停用绑定',
            'job_title' => '前员工',
            'is_default' => 0,
            'status' => RcIdentityStatus::Disabled,
        ]);

        $response = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->rcBearerToken($user, $currentIdentity),
            ])
            ->getJson('/rc/auth/organizations');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.organization_id', $company->id);
    }

    private function rcBearerToken(User $user, UserIdentity $identity): string
    {
        $tokenResult = app(PersonalAccessTokenFactory::class)->make(
            $user->getAuthIdentifier(),
            'rc',
            [],
            'rc_users',
        );

        $token = $tokenResult->getToken();
        $token->responsible_type = UserIdentity::class;
        $token->responsible_id = $identity->id;
        $token->save();

        return $tokenResult->accessToken;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCompany(array $attributes = []): Company
    {
        return Company::query()->create(array_merge([
            'name' => '南昌示例科技有限公司',
            'credit_code' => self::CREDIT_CODE_A,
            'legal_person' => '李四',
            'contact_phone' => '13900000000',
            'address' => '南昌市高新区示例路 88 号',
            'status' => CompanyStatus::Enabled,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRecruiterIdentity(User $user, array $attributes = []): UserIdentity
    {
        return UserIdentity::query()->create(array_merge([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => RcIdentityType::Recruiter->getLabel(),
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ], $attributes));
    }
}
