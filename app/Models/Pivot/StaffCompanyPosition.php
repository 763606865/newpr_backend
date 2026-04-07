<?php

namespace App\Models\Pivot;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use App\Models\Concerns\DateTimeFormat;

#[Table(name: 'staff_company_positions', incrementing: true)]
#[Fillable(['company_id', 'department_id', 'position_id', 'staff_id', 'staff_no', 'status', 'entry_time'])]
class StaffCompanyPosition extends Pivot
{
    use DateTimeFormat;
}
