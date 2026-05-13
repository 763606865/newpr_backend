<?php

namespace App\Models\Pivot;

use Illuminate\Database\Eloquent\Attributes\Table;

#[Table(name: 'department_positions', incrementing: true)]
class DepartmentPosition extends Pivot
{

}
