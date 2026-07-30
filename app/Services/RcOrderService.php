<?php

namespace App\Services;

use App\Enums\RcAssetOwnerType;
use App\Enums\RcBizPlanProductType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Enums\RcOrderPayChannel;
use App\Enums\RcOrderPayStatus;
use App\Enums\RcOrderStatus;
use App\Models\Company;
use App\Models\Rc\BizPlan;
use App\Models\Rc\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RcOrderService extends Service
{
    public const PAYMENT_TTL_MINUTES = 5;

    /**
     * @return array{order: Order, created: bool}
     */
    public function create(User $buyerUser, BizPlan $plan, int $quantity): array
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('商品数量必须大于零。');
        }

        if ($plan->status !== RcBizPlanStatus::Enabled) {
            throw new InvalidArgumentException('商品不存在或已下架。');
        }

        [$payerType, $payerId] = $this->resolvePayer($buyerUser, $plan);
        $pendingKey = hash('sha256', $payerType->value.':'.$payerId.':'.$plan->plan_code);

        return DB::transaction(function () use (
            $buyerUser,
            $plan,
            $quantity,
            $payerType,
            $payerId,
            $pendingKey,
        ): array {
            $pendingOrder = Order::query()
                ->where('pending_key', $pendingKey)
                ->lockForUpdate()
                ->first();

            if ($pendingOrder instanceof Order && $this->cancelIfExpired($pendingOrder)) {
                $pendingOrder = null;
            }

            if ($pendingOrder instanceof Order) {
                return [
                    'order' => $pendingOrder->load('items'),
                    'created' => false,
                ];
            }

            $amount = number_format((float) $plan->price * $quantity, 2, '.', '');
            $entitlements = $this->entitlementSnapshot($plan, $quantity);
            $order = Order::query()->create([
                'order_no' => 'RC'.now()->format('YmdHis').strtoupper(Str::random(10)),
                'pending_key' => $pendingKey,
                'payer_type' => $payerType->value,
                'payer_id' => $payerId,
                'buyer_user_id' => $buyerUser->id,
                'scene_type' => $this->sceneType($plan),
                'product_code' => $plan->plan_code,
                'product_name' => $plan->plan_name,
                'quantity' => $quantity,
                'original_amount' => $amount,
                'discount_amount' => 0,
                'payable_amount' => $amount,
                'paid_amount' => 0,
                'currency' => 'CNY',
                'pay_channel' => RcOrderPayChannel::Unselected->value,
                'pay_status' => RcOrderPayStatus::Pending->value,
                'order_status' => RcOrderStatus::PendingPayment->value,
                'expired_at' => now()->addMinutes(self::PAYMENT_TTL_MINUTES),
                'extra' => [
                    'biz_plan_id' => $plan->id,
                    'target_side' => $plan->target_side->value,
                ],
            ]);

            $order->items()->create([
                'item_code' => $plan->plan_code,
                'item_name' => $plan->plan_name,
                'item_type' => $this->itemType($plan),
                'unit_price' => $plan->price,
                'quantity' => $quantity,
                'line_amount' => $amount,
                'entitlement_snapshot' => $entitlements,
                'extra' => [
                    'biz_plan_id' => $plan->id,
                    'duration' => $plan->duration,
                ],
            ]);

            return [
                'order' => $order->load('items'),
                'created' => true,
            ];
        });
    }

    public function findAccessibleOrder(User $user, int $orderId): ?Order
    {
        $order = Order::query()->with('items')->find($orderId);

        if (! $order instanceof Order || ! $this->canAccess($user, $order)) {
            return null;
        }

        $this->cancelIfExpired($order);

        return $order->refresh()->load('items');
    }

    public function canAccess(User $user, Order $order): bool
    {
        if ((int) $order->buyer_user_id !== (int) $user->id) {
            return false;
        }

        if ($order->payer_type === RcAssetOwnerType::User->value) {
            return (int) $order->payer_id === (int) $user->id;
        }

        if ($order->payer_type !== RcAssetOwnerType::Company->value) {
            return false;
        }

        return $user->identities()
            ->where('organization_type', 'company')
            ->where('organization_id', $order->payer_id)
            ->exists();
    }

    public function cancelExpiredPendingOrders(): int
    {
        return Order::query()
            ->where('order_status', RcOrderStatus::PendingPayment->value)
            ->where('pay_status', RcOrderPayStatus::Pending->value)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->update([
                'pending_key' => null,
                'order_status' => RcOrderStatus::Canceled->value,
                'canceled_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function cancelIfExpired(Order $order): bool
    {
        if (
            $order->order_status !== RcOrderStatus::PendingPayment->value
            || $order->pay_status !== RcOrderPayStatus::Pending->value
            || $order->expired_at === null
            || $order->expired_at->isFuture()
        ) {
            return false;
        }

        $order->forceFill([
            'pending_key' => null,
            'order_status' => RcOrderStatus::Canceled->value,
            'canceled_at' => now(),
        ])->save();

        return true;
    }

    /**
     * @return array{0: RcAssetOwnerType, 1: int}
     */
    private function resolvePayer(User $user, BizPlan $plan): array
    {
        if ($plan->target_side === RcBizPlanTargetSide::JobSeeker) {
            return [RcAssetOwnerType::User, (int) $user->id];
        }

        if ($plan->target_side !== RcBizPlanTargetSide::Recruiter) {
            throw new InvalidArgumentException('当前商品不支持购买。');
        }

        $company = RcJobService::make()->resolveRecruiterCompany($user);
        if (! $company instanceof Company) {
            throw new InvalidArgumentException('请先切换到已绑定企业的招聘方身份。');
        }

        return [RcAssetOwnerType::Company, (int) $company->id];
    }

    /**
     * @return list<array{asset_code: string, quantity: int, duration_days: int}>
     */
    private function entitlementSnapshot(BizPlan $plan, int $quantity): array
    {
        return collect($plan->quota_rules ?? [])
            ->filter(fn (mixed $rule): bool => is_array($rule) && filled($rule['asset_code'] ?? null))
            ->groupBy('asset_code')
            ->map(fn ($rules, string $assetCode): array => [
                'asset_code' => $assetCode,
                'quantity' => $rules->sum(fn (array $rule): int => (int) ($rule['quantity'] ?? 0)) * $quantity,
                'duration_days' => $rules->max(fn (array $rule): int => (int) ($rule['duration_days'] ?? $plan->duration)),
            ])
            ->filter(fn (array $rule): bool => $rule['quantity'] > 0)
            ->values()
            ->all();
    }

    private function sceneType(BizPlan $plan): int
    {
        if ($plan->target_side === RcBizPlanTargetSide::Recruiter) {
            return match ($plan->product_type) {
                RcBizPlanProductType::ValueAddedItem => 2,
                RcBizPlanProductType::AiTool => 3,
                default => 1,
            };
        }

        return match ($plan->product_type) {
            RcBizPlanProductType::ResumeOptimization,
            RcBizPlanProductType::VipCoaching => 12,
            RcBizPlanProductType::ResumeRefresh => 13,
            RcBizPlanProductType::ResumeExposure => 14,
            default => 11,
        };
    }

    private function itemType(BizPlan $plan): int
    {
        if ($plan->target_side === RcBizPlanTargetSide::JobSeeker) {
            return 4;
        }

        return match ($plan->product_type) {
            RcBizPlanProductType::ValueAddedItem => 2,
            RcBizPlanProductType::AiTool => 3,
            default => 1,
        };
    }
}
