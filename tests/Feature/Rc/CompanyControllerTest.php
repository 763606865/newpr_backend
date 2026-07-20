<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyContactType;
use App\Enums\CompanyLicenseType;
use App\Enums\CompanyNatureType;
use App\Enums\CompanyProfileStatus;
use App\Enums\CompanyRestType;
use App\Enums\CompanyScaleType;
use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Company;
use App\Models\CompanyLicense;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;

    private const CREDIT_CODE = '91360100MA0000000X';

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_lookup_returns_exists_false_when_company_missing(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/companies/lookup?credit_code='.self::CREDIT_CODE);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.exists', false)
            ->assertJsonPath('data.company', null);
    }

    public function test_lookup_returns_company_when_exists(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany();
        CompanyLicense::query()->create([
            'company_id' => $company->id,
            'license_type' => CompanyLicenseType::BusinessLicense,
            'name' => '营业执照',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/companies/lookup?credit_code='.self::CREDIT_CODE);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.exists', true)
            ->assertJsonPath('data.company.id', $company->id)
            ->assertJsonPath('data.company.name', $company->name)
            ->assertJsonPath('data.company.credit_code', self::CREDIT_CODE)
            ->assertJsonPath('data.company.licenses.0.name', '营业执照');
    }

    public function test_lookup_normalizes_credit_code_case(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany(['credit_code' => '91360100ma0000000x']);

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/companies/lookup?credit_code='.self::CREDIT_CODE);

        $response
            ->assertOk()
            ->assertJsonPath('data.exists', true)
            ->assertJsonPath('data.company.id', $company->id);
    }

    public function test_lookup_searches_companies_by_name(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany(['name' => '南昌示例科技有限公司']);
        $this->createCompany([
            'name' => '上海未来科技有限公司',
            'credit_code' => '91360100MA0000000A',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/companies/lookup?name='.urlencode('示例科技'));

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonCount(1, 'data.companies')
            ->assertJsonPath('data.companies.0.id', $company->id)
            ->assertJsonPath('data.companies.0.name', '南昌示例科技有限公司');
    }

    public function test_bind_requires_recruiter_identity(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany();

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/companies/bind', [
                'company_id' => $company->id,
                'job_title' => 'HR 经理',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为招聘方身份。');
    }

    public function test_bind_links_existing_company_to_recruiter_identity(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany();
        $identity = $this->createRecruiterIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/companies/bind', [
                'company_id' => $company->id,
                'job_title' => '招聘经理',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.company.id', $company->id)
            ->assertJsonPath('data.identity.id', $identity->id)
            ->assertJsonPath('data.identity.organization_type', 'company')
            ->assertJsonPath('data.identity.organization_id', $company->id)
            ->assertJsonPath('data.identity.organization_name', $company->name)
            ->assertJsonPath('data.identity.job_title', '招聘经理')
            ->assertJsonPath('data.identity.has_basic_info', true);

        $this->assertDatabaseHas('rc_user_identities', [
            'id' => $identity->id,
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'job_title' => '招聘经理',
        ]);
    }

    public function test_bind_returns_validation_error_when_company_does_not_exist(): void
    {
        $user = User::factory()->create();
        $this->createRecruiterIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/companies/bind', [
                'company_id' => 99999,
                'job_title' => '招聘经理',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['company_id']);
    }

    public function test_bind_rejects_disabled_company(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany(['status' => CompanyStatus::Disabled]);
        $this->createRecruiterIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/companies/bind', [
                'company_id' => $company->id,
                'job_title' => '招聘经理',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '该企业已被禁用，无法绑定。');
    }

    public function test_bind_rejects_when_same_company_already_bound(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany();
        $this->createRecruiterIdentity($user, [
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'job_title' => '已有岗位',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/companies/bind', [
                'company_id' => $company->id,
                'job_title' => '招聘经理',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '您已绑定该企业。');
    }

    public function test_bind_creates_new_identity_when_current_identity_already_has_company(): void
    {
        $user = User::factory()->create();
        $companyA = $this->createCompany();
        $companyB = $this->createCompany([
            'name' => '乙公司',
            'credit_code' => '91360100MA0000000B',
        ]);
        $boundIdentity = $this->createRecruiterIdentity($user, [
            'organization_type' => 'company',
            'organization_id' => $companyA->id,
            'organization_name' => $companyA->name,
            'job_title' => '已有岗位',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/companies/bind', [
                'company_id' => $companyB->id,
                'job_title' => '招聘经理',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.company.id', $companyB->id)
            ->assertJsonPath('data.identity.organization_id', $companyB->id);

        $this->assertNotEquals($boundIdentity->id, $response->json('data.identity.id'));

        $this->assertDatabaseCount('rc_user_identities', 2);
        $this->assertDatabaseHas('rc_user_identities', [
            'user_id' => $user->id,
            'organization_id' => $companyB->id,
            'job_title' => '招聘经理',
        ]);
    }

    public function test_store_creates_new_identity_when_current_identity_already_has_company(): void
    {
        $user = User::factory()->create();
        $companyA = $this->createCompany();
        $boundIdentity = $this->createRecruiterIdentity($user, [
            'organization_type' => 'company',
            'organization_id' => $companyA->id,
            'organization_name' => $companyA->name,
            'job_title' => '已有岗位',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/companies', [
                'name' => '乙公司科技有限公司',
                'credit_code' => '91360100MA0000000B',
                'legal_person' => '王五',
                'contact_phone' => '13900000001',
                'job_title' => 'HR 总监',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.company.credit_code', '91360100MA0000000B')
            ->assertJsonPath('data.identity.organization_id', Company::query()->where('credit_code', '91360100MA0000000B')->value('id'));

        $this->assertNotEquals($boundIdentity->id, $response->json('data.identity.id'));

        $this->assertDatabaseCount('rc_user_identities', 2);
    }

    public function test_bind_rejects_invalid_company_id(): void
    {
        $user = User::factory()->create();
        $this->createRecruiterIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/companies/bind', [
                'company_id' => 0,
                'job_title' => '招聘经理',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['company_id']);
    }

    public function test_store_registers_company_and_seeds_profile(): void
    {
        $user = User::factory()->create();
        $identity = $this->createRecruiterIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/companies', [
                'name' => '南昌示例科技有限公司',
                'credit_code' => self::CREDIT_CODE,
                'legal_person' => '李四',
                'contact_phone' => '13900000000',
                'address' => '南昌市高新区示例路 88 号',
                'job_title' => 'HR 总监',
                'licenses_file_path' => 'uploads/rc/file/license.pdf',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.company.name', '南昌示例科技有限公司')
            ->assertJsonPath('data.company.credit_code', self::CREDIT_CODE)
            ->assertJsonPath('data.company.status', CompanyStatus::Auditing->value)
            ->assertJsonPath('data.company.licenses.0.name', '营业执照')
            ->assertJsonPath('data.company.licenses.0.license_no', self::CREDIT_CODE)
            ->assertJsonPath('data.company.licenses.0.file_url', 'uploads/rc/file/license.pdf')
            ->assertJsonPath('data.company.contacts.0.name', '李四')
            ->assertJsonPath('data.company.contacts.0.contact_type', CompanyContactType::LegalPerson->value)
            ->assertJsonPath('data.identity.id', $identity->id)
            ->assertJsonPath('data.identity.job_title', 'HR 总监')
            ->assertJsonPath('data.identity.has_basic_info', true)
            ->assertJsonPath('data.company.profile.profile_status', CompanyProfileStatus::Draft->value);

        $companyId = Company::query()->where('credit_code', self::CREDIT_CODE)->value('id');

        $this->assertDatabaseHas('rc_company_profiles', [
            'company_id' => $companyId,
            'profile_status' => CompanyProfileStatus::Draft->value,
        ]);

        $this->assertDatabaseHas('company_licenses', [
            'company_id' => $companyId,
            'license_type' => CompanyLicenseType::BusinessLicense->value,
            'file_url' => 'uploads/rc/file/license.pdf',
        ]);

        $this->assertDatabaseHas('company_contacts', [
            'company_id' => $companyId,
            'contact_type' => CompanyContactType::LegalPerson->value,
            'name' => '李四',
        ]);
    }

    public function test_store_rejects_when_company_already_exists(): void
    {
        $user = User::factory()->create();
        $this->createCompany();
        $this->createRecruiterIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/companies', [
                'name' => '另一家企业名称',
                'credit_code' => self::CREDIT_CODE,
                'legal_person' => '王五',
                'contact_phone' => '13900000001',
                'job_title' => 'HR',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '企业已存在，请直接绑定。');
    }

    public function test_bind_creates_draft_profile_when_missing(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany();
        $this->createRecruiterIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/companies/bind', [
                'company_id' => $company->id,
                'job_title' => '招聘经理',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.company.profile.profile_status', CompanyProfileStatus::Draft->value);

        $this->assertDatabaseHas('rc_company_profiles', [
            'company_id' => $company->id,
            'profile_status' => CompanyProfileStatus::Draft->value,
        ]);
    }

    public function test_profile_show_requires_bound_recruiter_company(): void
    {
        $user = User::factory()->create();
        $this->createRecruiterIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/companies/profile');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为招聘方身份并绑定企业。');
    }

    public function test_profile_update_updates_company_profile(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany();
        $this->createRecruiterIdentity($user, [
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'job_title' => 'HR',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->putJson('/rc/companies/profile', [
                'short_name' => '示例科技',
                'scale_type' => CompanyScaleType::From100To499->value,
                'nature_type' => CompanyNatureType::Private->value,
                'introduction' => '专注招聘数字化。',
                'work_time' => '09:00-18:00',
                'rest_type' => CompanyRestType::AlternatingWeekend->value,
                'salary_pay_day' => 5,
                'has_overtime_subsidy' => true,
                'logo' => 'uploads/rc/logo.png',
                'benefit_tags' => ['social_insurance', 'weekend_off'],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.profile.short_name', '示例科技')
            ->assertJsonPath('data.profile.scale_type', CompanyScaleType::From100To499->value)
            ->assertJsonPath('data.profile.profile_status', CompanyProfileStatus::Complete->value)
            ->assertJsonPath('data.profile.work_time', '09:00-18:00')
            ->assertJsonPath('data.profile.rest_type', CompanyRestType::AlternatingWeekend->value)
            ->assertJsonPath('data.profile.rest_type_label', '大小周')
            ->assertJsonPath('data.profile.salary_pay_day', 5)
            ->assertJsonPath('data.profile.has_overtime_subsidy', true)
            ->assertJsonPath('data.profile.benefit_tags.0', 'social_insurance');

        $this->assertDatabaseHas('rc_company_profiles', [
            'company_id' => $company->id,
            'short_name' => '示例科技',
            'work_time' => '09:00-18:00',
            'rest_type' => CompanyRestType::AlternatingWeekend->value,
            'salary_pay_day' => 5,
            'has_overtime_subsidy' => true,
            'profile_status' => CompanyProfileStatus::Complete->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCompany(array $attributes = []): Company
    {
        return Company::query()->create(array_merge([
            'name' => '南昌示例科技有限公司',
            'credit_code' => self::CREDIT_CODE,
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
