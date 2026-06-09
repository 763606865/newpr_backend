<?php

namespace Tests\Unit\Models;

use App\Enums\CompanyNatureType;
use App\Enums\CompanyProfileStatus;
use App\Enums\CompanyScaleType;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanyProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_has_profile_relation(): void
    {
        $company = Company::query()->create([
            'name' => '资料测试企业',
            'credit_code' => '91360100MA0000000P',
            'status' => CompanyStatus::Enabled,
        ]);

        $profile = CompanyProfile::query()->create([
            'company_id' => $company->id,
            'scale_type' => CompanyScaleType::From100To499,
            'nature_type' => CompanyNatureType::Private,
            'introduction' => '企业简介',
            'profile_status' => CompanyProfileStatus::Draft,
        ]);

        $company->refresh();

        $this->assertTrue($company->profile->is($profile));
        $this->assertSame(CompanyScaleType::From100To499, $profile->scale_type);
    }
}
