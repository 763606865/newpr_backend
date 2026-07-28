<?php

namespace Tests\Feature\Filament;

use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetSourceType;
use App\Enums\RcAssetType;
use App\Enums\RcBizPlanBillingCycle;
use App\Enums\RcBizPlanProductType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Filament\Resources\Users\Pages\UserCommerce;
use App\Models\Rc\AssetDefinition;
use App\Models\Rc\BizPlan;
use App\Models\User;
use App\Services\UserRcCommerceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class UserCommercePageTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    public function test_authorized_admin_can_view_user_commerce_page(): void
    {
        $this->actingAsFilamentAdmin($this->permissions());
        $user = User::factory()->create(['name' => '求职用户张三']);

        Livewire::test(UserCommerce::class, ['record' => $user->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('用户权益与订单')
            ->assertSee('求职用户张三')
            ->assertSee('权益余额')
            ->assertSee('订单列表')
            ->assertSee('权益流水');
    }

    public function test_admin_can_increase_a_user_asset_balance_with_a_ledger(): void
    {
        $admin = $this->actingAsFilamentAdmin($this->permissions());
        $user = User::factory()->create();
        $definition = $this->createAssetDefinition('resume_refresh', '简历刷新');

        UserRcCommerceService::make()->increaseAsset(
            $user,
            $definition,
            3,
            '测试人工增加',
            $admin->id,
        );

        $this->assertDatabaseHas('rc_asset_accounts', [
            'owner_type' => RcAssetOwnerType::User->value,
            'owner_id' => $user->id,
            'asset_code' => 'resume_refresh',
            'balance' => 3,
        ]);
        $this->assertDatabaseHas('rc_asset_ledgers', [
            'owner_id' => $user->id,
            'asset_code' => 'resume_refresh',
            'delta' => 3,
            'balance_after' => 3,
            'source_type' => RcAssetSourceType::Manual->value,
            'source_id' => $admin->id,
            'remark' => '测试人工增加',
        ]);
    }

    public function test_admin_can_add_a_job_seeker_product_and_grant_its_assets(): void
    {
        $admin = $this->actingAsFilamentAdmin($this->permissions());
        $user = User::factory()->create();
        $this->createAssetDefinition('resume_exposure', '简历曝光');
        $plan = BizPlan::query()->create([
            'plan_name' => '简历曝光卡',
            'plan_code' => 'resume_exposure_card',
            'price' => 29,
            'duration' => 30,
            'target_side' => RcBizPlanTargetSide::JobSeeker,
            'product_type' => RcBizPlanProductType::ResumeExposure,
            'billing_cycle' => RcBizPlanBillingCycle::OneTime,
            'quota_rules' => [
                ['asset_code' => 'resume_exposure', 'quantity' => 5],
            ],
            'status' => RcBizPlanStatus::Enabled,
        ]);

        $order = UserRcCommerceService::make()->addProduct(
            $user,
            $plan,
            2,
            '运营赠送商品',
            $admin->id,
        );

        $this->assertDatabaseHas('rc_orders', [
            'id' => $order->id,
            'payer_type' => RcAssetOwnerType::User->value,
            'payer_id' => $user->id,
            'buyer_user_id' => $user->id,
            'scene_type' => 14,
            'product_code' => 'resume_exposure_card',
            'quantity' => 2,
            'paid_amount' => 0,
            'pay_status' => 1,
            'order_status' => 1,
        ]);
        $this->assertDatabaseHas('rc_order_items', [
            'order_id' => $order->id,
            'item_code' => 'resume_exposure_card',
            'item_type' => 4,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('rc_asset_accounts', [
            'owner_type' => RcAssetOwnerType::User->value,
            'owner_id' => $user->id,
            'asset_code' => 'resume_exposure',
            'balance' => 10,
        ]);
    }

    /**
     * @return list<string>
     */
    private function permissions(): array
    {
        return [
            'ViewAny:User',
            'View:User',
            'Update:User',
        ];
    }

    private function createAssetDefinition(string $code, string $name): AssetDefinition
    {
        return AssetDefinition::query()->create([
            'asset_code' => $code,
            'asset_name' => $name,
            'owner_type' => RcAssetOwnerType::User,
            'asset_type' => RcAssetType::Count,
            'unit' => '次',
            'default_duration' => 0,
            'status' => RcAssetDefinitionStatus::Enabled,
        ]);
    }
}
