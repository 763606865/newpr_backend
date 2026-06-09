<?php

namespace Tests\Unit\Services;

use App\Enums\CompanyNatureType;
use App\Enums\CompanyProfileStatus;
use App\Enums\CompanyScaleType;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Services\CompanyProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_for_company_creates_draft_profile_once(): void
    {
        $company = $this->createCompany();
        $service = CompanyProfileService::make();

        $profile = $service->ensureForCompany($company);
        $again = $service->ensureForCompany($company);

        $this->assertSame($profile->id, $again->id);
        $this->assertSame(CompanyProfileStatus::Draft, $profile->profile_status);
        $this->assertDatabaseCount('company_profiles', 1);
    }

    public function test_update_marks_profile_complete_when_required_fields_present(): void
    {
        $company = $this->createCompany();
        $service = CompanyProfileService::make();
        $profile = $service->ensureForCompany($company);

        $updated = $service->update($profile, [
            'scale_type' => CompanyScaleType::From20To99->value,
            'nature_type' => CompanyNatureType::Private->value,
            'introduction' => '我们是一家专注招聘科技的公司。',
            'logo' => 'uploads/rc/logo.png',
            'short_name' => '示例科技',
        ]);

        $this->assertSame(CompanyProfileStatus::Complete, $updated->profile_status);
        $this->assertSame('示例科技', $updated->short_name);
    }

    public function test_update_keeps_draft_when_required_fields_missing(): void
    {
        $company = $this->createCompany();
        $service = CompanyProfileService::make();
        $profile = $service->ensureForCompany($company);

        $updated = $service->update($profile, [
            'introduction' => '仅填写简介',
        ]);

        $this->assertSame(CompanyProfileStatus::Draft, $updated->profile_status);
    }

    private function createCompany(): Company
    {
        return Company::query()->create([
            'name' => '资料服务测试企业',
            'credit_code' => '91360100MA0000000S',
            'status' => CompanyStatus::Enabled,
        ]);
    }
}
