<?php

namespace App\Models\Oa;

use App\Enums\LeaveTypeDeductionType;
use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('oa_leave_types')]
#[Fillable([
    'company_id',
    'name',
    'code',
    'deduction_type',
    'unit_type',
    'min_duration',
    'need_attachment',
    'allow_negative',
    'max_continuous_days',
    'status',
])]
class LeaveType extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'deduction_type' => LeaveTypeDeductionType::Full->value,
        'unit_type' => 1,
        'min_duration' => 0.5,
        'need_attachment' => 0,
        'allow_negative' => 0,
        'status' => 1,
    ];

    protected function casts(): array
    {
        return [
            'deduction_type' => LeaveTypeDeductionType::class,
            'unit_type' => 'integer',
            'min_duration' => 'decimal:2',
            'need_attachment' => 'boolean',
            'allow_negative' => 'boolean',
            'max_continuous_days' => 'integer',
            'status' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'leave_type_id');
    }
}
