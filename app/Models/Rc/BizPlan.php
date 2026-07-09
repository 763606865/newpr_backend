<?php

namespace App\Models\Rc;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('rc_biz_plans')]
#[Fillable([
    'plan_name',
    'plan_code',
    'price',
    'duration',
    'target_side',
    'product_type',
    'billing_cycle',
    'sort',
    'remark',
    'quota_rules',
    'status',
    'extra',
])]
class BizPlan extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration' => 'integer',
            'target_side' => 'integer',
            'product_type' => 'integer',
            'billing_cycle' => 'integer',
            'sort' => 'integer',
            'quota_rules' => 'array',
            'status' => 'integer',
            'extra' => 'array',
        ];
    }
}
