<?php

namespace App\Http\Controllers;

use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use App\Enums\RcIdentityType;
use App\Models\Rc\BizPlan;
use App\Services\CmsMenuAudienceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RcBizPlanController extends Controller
{
    /**
     * 获取当前访问身份可购买的商品套餐。
     *
     * GET /cms/rc/biz-plans
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $identityType = CmsMenuAudienceService::make()->resolveRcIdentityType($request);
        $targetSide = $identityType === RcIdentityType::Recruiter
            ? RcBizPlanTargetSide::Recruiter
            : RcBizPlanTargetSide::JobSeeker;

        $plans = BizPlan::query()
            ->where('status', RcBizPlanStatus::Enabled->value)
            ->where('target_side', $targetSide->value)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn (BizPlan $plan): array => $this->serializePlan($plan))
            ->all();

        return $this->success($plans);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlan(BizPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'plan_name' => $plan->plan_name,
            'plan_code' => $plan->plan_code,
            'price' => $plan->price,
            'duration' => $plan->duration,
            'target_side' => $plan->target_side->value,
            'target_side_label' => $plan->target_side->getLabel(),
            'product_type' => $plan->product_type->value,
            'product_type_label' => $plan->product_type->getLabel(),
            'billing_cycle' => $plan->billing_cycle->value,
            'billing_cycle_label' => $plan->billing_cycle->getLabel(),
            'remark' => $plan->remark,
            'quota_rules' => $plan->quota_rules,
            'extra' => $plan->extra,
        ];
    }
}
