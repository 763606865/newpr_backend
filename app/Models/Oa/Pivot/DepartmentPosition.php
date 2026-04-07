<?php

namespace App\Models\Oa\Pivot;

use App\Models\Pivot\Pivot;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table(name: 'oa_department_positions', incrementing: true)]
class DepartmentPosition extends Pivot
{

}
