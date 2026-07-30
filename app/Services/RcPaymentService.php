<?php

namespace App\Services;

use App\Enums\RcOrderPayChannel;
use App\Enums\RcOrderPayStatus;
use App\Enums\RcOrderStatus;
use App\Enums\RcPaymentStatus;
use App\Models\Rc\Order;
use App\Models\Rc\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class RcPaymentService extends Service
{
    public function initiate(
        Order $order,
        RcOrderPayChannel $channel,
        ?string $scene = null,
        ?string $openid = null,
        ?string $clientIp = null,
    ): PaymentTransaction {
        if ($channel === RcOrderPayChannel::Unselected) {
            throw new InvalidArgumentException('请选择微信或支付宝。');
        }

        $scene = $scene ?: (string) config(
            $channel === RcOrderPayChannel::Wechat
                ? 'pay.defaults.wechat_scene'
                : 'pay.defaults.alipay_scene',
        );
        $supportedScenes = $channel === RcOrderPayChannel::Wechat
            ? ['app', 'mini', 'mp', 'h5', 'scan']
            : ['app', 'h5', 'web', 'scan'];

        if (! in_array($scene, $supportedScenes, true)) {
            throw new InvalidArgumentException('当前支付渠道不支持所选支付场景。');
        }

        if ($channel === RcOrderPayChannel::Wechat && in_array($scene, ['mini', 'mp'], true) && blank($openid)) {
            throw new InvalidArgumentException('微信小程序或公众号支付必须提供 openid。');
        }

        $payment = DB::transaction(function () use ($order, $channel, $scene): ?PaymentTransaction {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (RcOrderService::make()->cancelIfExpired($lockedOrder)) {
                return null;
            }

            if (
                $lockedOrder->order_status !== RcOrderStatus::PendingPayment->value
                || $lockedOrder->pay_status !== RcOrderPayStatus::Pending->value
            ) {
                throw new InvalidArgumentException('当前订单状态不允许支付。');
            }

            $existingTransaction = PaymentTransaction::query()
                ->where('order_id', $lockedOrder->id)
                ->where('channel', $channel->value)
                ->where('status', RcPaymentStatus::Initialized->value)
                ->where('expired_at', '>', now())
                ->latest('id')
                ->first();

            if (
                $existingTransaction instanceof PaymentTransaction
                && ($existingTransaction->request_payload['scene'] ?? null) === $scene
            ) {
                return $existingTransaction;
            }

            PaymentTransaction::query()
                ->where('order_id', $lockedOrder->id)
                ->where('status', RcPaymentStatus::Initialized->value)
                ->update([
                    'status' => RcPaymentStatus::Closed->value,
                    'updated_at' => now(),
                ]);

            $lockedOrder->forceFill(['pay_channel' => $channel->value])->save();

            $paymentNo = 'RCPAY'.now()->format('YmdHis').strtoupper(Str::random(10));

            return PaymentTransaction::query()->create([
                'order_id' => $lockedOrder->id,
                'payment_no' => $paymentNo,
                'channel' => $channel->value,
                'status' => RcPaymentStatus::Initialized->value,
                'amount' => $lockedOrder->payable_amount,
                'currency' => $lockedOrder->currency,
                'request_payload' => [
                    'out_trade_no' => $paymentNo,
                    'order_no' => $lockedOrder->order_no,
                    'subject' => $lockedOrder->product_name,
                    'scene' => $scene,
                ],
                'expired_at' => $lockedOrder->expired_at,
            ]);
        });

        if (! $payment instanceof PaymentTransaction) {
            throw new InvalidArgumentException('订单已超时取消，请重新下单。');
        }

        if (is_array($payment->response_payload) && $payment->response_payload !== []) {
            return $payment;
        }

        try {
            $gatewayPayload = app(YansongdaPaymentGateway::class)->pay(
                order: $order,
                payment: $payment,
                channel: $channel,
                scene: $scene,
                openid: $openid,
                clientIp: $clientIp,
            );
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('支付平台下单失败', [
                'payment_no' => $payment->payment_no,
                'channel' => $channel->name,
                'scene' => $scene,
                'message' => $exception->getMessage(),
            ]);

            throw new InvalidArgumentException('支付平台下单失败，请稍后重试。', previous: $exception);
        }

        $payment->forceFill([
            'request_payload' => array_merge($payment->request_payload ?? [], [
                'gateway_requested_at' => now()->toDateTimeString(),
            ]),
            'response_payload' => $gatewayPayload,
        ])->save();

        return $payment->refresh();
    }
}
