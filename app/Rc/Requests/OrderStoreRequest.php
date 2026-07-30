<?php

namespace App\Rc\Requests;

use App\Enums\RcBizPlanStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'biz_plan_id' => [
                'required',
                'integer',
                Rule::exists('rc_biz_plans', 'id')
                    ->where('status', RcBizPlanStatus::Enabled->value),
            ],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'biz_plan_id' => '商品',
            'quantity' => '商品数量',
        ];
    }
}
