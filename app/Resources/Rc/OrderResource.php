<?php

namespace App\Resources\Rc;

use App\Enums\RcOrderPayChannel;
use App\Enums\RcOrderPayStatus;
use App\Enums\RcOrderStatus;
use App\Models\Rc\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'payer_type' => $order->payer_type,
            'payer_id' => $order->payer_id,
            'scene_type' => $order->scene_type,
            'product_code' => $order->product_code,
            'product_name' => $order->product_name,
            'quantity' => $order->quantity,
            'original_amount' => $order->original_amount,
            'discount_amount' => $order->discount_amount,
            'payable_amount' => $order->payable_amount,
            'paid_amount' => $order->paid_amount,
            'currency' => $order->currency,
            'pay_channel' => $order->pay_channel,
            'pay_channel_label' => RcOrderPayChannel::tryFrom($order->pay_channel)?->getLabel(),
            'pay_status' => $order->pay_status,
            'pay_status_name' => RcOrderPayStatus::tryFrom($order->pay_status)?->name,
            'order_status' => $order->order_status,
            'order_status_name' => RcOrderStatus::tryFrom($order->order_status)?->name,
            'expired_at' => $order->expired_at,
            'paid_at' => $order->paid_at,
            'canceled_at' => $order->canceled_at,
            'items' => $order->relationLoaded('items')
                ? $order->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'item_code' => $item->item_code,
                    'item_name' => $item->item_name,
                    'item_type' => $item->item_type,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'line_amount' => $item->line_amount,
                    'entitlement_snapshot' => $item->entitlement_snapshot,
                ])->all()
                : [],
            'created_at' => $order->created_at,
        ];
    }
}
