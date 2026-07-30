<?php

namespace App\Services;

use App\Enums\RcOrderPayChannel;
use App\Models\Rc\Order;
use App\Models\Rc\PaymentTransaction;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Yansongda\Artful\Rocket;
use Yansongda\Pay\Pay;
use Yansongda\Supports\Collection;

class YansongdaPaymentGateway
{
    /**
     * @return array<string, mixed>
     */
    public function pay(
        Order $order,
        PaymentTransaction $payment,
        RcOrderPayChannel $channel,
        string $scene,
        ?string $openid,
        ?string $clientIp,
    ): array {
        $this->configure();

        return match ($channel) {
            RcOrderPayChannel::Wechat => $this->payWithWechat(
                $order,
                $payment,
                $scene,
                $openid,
                $clientIp,
            ),
            RcOrderPayChannel::Alipay => $this->payWithAlipay($order, $payment, $scene),
            default => throw new InvalidArgumentException('不支持的支付渠道。'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function callback(Request $request, RcOrderPayChannel $channel): array
    {
        $this->configure();

        $result = match ($channel) {
            RcOrderPayChannel::Wechat => Pay::wechat()->callback([
                'body' => $request->getContent(),
                'headers' => $request->headers->all(),
            ]),
            RcOrderPayChannel::Alipay => Pay::alipay()->callback($request->post()),
            default => throw new InvalidArgumentException('不支持的支付通知渠道。'),
        };

        return $this->toArray($result);
    }

    /**
     * @return array<string, mixed>
     */
    private function payWithWechat(
        Order $order,
        PaymentTransaction $payment,
        string $scene,
        ?string $openid,
        ?string $clientIp,
    ): array {
        if (! in_array($scene, ['app', 'mini', 'mp', 'h5', 'scan'], true)) {
            throw new InvalidArgumentException('不支持的微信支付场景。');
        }

        $this->assertWechatConfigured($scene);

        if (in_array($scene, ['mini', 'mp'], true) && blank($openid)) {
            throw new InvalidArgumentException('微信小程序或公众号支付必须提供 openid。');
        }

        $params = [
            'out_trade_no' => $payment->payment_no,
            'description' => $order->product_name,
            'amount' => [
                'total' => (int) round((float) $payment->amount * 100),
                'currency' => $payment->currency,
            ],
            'time_expire' => $payment->expired_at?->toIso8601String(),
        ];

        if (in_array($scene, ['mini', 'mp'], true)) {
            $params['payer'] = ['openid' => $openid];
        }

        if ($scene === 'h5') {
            $params['scene_info'] = [
                'payer_client_ip' => $clientIp ?: '127.0.0.1',
                'h5_info' => ['type' => 'Wap'],
            ];
        }

        $result = Pay::wechat()->{$scene}($params);

        return [
            'scene' => $scene,
            'payload' => $this->toArray($result),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payWithAlipay(
        Order $order,
        PaymentTransaction $payment,
        string $scene,
    ): array {
        if (! in_array($scene, ['app', 'h5', 'web', 'scan'], true)) {
            throw new InvalidArgumentException('不支持的支付宝支付场景。');
        }

        $this->assertAlipayConfigured();

        $result = Pay::alipay()->{$scene}([
            'out_trade_no' => $payment->payment_no,
            'total_amount' => $payment->amount,
            'subject' => $order->product_name,
            'time_expire' => $payment->expired_at?->format('Y-m-d H:i:s'),
        ]);

        return [
            'scene' => $scene,
            'payload' => $this->toArray($result),
        ];
    }

    private function configure(): void
    {
        Pay::clear();
        Pay::config((array) config('pay'));
    }

    private function assertWechatConfigured(string $scene): void
    {
        $config = (array) config('pay.wechat.default');
        $appIdKey = match ($scene) {
            'mini' => 'mini_app_id',
            'mp' => 'mp_app_id',
            default => 'app_id',
        };

        foreach (['mch_id', 'mch_secret_key', 'mch_secret_cert', $appIdKey] as $key) {
            if (blank($config[$key] ?? null)) {
                throw new InvalidArgumentException("微信支付配置缺少 {$key}。");
            }
        }
    }

    private function assertAlipayConfigured(): void
    {
        $config = (array) config('pay.alipay.default');

        foreach ([
            'app_id',
            'app_secret_cert',
            'app_public_cert_path',
            'alipay_public_cert_path',
            'alipay_root_cert_path',
        ] as $key) {
            if (blank($config[$key] ?? null)) {
                throw new InvalidArgumentException("支付宝配置缺少 {$key}。");
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(mixed $result): array
    {
        if ($result instanceof Rocket) {
            return $this->toArray($result->getDestination());
        }

        if ($result instanceof Collection) {
            return $result->all();
        }

        if ($result instanceof MessageInterface) {
            return [
                'body' => (string) $result->getBody(),
                'content_type' => $result->getHeaderLine('Content-Type'),
            ];
        }

        if (is_array($result)) {
            return $result;
        }

        if ($result instanceof \JsonSerializable) {
            return (array) $result->jsonSerialize();
        }

        throw new InvalidArgumentException('支付平台返回了无法识别的响应。');
    }
}
