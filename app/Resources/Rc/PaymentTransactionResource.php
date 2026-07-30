<?php

namespace App\Resources\Rc;

use App\Enums\RcOrderPayChannel;
use App\Enums\RcPaymentStatus;
use App\Models\Rc\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PaymentTransaction $payment */
        $payment = $this->resource;

        return [
            'payment_no' => $payment->payment_no,
            'order_id' => $payment->order_id,
            'channel' => $payment->channel,
            'channel_name' => RcOrderPayChannel::tryFrom($payment->channel)?->name,
            'channel_label' => RcOrderPayChannel::tryFrom($payment->channel)?->getLabel(),
            'status' => $payment->status,
            'status_name' => RcPaymentStatus::tryFrom($payment->status)?->name,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'expired_at' => $payment->expired_at,
            'gateway_payload' => $payment->response_payload,
        ];
    }
}
