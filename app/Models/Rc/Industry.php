<?php

namespace App\Models\Rc;

use App\Models\Model;
use App\Observers\Rc\IndustryMetaObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 常见行业表
 *
 * @property int $id 主键ID
 * @property string $name 行业名称
 * @property string $code 行业代码
 * @property int|null $parent_id 父级行业ID
 * @property int $sort 排序
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Industry|null $parent 父级行业
 * @property-read Collection<int, Industry> $children 子级行业
 */
#[Table('rc_industries')]
#[Fillable([
    'name',
    'code',
    'parent_id',
    'sort',
    'extra',
])]
#[ObservedBy(IndustryMetaObserver::class)]
class Industry extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    /**
     * 父级行业
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 子级行业
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
