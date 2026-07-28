<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyStatus;
use App\Enums\RcAssetCode;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Company;
use App\Models\Rc\AssetAccount;
use App\Models\Rc\AssetLedger;
use App\Models\Rc\Job;
use App\Services\RcJobPublishingEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RcJobPublishingEntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prioritizes_the_employment_type_specific_asset(): void
    {
        $job = $this->createJob(RcJobEmploymentType::FullTime);
        $this->createAccount($job, RcAssetCode::FullTimeJobPosting, 1);
        $this->createAccount($job, RcAssetCode::JobPosting, 2);

        $consumed = RcJobPublishingEntitlementService::make()->consumeFor($job);

        $this->assertTrue($consumed);
        $this->assertSame(0, $this->balance($job, RcAssetCode::FullTimeJobPosting));
        $this->assertSame(2, $this->balance($job, RcAssetCode::JobPosting));
        $this->assertDatabaseHas('rc_asset_ledgers', [
            'owner_id' => $job->company_id,
            'asset_code' => RcAssetCode::FullTimeJobPosting->value,
            'delta' => -1,
        ]);
    }

    public function test_it_falls_back_to_the_general_job_posting_asset(): void
    {
        $job = $this->createJob(RcJobEmploymentType::Campus);
        $this->createAccount($job, RcAssetCode::CampusJobPosting, 0);
        $this->createAccount($job, RcAssetCode::JobPosting, 2);

        $consumed = RcJobPublishingEntitlementService::make()->consumeFor($job);

        $this->assertTrue($consumed);
        $this->assertSame(0, $this->balance($job, RcAssetCode::CampusJobPosting));
        $this->assertSame(1, $this->balance($job, RcAssetCode::JobPosting));
        $ledger = AssetLedger::query()->where('owner_id', $job->company_id)->sole();
        $this->assertSame(RcAssetCode::JobPosting->value, $ledger->asset_code);
        $this->assertSame(RcAssetCode::CampusJobPosting->value, $ledger->extra['preferred_asset_code']);
    }

    public function test_it_throws_when_specific_and_general_assets_are_both_insufficient(): void
    {
        $job = $this->createJob(RcJobEmploymentType::FullTime);
        $this->createAccount($job, RcAssetCode::FullTimeJobPosting, 0);
        $this->createAccount($job, RcAssetCode::JobPosting, 0);

        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage('职位发布权益不足。');

        RcJobPublishingEntitlementService::make()->consumeFor($job);
    }

    private function createJob(RcJobEmploymentType $employmentType): Job
    {
        $company = Company::query()->create([
            'name' => '测试企业',
            'credit_code' => '91360100'.strtoupper(substr(md5($employmentType->value.microtime()), 0, 10)),
            'status' => CompanyStatus::Enabled,
        ]);

        return Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-'.strtoupper(substr(md5(microtime()), 0, 12)),
            'title' => '测试职位',
            'employment_type' => $employmentType,
            'status' => RcJobStatus::Draft,
        ]);
    }

    private function createAccount(Job $job, RcAssetCode $assetCode, int $balance): void
    {
        AssetAccount::query()->create([
            'owner_type' => RcAssetOwnerType::Company,
            'owner_id' => $job->company_id,
            'asset_code' => $assetCode->value,
            'asset_name' => $assetCode->getLabel(),
            'balance' => $balance,
            'frozen_balance' => 0,
        ]);
    }

    private function balance(Job $job, RcAssetCode $assetCode): int
    {
        return (int) AssetAccount::query()
            ->where('owner_type', RcAssetOwnerType::Company->value)
            ->where('owner_id', $job->company_id)
            ->where('asset_code', $assetCode->value)
            ->value('balance');
    }
}
