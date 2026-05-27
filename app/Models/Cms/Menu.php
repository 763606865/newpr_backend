<?php

namespace App\Models\Cms;

use App\Enums\CmsLinkType;
use App\Enums\CmsOpenTarget;
use App\Enums\CmsStatus;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 门户首页菜单表
 *
 * @property int $id 主键ID
 * @property int $parent_id 父级菜单ID
 * @property string $name 菜单名称
 * @property string|null $code 菜单编码
 * @property CmsLinkType $link_type 链接类型
 * @property string|null $link_url 跳转地址
 * @property string|null $icon 菜单图标
 * @property string|null $image 菜单图片
 * @property CmsOpenTarget $target 打开方式
 * @property int $is_show 是否展示
 * @property CmsStatus $status 状态
 * @property int $sort 排序
 * @property Carbon|null $start_at 生效开始时间
 * @property Carbon|null $end_at 生效结束时间
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Menu|null $parent 父级菜单
 * @property-read Collection<int, Menu> $children 子级菜单
 *
 * @method static Builder enabled()
 */
#[Table('cms_menus')]
#[Fillable([
    'parent_id',
    'name',
    'code',
    'link_type',
    'link_url',
    'icon',
    'image',
    'target',
    'is_show',
    'status',
    'sort',
    'start_at',
    'end_at',
    'extra',
])]
class Menu extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'parent_id' => 0,
        'link_type' => CmsLinkType::Internal,
        'target' => CmsOpenTarget::Self,
        'is_show' => 1,
        'status' => CmsStatus::Enabled,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'link_type' => CmsLinkType::class,
            'target' => CmsOpenTarget::class,
            'is_show' => 'boolean',
            'status' => CmsStatus::class,
            'sort' => 'integer',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CmsStatus::Enabled->value);
    }
}
