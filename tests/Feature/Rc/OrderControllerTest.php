<?php

namespace Tests\Feature\Rc;

use App\Enums\RcBizPlanBillingCycle;
use App\Enums\RcBizPlanProductType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Enums\RcOrderPayChannel;
use App\Enums\RcOrderStatus;
use App\Enums\RcPaymentStatus;
use App\Models\Rc\BizPlan;
use App\Models\Rc\Order;
use App\Models\Rc\PaymentTransaction;
use App\Models\User;
use App\Services\YansongdaPaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        $this->mock(
            YansongdaPaymentGateway::class,
            fn (MockInterface $mock) => $mock
                ->shouldReceive('pay')
                ->zeroOrMoreTimes()
                ->andReturn([
                    'scene' => 'app',
                    'payload' => ['pay_info' => 'test-payment-payload'],
                ]),
        );
    }

    public function test_same_buyer_and_product_reuses_unpaid_order(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan();

        $firstResponse = $this->actingAs($user, 'rc')
            ->postJson('/rc/orders', [
                'biz_plan_id' => $plan->id,
                'quantity' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.created', true);

        $firstOrderId = $firstResponse->json('data.order.id');

        $this->actingAs($user, 'rc')
            ->postJson('/rc/orders', [
                'biz_plan_id' => $plan->id,
                'quantity' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.created', false)
            ->assertJsonPath('data.order.id', $firstOrderId)
            ->assertJsonPath('data.order.quantity', 2);

        $this->assertDatabaseCount('rc_orders', 1);
        $this->assertDatabaseCount('rc_order_items', 1);
    }

    public function test_expired_unpaid_order_is_canceled_before_new_order_is_created(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan();

        $oldOrderId = $this->actingAs($user, 'rc')
            ->postJson('/rc/orders', ['biz_plan_id' => $plan->id])
            ->assertOk()
            ->json('data.order.id');

        Order::query()->whereKey($oldOrderId)->update(['expired_at' => now()->subSecond()]);

        $newOrderId = $this->actingAs($user, 'rc')
            ->postJson('/rc/orders', ['biz_plan_id' => $plan->id])
            ->assertOk()
            ->assertJsonPath('data.created', true)
            ->json('data.order.id');

        $this->assertNotSame($oldOrderId, $newOrderId);
        $this->assertDatabaseHas('rc_orders', [
            'id' => $oldOrderId,
            'pending_key' => null,
            'order_status' => RcOrderStatus::Canceled->value,
        ]);
        $this->assertDatabaseCount('rc_orders', 2);
    }

    public function test_pay_endpoint_selects_channel_and_reuses_active_payment_transaction(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan();
        $orderId = $this->actingAs($user, 'rc')
            ->postJson('/rc/orders', ['biz_plan_id' => $plan->id])
            ->json('data.order.id');

        $firstResponse = $this->actingAs($user, 'rc')
            ->postJson("/rc/orders/{$orderId}/pay", ['pay_channel' => 'wechat'])
            ->assertOk()
            ->assertJsonPath('data.order.pay_channel', RcOrderPayChannel::Wechat->value)
            ->assertJsonPath('data.payment.channel_name', RcOrderPayChannel::Wechat->name)
            ->assertJsonPath('data.payment.gateway_payload.scene', 'app')
            ->assertJsonPath('data.payment.gateway_payload.payload.pay_info', 'test-payment-payload');

        $paymentNo = $firstResponse->json('data.payment.payment_no');

        $this->actingAs($user, 'rc')
            ->postJson("/rc/orders/{$orderId}/pay", ['pay_channel' => 'wechat'])
            ->assertOk()
            ->assertJsonPath('data.payment.payment_no', $paymentNo);

        $this->assertDatabaseCount('rc_payment_transactions', 1);
    }

    public function test_wechat_mini_payment_requires_openid(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan();
        $orderId = $this->actingAs($user, 'rc')
            ->postJson('/rc/orders', ['biz_plan_id' => $plan->id])
            ->json('data.order.id');

        $this->actingAs($user, 'rc')
            ->postJson("/rc/orders/{$orderId}/pay", [
                'pay_channel' => 'wechat',
                'pay_scene' => 'mini',
            ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '微信小程序或公众号支付必须提供 openid。');

        $this->assertDatabaseCount('rc_payment_transactions', 0);
    }

    public function test_switching_payment_channel_closes_previous_payment_transaction(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan();
        $orderId = $this->actingAs($user, 'rc')
            ->postJson('/rc/orders', ['biz_plan_id' => $plan->id])
            ->json('data.order.id');

        $this->actingAs($user, 'rc')
            ->postJson("/rc/orders/{$orderId}/pay", ['pay_channel' => 'wechat'])
            ->assertOk();
        $this->actingAs($user, 'rc')
            ->postJson("/rc/orders/{$orderId}/pay", ['pay_channel' => 'alipay'])
            ->assertOk()
            ->assertJsonPath('data.payment.channel_name', RcOrderPayChannel::Alipay->name);

        $this->assertSame(
            RcPaymentStatus::Closed->value,
            PaymentTransaction::query()
                ->where('channel', RcOrderPayChannel::Wechat->value)
                ->value('status'),
        );
        $this->assertDatabaseCount('rc_payment_transactions', 2);
    }

    public function test_other_user_cannot_pay_the_order(): void
    {
        $buyer = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createPlan();
        $orderId = $this->actingAs($buyer, 'rc')
            ->postJson('/rc/orders', ['biz_plan_id' => $plan->id])
            ->json('data.order.id');

        $this->actingAs($otherUser, 'rc')
            ->postJson("/rc/orders/{$orderId}/pay", ['pay_channel' => 'alipay'])
            ->assertOk()
            ->assertJsonPath('code', 404);

        $this->assertDatabaseCount('rc_payment_transactions', 0);
    }

    private function createPlan(): BizPlan
    {
        return BizPlan::query()->create([
            'plan_name' => '简历刷新卡',
            'plan_code' => 'resume_refresh_card',
            'price' => 9.90,
            'duration' => 30,
            'target_side' => RcBizPlanTargetSide::JobSeeker,
            'product_type' => RcBizPlanProductType::ResumeRefresh,
            'billing_cycle' => RcBizPlanBillingCycle::OneTime,
            'quota_rules' => [
                ['asset_code' => 'resume_refresh', 'quantity' => 5],
            ],
            'status' => RcBizPlanStatus::Enabled,
        ]);
    }
}
