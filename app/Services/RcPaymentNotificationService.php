<?php

namespace App\Services;

use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetSourceType;
use App\Enums\RcOrderPayChannel;
use App\Enums\RcOrderPayStatus;
use App\Enums\RcOrderStatus;
use App\Enums\RcPaymentStatus;
use App\Models\Rc\AssetDefinition;
use App\Models\Rc\Order;
use App\Models\Rc\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RcPaymentNotificationService extends Service
{
    /**
     * @param  array{
     *     channel: RcOrderPayChannel,
     *     payment_no: string,
     *     successful: bool,
     *     provider_trade_no: string|null,
     *     amount: string,
     *     currency: string,
     *     payload: array<string, mixed>
     * }  $notification
     */
    public function handle(array $notification): void
    {
        DB::transaction(function () use ($notification): void {
            $payment = PaymentTransaction::query()
                ->where('payment_no', $notification['payment_no'])
                ->lockForUpdate()
                ->first();

            if (! $payment instanceof PaymentTransaction) {
                throw new InvalidArgumentException('支付流水不存在。');
            }

            if ($payment->channel !== $notification['channel']->value) {
                throw new InvalidArgumentException('支付通知渠道与支付流水不一致。');
            }

            $order = Order::query()->lockForUpdate()->find($payment->order_id);
            if (! $order instanceof Order) {
                throw new InvalidArgumentException('支付流水关联订单不存在。');
            }

            $this->assertAmountAndCurrency($payment, $notification['amount'], $notification['currency']);

            if (! $notification['successful']) {
                if ($payment->status !== RcPaymentStatus::Succeeded->value) {
                    $payment->forceFill([
                        'status' => RcPaymentStatus::Failed->value,
                        'provider_trade_no' => $notification['provider_trade_no'],
                        'response_payload' => $notification['payload'],
                    ])->save();
                }

                return;
            }

            if ($payment->status === RcPaymentStatus::Succeeded->value) {
                return;
            }

            if ($order->pay_status === RcOrderPayStatus::Paid->value) {
                throw new InvalidArgumentException('订单已经由其他支付流水完成支付。');
            }

            $paidAt = now();
            $payment->forceFill([
                'status' => RcPaymentStatus::Succeeded->value,
                'provider_trade_no' => $notification['provider_trade_no'],
                'response_payload' => $notification['payload'],
                'paid_at' => $paidAt,
            ])->save();

            $order->forceFill([
                'pending_key' => null,
                'pay_channel' => $payment->channel,
                'pay_status' => RcOrderPayStatus::Paid->value,
                'order_status' => RcOrderStatus::Completed->value,
                'paid_amount' => $payment->amount,
                'paid_at' => $paidAt,
                'canceled_at' => null,
            ])->save();

            $this->grantOrderEntitlements($order);
        });
    }

    private function assertAmountAndCurrency(
        PaymentTransaction $payment,
        string $amount,
        string $currency,
    ): void {
        if (
            number_format((float) $payment->amount, 2, '.', '') !== number_format((float) $amount, 2, '.', '')
            || strtoupper($payment->currency) !== strtoupper($currency)
        ) {
            throw new InvalidArgumentException('支付通知金额或币种与支付流水不一致。');
        }
    }

    private function grantOrderEntitlements(Order $order): void
    {
        $ownerType = RcAssetOwnerType::tryFrom($order->payer_type);
        if (! $ownerType instanceof RcAssetOwnerType || $ownerType === RcAssetOwnerType::Universal) {
            throw new InvalidArgumentException('订单付款主体类型无效。');
        }

        $rules = $order->items()
            ->get()
            ->flatMap(fn ($item) => $item->entitlement_snapshot ?? [])
            ->filter(fn (mixed $rule): bool => is_array($rule) && filled($rule['asset_code'] ?? null))
            ->groupBy('asset_code')
            ->map(fn ($items, string $assetCode): array => [
                'asset_code' => $assetCode,
                'quantity' => $items->sum(fn (array $rule): int => (int) ($rule['quantity'] ?? 0)),
            ])
            ->filter(fn (array $rule): bool => $rule['quantity'] > 0)
            ->values();

        if ($rules->isEmpty()) {
            throw new InvalidArgumentException('订单没有可发放的权益。');
        }

        $definitions = AssetDefinition::query()
            ->whereIn('asset_code', $rules->pluck('asset_code'))
            ->get()
            ->keyBy('asset_code');

        if ($definitions->count() !== $rules->count()) {
            throw new InvalidArgumentException('订单包含不存在的权益定义。');
        }

        foreach ($rules as $rule) {
            $definition = $definitions->get($rule['asset_code']);

            RcAssetService::make()->grantOnce(
                ownerType: $ownerType,
                ownerId: (int) $order->payer_id,
                assetCode: $definition->asset_code,
                assetName: $definition->asset_name,
                quantity: (int) $rule['quantity'],
                sourceType: RcAssetSourceType::Order,
                sourceId: (int) $order->id,
                bizNo: 'order_paid:'.$order->id.':'.$definition->asset_code,
                remark: '购买商品：'.$order->product_name,
            );
        }
    }
}
