<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyStatus;
use App\Enums\RcAssetChangeType;
use App\Enums\RcAssetCode;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetSourceType;
use App\Models\Company;
use App\Models\Rc\AssetAccount;
use App\Models\Rc\AssetLedger;
use App\Services\CompanyApprovalBenefitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyApprovalBenefitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_grants_company_approval_benefits_once(): void
    {
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);
        $service = CompanyApprovalBenefitService::make();

        $service->grant($company);
        $service->grant($company);

        $fullTimeAccount = AssetAccount::query()
            ->where('owner_type', RcAssetOwnerType::Company)
            ->where('owner_id', $company->id)
            ->where('asset_code', RcAssetCode::FullTimeJobPosting)
            ->sole();
        $campusAccount = AssetAccount::query()
            ->where('owner_type', RcAssetOwnerType::Company)
            ->where('owner_id', $company->id)
            ->where('asset_code', RcAssetCode::CampusJobPosting)
            ->sole();

        $this->assertSame(1, $fullTimeAccount->balance);
        $this->assertSame(10, $campusAccount->balance);
        $this->assertSame(2, AssetLedger::query()->where('owner_id', $company->id)->count());

        $fullTimeLedger = AssetLedger::query()
            ->where('asset_code', RcAssetCode::FullTimeJobPosting)
            ->sole();
        $this->assertSame(RcAssetChangeType::Grant, $fullTimeLedger->change_type);
        $this->assertSame(RcAssetSourceType::System, $fullTimeLedger->source_type);
        $this->assertSame(1, $fullTimeLedger->delta);
        $this->assertSame(1, $fullTimeLedger->balance_after);
        $this->assertSame($company->id, $fullTimeLedger->source_id);
        $this->assertSame(
            sprintf('company_approval:%d:%s', $company->id, RcAssetCode::FullTimeJobPosting->value),
            $fullTimeLedger->biz_no,
        );

        $campusLedger = AssetLedger::query()
            ->where('asset_code', RcAssetCode::CampusJobPosting)
            ->sole();
        $this->assertSame(10, $campusLedger->delta);
        $this->assertSame(10, $campusLedger->balance_after);
    }
}
