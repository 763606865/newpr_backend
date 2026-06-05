<?php

namespace Tests\Unit\Models;

use App\Enums\CompanyContactType;
use App\Enums\CompanyLicenseType;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanyContact;
use App\Models\CompanyLicense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_has_licenses_and_contacts_relations(): void
    {
        $company = Company::query()->create([
            'name' => '证件测试企业',
            'credit_code' => '91360100MA0000000L',
            'status' => CompanyStatus::Enabled,
        ]);

        $license = CompanyLicense::query()->create([
            'company_id' => $company->id,
            'license_type' => CompanyLicenseType::BusinessLicense,
            'name' => '营业执照',
            'license_no' => 'LIC-001',
            'file_url' => 'uploads/company/license.pdf',
            'is_primary' => 1,
        ]);

        $contact = CompanyContact::query()->create([
            'company_id' => $company->id,
            'contact_type' => CompanyContactType::Shareholder,
            'name' => '张三',
            'share_ratio' => 60.50,
            'phone' => '13800000000',
        ]);

        $company->refresh();

        $this->assertTrue($company->licenses->contains($license));
        $this->assertTrue($company->contacts->contains($contact));
        $this->assertSame(CompanyLicenseType::BusinessLicense, $license->license_type);
        $this->assertSame(CompanyContactType::Shareholder, $contact->contact_type);
        $this->assertSame('60.50', $contact->share_ratio);
    }
}
