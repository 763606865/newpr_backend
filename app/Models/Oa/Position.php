<?php

namespace App\Models\Oa;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;

#[Table('oa_positions')]
#[Fillable(['name', 'code', 'sort', 'remark'])]
class Position extends Model
{
    use SoftDeletes;
}
