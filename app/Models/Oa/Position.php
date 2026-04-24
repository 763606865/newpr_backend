<?php

namespace App\Models\Oa;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('oa_positions')]
#[Fillable(['company_id', 'name', 'code', 'sort', 'remark'])]
class Position extends Model
{
    use SoftDeletes;

    /**
     * 所属企业
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
