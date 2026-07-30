<?php

namespace App\Services;

use App\Enums\RcOrderPayChannel;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RcPaymentNotificationParser extends Service
{
    /**
     * 使用 Yansongda Pay 完成支付平台通知的验签和解密，并转换为业务统一格式。
     *
     * @return array{
     *     channel: RcOrderPayChannel,
     *     payment_no: string,
     *     successful: bool,
     *     provider_trade_no: string|null,
     *     amount: string,
     *     currency: string,
     *     payload: array<string, mixed>
     * }
     */
    public function parse(Request $request, string $channel): array
    {
        $payChannel = match ($channel) {
            'wechat' => RcOrderPayChannel::Wechat,
            'alipay' => RcOrderPayChannel::Alipay,
            default => throw new InvalidArgumentException('不支持的支付通知渠道。'),
        };

        $payload = app(YansongdaPaymentGateway::class)->callback($request, $payChannel);

        return match ($payChannel) {
            RcOrderPayChannel::Wechat => $this->normalizeWechat($payload),
            RcOrderPayChannel::Alipay => $this->normalizeAlipay($payload),
            default => throw new InvalidArgumentException('不支持的支付通知渠道。'),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     channel: RcOrderPayChannel,
     *     payment_no: string,
     *     successful: bool,
     *     provider_trade_no: string|null,
     *     amount: string,
     *     currency: string,
     *     payload: array<string, mixed>
     * }
     */
    private function normalizeWechat(array $payload): array
    {
        $transaction = is_array($payload['resource'] ?? null)
            ? $payload['resource']
            : $payload;
        $amount = $transaction['amount'] ?? null;
        if (! is_array($amount)) {
            throw new InvalidArgumentException('微信支付通知缺少金额信息。');
        }

        $wechatConfig = (array) config('pay.wechat.default');
        if (
            filled($wechatConfig['mch_id'] ?? null)
            && ! hash_equals((string) $wechatConfig['mch_id'], (string) ($transaction['mchid'] ?? ''))
        ) {
            throw new InvalidArgumentException('微信支付通知商户号不匹配。');
        }

        $configuredAppIds = collect([
            $wechatConfig['app_id'] ?? null,
            $wechatConfig['mini_app_id'] ?? null,
            $wechatConfig['mp_app_id'] ?? null,
        ])->filter()->map(fn (mixed $appId): string => (string) $appId);
        if (
            $configuredAppIds->isNotEmpty()
            && ! $configuredAppIds->contains((string) ($transaction['appid'] ?? ''))
        ) {
            throw new InvalidArgumentException('微信支付通知应用 ID 不匹配。');
        }

        return [
            'channel' => RcOrderPayChannel::Wechat,
            'payment_no' => (string) ($transaction['out_trade_no'] ?? ''),
            'successful' => ($transaction['trade_state'] ?? null) === 'SUCCESS',
            'provider_trade_no' => filled($transaction['transaction_id'] ?? null)
                ? (string) $transaction['transaction_id']
                : null,
            'amount' => number_format(((int) ($amount['payer_total'] ?? $amount['total'] ?? 0)) / 100, 2, '.', ''),
            'currency' => (string) ($amount['payer_currency'] ?? $amount['currency'] ?? 'CNY'),
            'payload' => $payload,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     channel: RcOrderPayChannel,
     *     payment_no: string,
     *     successful: bool,
     *     provider_trade_no: string|null,
     *     amount: string,
     *     currency: string,
     *     payload: array<string, mixed>
     * }
     */
    private function normalizeAlipay(array $payload): array
    {
        $alipayConfig = (array) config('pay.alipay.default');
        if (
            filled($alipayConfig['app_id'] ?? null)
            && ! hash_equals((string) $alipayConfig['app_id'], (string) ($payload['app_id'] ?? ''))
        ) {
            throw new InvalidArgumentException('支付宝通知应用 ID 不匹配。');
        }
        if (
            filled($alipayConfig['seller_id'] ?? null)
            && ! hash_equals((string) $alipayConfig['seller_id'], (string) ($payload['seller_id'] ?? ''))
        ) {
            throw new InvalidArgumentException('支付宝通知商户 ID 不匹配。');
        }

        return [
            'channel' => RcOrderPayChannel::Alipay,
            'payment_no' => (string) ($payload['out_trade_no'] ?? ''),
            'successful' => in_array($payload['trade_status'] ?? null, ['TRADE_SUCCESS', 'TRADE_FINISHED'], true),
            'provider_trade_no' => filled($payload['trade_no'] ?? null)
                ? (string) $payload['trade_no']
                : null,
            'amount' => number_format((float) ($payload['total_amount'] ?? 0), 2, '.', ''),
            'currency' => 'CNY',
            'payload' => $payload,
        ];
    }
}
