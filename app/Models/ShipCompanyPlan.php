<?php

namespace App\Models;

use App\Models\Biz\Plan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('ship_company_biz_plans')]
#[Fillable(['company_id', 'plan_id', 'plan_name', 'plan_code', 'original_price', 'pay_amount', 'menus', 'features', 'quota', 'start_time', 'end_time', 'remark', 'extra'])]
class ShipCompanyPlan extends Model
{
    protected $casts = [
        'original_price' => 'decimal:2',
        'pay_amount' => 'decimal:2',
        'menus' => 'array',
        'features' => 'array',
        'quota' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'extra' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
