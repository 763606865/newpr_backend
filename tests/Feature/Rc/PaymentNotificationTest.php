<?php

namespace Tests\Feature\Rc;

use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetType;
use App\Enums\RcBizPlanBillingCycle;
use App\Enums\RcBizPlanProductType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Enums\RcOrderPayChannel;
use App\Enums\RcOrderPayStatus;
use App\Enums\RcOrderStatus;
use App\Enums\RcPaymentStatus;
use App\Models\Rc\AssetDefinition;
use App\Models\Rc\BizPlan;
use App\Models\Rc\Order;
use App\Models\Rc\PaymentTransaction;
use App\Models\User;
use App\Services\RcOrderService;
use App\Services\RcPaymentService;
use App\Services\YansongdaPaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery\MockInterface;
use Tests\TestCase;

class PaymentNotificationTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = $this->mock(YansongdaPaymentGateway::class);
        $this->gateway
            ->shouldReceive('pay')
            ->zeroOrMoreTimes()
            ->andReturn([
                'scene' => 'app',
                'payload' => ['pay_info' => 'test-payment-payload'],
            ]);
    }

    public function test_successful_notification_completes_order_and_grants_entitlement_once(): void
    {
        [$order, $payment] = $this->createPendingPayment();
        $this->mockAlipayCallback($payment, 'TRADE_SUCCESS');

        $this->post('/rc/payments/notify/alipay')
            ->assertOk()
            ->assertSeeText('success');
        $this->post('/rc/payments/notify/alipay')
            ->assertOk()
            ->assertSeeText('success');

        $order->refresh();
        $payment->refresh();

        $this->assertSame(RcOrderPayStatus::Paid->value, $order->pay_status);
        $this->assertSame(RcOrderStatus::Completed->value, $order->order_status);
        $this->assertNull($order->pending_key);
        $this->assertSame(RcPaymentStatus::Succeeded->value, $payment->status);
        $this->assertSame('ALI202600000001', $payment->provider_trade_no);
        $this->assertDatabaseHas('rc_asset_accounts', [
            'owner_type' => RcAssetOwnerType::User->value,
            'owner_id' => $order->payer_id,
            'asset_code' => 'resume_refresh',
            'balance' => 5,
        ]);
        $this->assertDatabaseCount('rc_asset_ledgers', 1);
    }

    public function test_failed_notification_marks_payment_failed_without_completing_order(): void
    {
        [$order, $payment] = $this->createPendingPayment();
        $this->mockAlipayCallback($payment, 'TRADE_CLOSED', times: 1);

        $this->post('/rc/payments/notify/alipay')
            ->assertOk()
            ->assertSeeText('success');

        $this->assertSame(RcPaymentStatus::Failed->value, $payment->refresh()->status);
        $this->assertSame(RcOrderPayStatus::Pending->value, $order->refresh()->pay_status);
        $this->assertSame(RcOrderStatus::PendingPayment->value, $order->order_status);
        $this->assertDatabaseCount('rc_asset_accounts', 0);
        $this->assertDatabaseCount('rc_asset_ledgers', 0);
    }

    public function test_wechat_successful_notification_is_verified_decrypted_and_processed(): void
    {
        [$order] = $this->createPendingPayment(RcOrderPayChannel::Wechat);
        $payment = PaymentTransaction::query()->where('order_id', $order->id)->sole();
        $this->gateway
            ->shouldReceive('callback')
            ->once()
            ->andReturn([
                'id' => 'wechat-notification-id',
                'event_type' => 'TRANSACTION.SUCCESS',
                'resource' => [
                    'out_trade_no' => $payment->payment_no,
                    'transaction_id' => 'WX202600000001',
                    'trade_state' => 'SUCCESS',
                    'amount' => [
                        'payer_total' => 990,
                        'payer_currency' => 'CNY',
                    ],
                ],
            ]);

        $this->postJson('/rc/payments/notify/wechat')
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS');

        $this->assertSame(RcOrderPayStatus::Paid->value, $order->refresh()->pay_status);
        $this->assertSame(RcPaymentStatus::Succeeded->value, $payment->refresh()->status);
        $this->assertDatabaseHas('rc_asset_accounts', [
            'owner_id' => $order->payer_id,
            'asset_code' => 'resume_refresh',
            'balance' => 5,
        ]);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        [$order, $payment] = $this->createPendingPayment();
        $this->gateway
            ->shouldReceive('callback')
            ->once()
            ->andThrow(new InvalidArgumentException('支付宝通知验签失败。'));

        $this->post('/rc/payments/notify/alipay')
            ->assertBadRequest()
            ->assertSeeText('failure');

        $this->assertSame(RcPaymentStatus::Initialized->value, $payment->refresh()->status);
        $this->assertSame(RcOrderPayStatus::Pending->value, $order->refresh()->pay_status);
        $this->assertDatabaseCount('rc_asset_accounts', 0);
    }

    public function test_amount_mismatch_is_rejected(): void
    {
        [$order, $payment] = $this->createPendingPayment();
        $this->mockAlipayCallback($payment, 'TRADE_SUCCESS', '0.01', 1);

        $this->post('/rc/payments/notify/alipay')
            ->assertBadRequest()
            ->assertSeeText('failure');

        $this->assertSame(RcPaymentStatus::Initialized->value, $payment->refresh()->status);
        $this->assertSame(RcOrderPayStatus::Pending->value, $order->refresh()->pay_status);
        $this->assertDatabaseCount('rc_asset_accounts', 0);
    }

    /**
     * @return array{0: Order, 1: PaymentTransaction}
     */
    private function createPendingPayment(
        RcOrderPayChannel $channel = RcOrderPayChannel::Alipay,
    ): array {
        $user = User::factory()->create();
        AssetDefinition::query()->create([
            'asset_code' => 'resume_refresh',
            'asset_name' => '简历刷新',
            'owner_type' => RcAssetOwnerType::User,
            'asset_type' => RcAssetType::Count,
            'unit' => '次',
            'status' => RcAssetDefinitionStatus::Enabled,
        ]);
        $plan = BizPlan::query()->create([
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

        $order = RcOrderService::make()->create($user, $plan, 1)['order'];
        $payment = RcPaymentService::make()->initiate($order, $channel);

        return [$order, $payment];
    }

    private function mockAlipayCallback(
        PaymentTransaction $payment,
        string $tradeStatus,
        ?string $amount = null,
        int $times = 2,
    ): void {
        $this->gateway
            ->shouldReceive('callback')
            ->times($times)
            ->andReturn([
                'out_trade_no' => $payment->payment_no,
                'trade_no' => 'ALI202600000001',
                'trade_status' => $tradeStatus,
                'total_amount' => $amount ?? $payment->amount,
            ]);
    }
}
