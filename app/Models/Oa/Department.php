<?php

namespace App\Models\Oa;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

#[Table('oa_departments')]
#[Fillable(['company_id', 'parent_id', 'depth', 'name', 'type', 'sort', 'remark'])]
class Department extends Model
{
    use HasRecursiveRelationships, SoftDeletes;

    public function getDepthName(): string
    {
        return 'tree_depth';
    }

    protected $attributes = [
        'parent_id' => 0,
        'sort' => 0,
    ];

    /**
     * 所属企业
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * 父级
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 子部门
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getParentKeyName(): string
    {
        return 'parent_id';
    }
}
