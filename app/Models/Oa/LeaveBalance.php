<?php

namespace App\Models\Oa;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('oa_leave_balances')]
#[Fillable([
    'company_id',
    'employee_id',
    'leave_type_id',
    'year',
    'valid_start_date',
    'valid_end_date',
    'total_days',
    'used_days',
    'balance_days',
    'overtime_source_id',
])]
class LeaveBalance extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'used_days' => 0,
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'valid_start_date' => 'date',
            'valid_end_date' => 'date',
            'total_days' => 'decimal:2',
            'used_days' => 'decimal:2',
            'balance_days' => 'decimal:2',
            'overtime_source_id' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
