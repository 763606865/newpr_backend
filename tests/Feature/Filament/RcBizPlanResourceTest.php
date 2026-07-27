<?php

namespace Tests\Feature\Filament;

use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetType;
use App\Enums\RcBizPlanBillingCycle;
use App\Enums\RcBizPlanProductType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Filament\Resources\Rc\BizPlans\Pages\CreateBizPlan;
use App\Filament\Resources\Rc\BizPlans\Pages\EditBizPlan;
use App\Models\Rc\AssetDefinition;
use App\Models\Rc\BizPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class RcBizPlanResourceTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function bizPlanPermissions(): array
    {
        return [
            'ViewAny:BizPlan',
            'View:BizPlan',
            'Create:BizPlan',
            'Update:BizPlan',
        ];
    }

    public function test_admin_can_create_an_rc_product_with_entitlement_rules(): void
    {
        $this->actingAsFilamentAdmin($this->bizPlanPermissions());
        $this->createAssetDefinition();

        Livewire::test(CreateBizPlan::class)
            ->assertSuccessful()
            ->assertSee('商品名称')
            ->assertSee('权益规则')
            ->fillForm([
                'plan_name' => '7天紧急招聘',
                'plan_code' => 'job_urgent_7d',
                'target_side' => RcBizPlanTargetSide::Recruiter,
                'product_type' => RcBizPlanProductType::ValueAddedItem,
                'price' => '99.00',
                'billing_cycle' => RcBizPlanBillingCycle::OneTime,
                'duration' => 90,
                'status' => RcBizPlanStatus::Enabled,
                'quota_rules' => [
                    [
                        'asset_code' => 'job_urgent',
                        'quantity' => 1,
                    ],
                ],
                'remark' => '购买后可为一个职位开启7天紧急招聘。',
                'sort' => 10,
                'extra' => [
                    'refund_policy' => 'unused_only',
                ],
            ])
            ->call('create')
            ->assertNotified();

        $product = BizPlan::query()->where('plan_code', 'job_urgent_7d')->sole();

        $this->assertSame('7天紧急招聘', $product->plan_name);
        $this->assertSame('99.00', $product->price);
        $this->assertSame(RcBizPlanTargetSide::Recruiter, $product->target_side);
        $this->assertSame(RcBizPlanProductType::ValueAddedItem, $product->product_type);
        $this->assertSame(RcBizPlanBillingCycle::OneTime, $product->billing_cycle);
        $this->assertSame(RcBizPlanStatus::Enabled, $product->status);
        $this->assertSame('job_urgent', $product->quota_rules[0]['asset_code']);
        $this->assertSame('unused_only', $product->extra['refund_policy']);
    }

    public function test_admin_can_update_and_disable_an_rc_product(): void
    {
        $this->actingAsFilamentAdmin($this->bizPlanPermissions());
        $product = $this->createProduct();

        Livewire::test(EditBizPlan::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'price' => '129.00',
                'status' => RcBizPlanStatus::Disabled,
            ])
            ->call('save')
            ->assertNotified();

        $product->refresh();

        $this->assertSame('129.00', $product->price);
        $this->assertSame(RcBizPlanStatus::Disabled, $product->status);
    }

    public function test_product_code_must_be_unique(): void
    {
        $this->actingAsFilamentAdmin($this->bizPlanPermissions());
        $this->createProduct();

        Livewire::test(CreateBizPlan::class)
            ->fillForm([
                'plan_name' => '重复商品',
                'plan_code' => 'job_urgent_7d',
                'target_side' => RcBizPlanTargetSide::Recruiter,
                'product_type' => RcBizPlanProductType::ValueAddedItem,
                'price' => '199.00',
                'billing_cycle' => RcBizPlanBillingCycle::OneTime,
                'duration' => 30,
                'status' => RcBizPlanStatus::Enabled,
                'sort' => 20,
            ])
            ->call('create')
            ->assertHasFormErrors(['plan_code' => 'unique']);

        $this->assertSame(1, BizPlan::query()->count());
    }

    private function createProduct(): BizPlan
    {
        return BizPlan::query()->create([
            'plan_name' => '7天紧急招聘',
            'plan_code' => 'job_urgent_7d',
            'target_side' => RcBizPlanTargetSide::Recruiter,
            'product_type' => RcBizPlanProductType::ValueAddedItem,
            'price' => '99.00',
            'billing_cycle' => RcBizPlanBillingCycle::OneTime,
            'duration' => 90,
            'status' => RcBizPlanStatus::Enabled,
            'quota_rules' => [
                [
                    'asset_code' => 'job_urgent',
                    'asset_name' => '职位紧急招聘',
                    'quantity' => 1,
                    'duration_days' => 7,
                ],
            ],
            'sort' => 10,
        ]);
    }

    private function createAssetDefinition(): AssetDefinition
    {
        return AssetDefinition::query()->create([
            'asset_code' => 'job_urgent',
            'asset_name' => '职位紧急招聘',
            'owner_type' => RcAssetOwnerType::Company,
            'asset_type' => RcAssetType::Count,
            'consume_scene' => 'job_urgent',
            'unit' => '次',
            'default_duration' => 7,
            'status' => RcAssetDefinitionStatus::Enabled,
            'sort' => 10,
        ]);
    }
}
