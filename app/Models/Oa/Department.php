<?php

namespace App\Models\Oa;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

#[Table('oa_departments')]
#[Fillable(['company_id', 'parent_id', 'depth', 'name', 'type', 'sort', 'remark'])]
class Department extends Model
{
    use SoftDeletes, HasRecursiveRelationships;

    protected $attributes = [
        'parent_id' => 0,
        'sort' => 0,
    ];

    /**
     * 所属企业
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * 父级
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 子部门
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getParentKeyName(): string
    {
        return 'parent_id';
    }
}
