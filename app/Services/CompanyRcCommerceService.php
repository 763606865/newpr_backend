<?php

namespace App\Services;

use App\Enums\RcAssetDefinitionStatus;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetSourceType;
use App\Enums\RcBizPlanProductType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Models\Company;
use App\Models\Rc\AssetDefinition;
use App\Models\Rc\BizPlan;
use App\Models\Rc\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CompanyRcCommerceService extends Service
{
    public function increaseAsset(
        Company $company,
        AssetDefinition $definition,
        int $quantity,
        ?string $remark,
        ?int $adminId,
    ): void {
        if ($quantity < 1) {
            throw new InvalidArgumentException('增加数量必须大于零。');
        }

        if (
            $definition->status !== RcAssetDefinitionStatus::Enabled
            || ! in_array($definition->owner_type, [RcAssetOwnerType::Universal, RcAssetOwnerType::Company], true)
        ) {
            throw new InvalidArgumentException('只能增加已启用且适用于企业的权益。');
        }

        RcAssetService::make()->grantOnce(
            ownerType: RcAssetOwnerType::Company,
            ownerId: (int) $company->id,
            assetCode: $definition->asset_code,
            assetName: $definition->asset_name,
            quantity: $quantity,
            sourceType: RcAssetSourceType::Manual,
            sourceId: $adminId,
            bizNo: 'company_manual_asset:'.$company->id.':'.Str::uuid(),
            remark: filled($remark) ? $remark : '运营后台人工增加权益',
        );
    }

    public function addProduct(
        Company $company,
        BizPlan $plan,
        int $quantity,
        ?string $remark,
        ?int $adminId,
    ): Order {
        if ($quantity < 1) {
            throw new InvalidArgumentException('商品数量必须大于零。');
        }

        if (
            $plan->status !== RcBizPlanStatus::Enabled
            || $plan->target_side !== RcBizPlanTargetSide::Recruiter
        ) {
            throw new InvalidArgumentException('只能给企业增加已启用的招聘方商品。');
        }

        return DB::transaction(function () use ($company, $plan, $quantity, $remark, $adminId): Order {
            $quotaRules = collect($plan->quota_rules ?? [])
                ->filter(fn (mixed $rule): bool => is_array($rule) && filled($rule['asset_code'] ?? null))
                ->groupBy('asset_code')
                ->map(fn ($rules, string $assetCode): array => [
                    'asset_code' => $assetCode,
                    'quantity' => $rules->sum(fn (array $rule): int => (int) ($rule['quantity'] ?? 0)) * $quantity,
                ])
                ->filter(fn (array $rule): bool => $rule['quantity'] > 0)
                ->values();

            if ($quotaRules->isEmpty()) {
                throw new InvalidArgumentException('该商品尚未配置可发放的权益规则。');
            }

            $definitions = AssetDefinition::query()
                ->whereIn('asset_code', $quotaRules->pluck('asset_code'))
                ->whereIn('owner_type', [
                    RcAssetOwnerType::Universal->value,
                    RcAssetOwnerType::Company->value,
                ])
                ->where('status', RcAssetDefinitionStatus::Enabled->value)
                ->get()
                ->keyBy('asset_code');

            if ($definitions->count() !== $quotaRules->count()) {
                throw new InvalidArgumentException('商品包含不存在、已停用或非企业类型的权益。');
            }

            $order = Order::query()->create([
                'order_no' => 'RCADM'.now()->format('YmdHis').strtoupper(Str::random(8)),
                'payer_type' => RcAssetOwnerType::Company->value,
                'payer_id' => $company->id,
                'scene_type' => $this->sceneType($plan),
                'product_code' => $plan->plan_code,
                'product_name' => $plan->plan_name,
                'quantity' => $quantity,
                'original_amount' => 0,
                'discount_amount' => 0,
                'payable_amount' => 0,
                'paid_amount' => 0,
                'currency' => 'CNY',
                'pay_channel' => 0,
                'pay_status' => 1,
                'order_status' => 1,
                'paid_at' => now(),
                'extra' => [
                    'source' => 'admin_grant',
                    'admin_id' => $adminId,
                    'remark' => $remark,
                ],
            ]);

            $order->items()->create([
                'item_code' => $plan->plan_code,
                'item_name' => $plan->plan_name,
                'item_type' => $this->itemType($plan),
                'unit_price' => 0,
                'quantity' => $quantity,
                'line_amount' => 0,
                'entitlement_snapshot' => $quotaRules->all(),
                'extra' => ['biz_plan_id' => $plan->id],
            ]);

            foreach ($quotaRules as $rule) {
                $definition = $definitions->get($rule['asset_code']);

                RcAssetService::make()->grantOnce(
                    ownerType: RcAssetOwnerType::Company,
                    ownerId: (int) $company->id,
                    assetCode: $definition->asset_code,
                    assetName: $definition->asset_name,
                    quantity: (int) $rule['quantity'],
                    sourceType: RcAssetSourceType::Order,
                    sourceId: (int) $order->id,
                    bizNo: 'company_product:'.$order->id.':'.$definition->asset_code,
                    remark: filled($remark) ? $remark : '运营后台增加商品：'.$plan->plan_name,
                );
            }

            return $order->load('items');
        });
    }

    private function sceneType(BizPlan $plan): int
    {
        return match ($plan->product_type) {
            RcBizPlanProductType::ValueAddedItem => 2,
            RcBizPlanProductType::AiTool => 3,
            default => 1,
        };
    }

    private function itemType(BizPlan $plan): int
    {
        return match ($plan->product_type) {
            RcBizPlanProductType::ValueAddedItem => 2,
            RcBizPlanProductType::AiTool => 3,
            default => 1,
        };
    }
}
