<?php

namespace Tests\Unit\Services;

use App\Enums\CompanyContactType;
use App\Enums\CompanyLicenseType;
use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Company;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use App\Services\RcCompanyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RcCompanyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_company_profile_creates_business_license_and_contacts(): void
    {
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'legal_person' => '李四',
            'contact_phone' => '13900000000',
            'address' => '南昌市高新区示例路 88 号',
            'status' => CompanyStatus::Enabled,
        ]);

        RcCompanyService::make()->seedCompanyProfile($company, [
            'credit_code' => '91360100MA0000000X',
            'legal_person' => '李四',
            'contact_phone' => '13900000000',
            'address' => '南昌市高新区示例路 88 号',
        ], 'uploads/rc/file/license.pdf');

        $this->assertDatabaseHas('company_licenses', [
            'company_id' => $company->id,
            'license_type' => CompanyLicenseType::BusinessLicense->value,
            'name' => '营业执照',
            'license_no' => '91360100MA0000000X',
            'file_url' => 'uploads/rc/file/license.pdf',
            'file_name' => 'license.pdf',
            'file_ext' => 'pdf',
            'is_primary' => 1,
        ]);

        $this->assertDatabaseHas('company_contacts', [
            'company_id' => $company->id,
            'contact_type' => CompanyContactType::LegalPerson->value,
            'name' => '李四',
            'phone' => '13900000000',
            'is_primary' => 1,
        ]);

        $this->assertDatabaseHas('company_contacts', [
            'company_id' => $company->id,
            'contact_type' => CompanyContactType::Contact->value,
            'name' => '李四',
            'phone' => '13900000000',
            'is_primary' => 0,
        ]);
    }

    public function test_seed_company_profile_allows_missing_license_file(): void
    {
        $company = Company::query()->create([
            'name' => '无执照文件企业',
            'credit_code' => '91360100MA0000000Y',
            'legal_person' => '王五',
            'contact_phone' => '13900000001',
            'status' => CompanyStatus::Enabled,
        ]);

        RcCompanyService::make()->seedCompanyProfile($company, [
            'credit_code' => '91360100MA0000000Y',
            'legal_person' => '王五',
            'contact_phone' => '13900000001',
        ]);

        $this->assertDatabaseHas('company_licenses', [
            'company_id' => $company->id,
            'license_type' => CompanyLicenseType::BusinessLicense->value,
            'file_url' => null,
        ]);
    }

    public function test_prepare_recruiter_identity_reuses_unbound_identity(): void
    {
        $user = User::factory()->create();
        $identity = UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => '招聘方',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $prepared = RcCompanyService::make()->prepareRecruiterIdentityForCompanyBind($user);

        $this->assertSame($identity->id, $prepared->id);
        $this->assertDatabaseCount('rc_user_identities', 1);
    }

    public function test_prepare_recruiter_identity_creates_new_row_when_current_is_bound(): void
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => '甲公司',
            'credit_code' => '91360100MA0000000A',
            'status' => CompanyStatus::Enabled,
        ]);
        $boundIdentity = UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => '招聘方',
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $prepared = RcCompanyService::make()->prepareRecruiterIdentityForCompanyBind($user);

        $this->assertNotSame($boundIdentity->id, $prepared->id);
        $this->assertNull($prepared->organization_id);
        $this->assertDatabaseCount('rc_user_identities', 2);
    }
}
