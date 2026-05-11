<?php

namespace App\Models\Oa;

use App\Enums\CompanyPlanStatus;
use App\Models\Oa\System\Plan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 公司套餐绑定记录
 * Class CompanyPlan
 * @property CompanyPlanStatus $status
 */
#[Table('oa_company_plans')]
#[Fillable(['company_id', 'ship_id', 'plan_id', 'start_time', 'end_time', 'is_current', 'status', 'extra'])]
class CompanyPlan extends Model
{
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_current' => 'boolean',
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

    public function ship(): BelongsTo
    {
        return $this->belongsTo(ShipCompanyPlan::class, 'ship_id');
    }

    /**
     * 转化为失效状态
     */
    public function transitionToDisabled(): bool
    {
        if ($this->status !== CompanyPlanStatus::Disabled) {
            $this->is_current = 0;
            $this->end_time = Carbon::now();
            $this->status = CompanyPlanStatus::Disabled;
            return $this->save();
        }
        return false;
    }
}
