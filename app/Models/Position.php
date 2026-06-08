<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property bool $is_leader 是否管理岗
 */
#[Table('positions')]
#[Fillable(['company_id', 'name', 'code', 'is_leader', 'sort', 'remark'])]
class Position extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'is_leader' => false,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_leader' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /**
     * 所属企业
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
