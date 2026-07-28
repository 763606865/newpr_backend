<?php

namespace Tests\Feature\Filament;

use App\Enums\CompanyStatus;
use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetSourceType;
use App\Enums\RcAssetType;
use App\Enums\RcBizPlanBillingCycle;
use App\Enums\RcBizPlanProductType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Filament\Resources\Companies\Pages\CompanyCommerce;
use App\Models\Company;
use App\Models\Rc\AssetAccount;
use App\Models\Rc\AssetDefinition;
use App\Models\Rc\AssetLedger;
use App\Models\Rc\BizPlan;
use App\Models\Rc\Order;
use App\Services\CompanyRcCommerceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class CompanyCommercePageTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    public function test_authorized_admin_can_view_company_commerce_page(): void
    {
        $this->actingAsFilamentAdmin();
        $company = $this->createCompany();

        Livewire::test(CompanyCommerce::class, ['record' => $company->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('企业权益与订单')
            ->assertSee('权益余额')
            ->assertSee('订单列表')
            ->assertSee('权益流水');
    }

    public function test_admin_can_increase_a_company_asset_balance_with_a_ledger(): void
    {
        $admin = $this->actingAsFilamentAdmin();
        $company = $this->createCompany();
        $definition = $this->createAssetDefinition('job_posting_full_time', '社招职位发布');

        CompanyRcCommerceService::make()->increaseAsset(
            $company,
            $definition,
            3,
            '测试人工增加',
            $admin->id,
        );

        $this->assertDatabaseHas('rc_asset_accounts', [
            'owner_type' => RcAssetOwnerType::Company->value,
            'owner_id' => $company->id,
            'asset_code' => 'job_posting_full_time',
            'balance' => 3,
        ]);
        $this->assertDatabaseHas('rc_asset_ledgers', [
            'owner_id' => $company->id,
            'asset_code' => 'job_posting_full_time',
            'delta' => 3,
            'balance_after' => 3,
            'source_type' => RcAssetSourceType::Manual->value,
            'source_id' => $admin->id,
            'remark' => '测试人工增加',
        ]);
    }

    public function test_admin_can_add_a_product_and_grant_its_assets(): void
    {
        $admin = $this->actingAsFilamentAdmin();
        $company = $this->createCompany();
        $this->createAssetDefinition('job_posting_campus', '校招职位发布');
        $plan = BizPlan::query()->create([
            'plan_name' => '校招基础套餐',
            'plan_code' => 'campus_basic',
            'price' => 199,
            'duration' => 30,
            'target_side' => RcBizPlanTargetSide::Recruiter,
            'product_type' => RcBizPlanProductType::JobPosting,
            'billing_cycle' => RcBizPlanBillingCycle::OneTime,
            'quota_rules' => [
                ['asset_code' => 'job_posting_campus', 'quantity' => 10],
            ],
            'status' => RcBizPlanStatus::Enabled,
        ]);

        $order = CompanyRcCommerceService::make()->addProduct(
            $company,
            $plan,
            2,
            '运营赠送套餐',
            $admin->id,
        );

        $this->assertSame(1, $order->items->count());
        $this->assertDatabaseHas('rc_orders', [
            'id' => $order->id,
            'payer_type' => RcAssetOwnerType::Company->value,
            'payer_id' => $company->id,
            'product_code' => 'campus_basic',
            'quantity' => 2,
            'paid_amount' => 0,
            'pay_status' => 1,
            'order_status' => 1,
        ]);
        $this->assertDatabaseHas('rc_asset_accounts', [
            'owner_id' => $company->id,
            'asset_code' => 'job_posting_campus',
            'balance' => 20,
        ]);
        $this->assertSame(1, AssetLedger::query()->where('owner_id', $company->id)->count());
        $this->assertSame(1, Order::query()->where('payer_id', $company->id)->count());
        $this->assertSame(20, AssetAccount::query()->where('owner_id', $company->id)->value('balance'));
    }

    private function createCompany(): Company
    {
        return Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'legal_person' => '李四',
            'contact_phone' => '13900000000',
            'address' => '南昌市高新区示例路 88 号',
            'status' => CompanyStatus::Enabled,
        ]);
    }

    private function createAssetDefinition(string $code, string $name): AssetDefinition
    {
        return AssetDefinition::query()->create([
            'asset_code' => $code,
            'asset_name' => $name,
            'owner_type' => RcAssetOwnerType::Company,
            'asset_type' => RcAssetType::Count,
            'unit' => '次',
            'default_duration' => 0,
            'status' => RcAssetDefinitionStatus::Enabled,
        ]);
    }
}
