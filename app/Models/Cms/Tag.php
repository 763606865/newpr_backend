<?php

namespace App\Models\Cms;

use App\Enums\CmsStatus;
use App\Models\Model;
use App\Observers\TagMetaObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 门户通用标签表
 *
 * @property int $id 主键ID
 * @property string $category 标签分类
 * @property string $name 标签名称
 * @property string|null $slug 标签别名
 * @property CmsStatus $status 状态
 * @property int $sort 排序
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 *
 * @method static Builder enabled()
 * @method static Builder forCategory(?string $category)
 */
#[Table('cms_tags')]
#[Fillable(['category', 'name', 'slug', 'status', 'sort'])]
#[Visible([
    'id',
    'category',
    'name',
    'slug',
    'status',
    'sort',
    'created_at',
    'updated_at',
])]
#[ObservedBy(TagMetaObserver::class)]
class Tag extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => CmsStatus::Enabled,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => CmsStatus::class,
            'sort' => 'integer',
        ];
    }

    public function announcements(): MorphToMany
    {
        return $this->morphedByMany(Announcement::class, 'taggable', 'cms_tag_relations', 'tag_id', 'taggable_id')
            ->withTimestamps();
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CmsStatus::Enabled->value);
    }

    #[Scope]
    protected function forCategory(Builder $query, ?string $category): void
    {
        if (blank($category)) {
            return;
        }

        $query->where($this->getTable().'.category', '=', $category);
    }
}
