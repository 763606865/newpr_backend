<?php

namespace App\Rc\Controllers;

use App\Enums\RcOrderPayChannel;
use App\Models\Rc\BizPlan;
use App\Rc\Requests\OrderPayRequest;
use App\Rc\Requests\OrderStoreRequest;
use App\Resources\Rc\OrderResource;
use App\Resources\Rc\PaymentTransactionResource;
use App\Services\RcOrderService;
use App\Services\RcPaymentNotificationParser;
use App\Services\RcPaymentNotificationService;
use App\Services\RcPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class OrderController extends Controller
{
    /**
     * 接收微信或支付宝的异步支付结果通知。
     *
     * 该接口不使用用户登录鉴权，而是通过支付平台签名验证通知来源。
     * 通知处理具备幂等性，支付成功后会完成订单并发放商品权益。
     *
     * POST /rc/payments/notify/{channel}
     */
    public function notify(Request $request, string $channel): JsonResponse|Response
    {
        try {
            $notification = RcPaymentNotificationParser::make()->parse($request, $channel);
            RcPaymentNotificationService::make()->handle($notification);
        } catch (Throwable $exception) {
            Log::warning('RC 支付通知处理失败', [
                'channel' => $channel,
                'message' => $exception->getMessage(),
            ]);

            return $this->notificationResponse($channel, false);
        }

        return $this->notificationResponse($channel, true);
    }

    /**
     * 创建 RC 商品订单。
     *
     * 同一付款主体购买同一商品时，如果存在五分钟内有效的待支付订单，
     * 则直接返回原订单，不重复创建。
     *
     * POST /rc/orders
     *
     * @throws \Exception
     */
    public function store(OrderStoreRequest $request): JsonResponse
    {
        $plan = BizPlan::query()->findOrFail((int) $request->validated('biz_plan_id'));

        try {
            $result = RcOrderService::make()->create(
                $this->user(),
                $plan,
                (int) $request->validated('quantity', 1),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'created' => $result['created'],
            'order' => (new OrderResource($result['order']))->resolve($request),
        ]);
    }

    /**
     * 查询当前用户有权访问的订单详情。
     *
     * 查询时会同步检查订单是否已经超过支付期限，超时的待支付订单
     * 将被自动取消。
     *
     * GET /rc/orders/{id}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = RcOrderService::make()->findAccessibleOrder($this->user(), $id);

        if ($order === null) {
            return $this->error('订单不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new OrderResource($order))->resolve($request));
    }

    /**
     * 为待支付订单发起支付。
     *
     * 支持微信支付（wechat）和支付宝（alipay）。相同订单、相同渠道
     * 已存在有效支付流水时，将复用原支付流水。
     *
     * POST /rc/orders/{id}/pay
     *
     * @throws \Exception
     */
    public function pay(OrderPayRequest $request, int $id): JsonResponse
    {
        $order = RcOrderService::make()->findAccessibleOrder($this->user(), $id);

        if ($order === null) {
            return $this->error('订单不存在。', Response::HTTP_NOT_FOUND);
        }

        $channel = match ($request->validated('pay_channel')) {
            'wechat' => RcOrderPayChannel::Wechat,
            'alipay' => RcOrderPayChannel::Alipay,
        };

        try {
            $payment = RcPaymentService::make()->initiate(
                order: $order,
                channel: $channel,
                scene: (string) $request->validated(
                    'pay_scene',
                    config($channel === RcOrderPayChannel::Wechat
                        ? 'pay.defaults.wechat_scene'
                        : 'pay.defaults.alipay_scene'),
                ),
                openid: $request->validated('openid'),
                clientIp: $request->ip(),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'order' => (new OrderResource($order->refresh()->load('items')))->resolve($request),
            'payment' => (new PaymentTransactionResource($payment))->resolve($request),
        ]);
    }

    private function notificationResponse(string $channel, bool $successful): JsonResponse|Response
    {
        if ($channel === 'wechat') {
            return response()->json(
                $successful
                    ? ['code' => 'SUCCESS', 'message' => '成功']
                    : ['code' => 'FAIL', 'message' => '失败'],
                $successful ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST,
            );
        }

        return response(
            $successful ? 'success' : 'failure',
            $successful ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST,
        )->header('Content-Type', 'text/plain');
    }
}
