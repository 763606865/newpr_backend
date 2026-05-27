<?php

namespace App\Models\Cms;

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
 * 门户文章分类表
 *
 * @property int $id 主键ID
 * @property int $parent_id 父级分类ID
 * @property string $name 分类名称
 * @property string|null $slug 分类别名
 * @property string|null $cover 封面图
 * @property string|null $description 分类描述
 * @property CmsStatus $status 状态
 * @property int $sort 排序
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read ArticleCategory|null $parent 父级分类
 * @property-read Collection<int, ArticleCategory> $children 子级分类
 * @property-read Collection<int, Article> $articles 文章列表
 *
 * @method static Builder enabled()
 */
#[Table('cms_article_categories')]
#[Fillable(['parent_id', 'name', 'slug', 'cover', 'description', 'status', 'sort'])]
class ArticleCategory extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'parent_id' => 0,
        'status' => CmsStatus::Enabled,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'status' => CmsStatus::class,
            'sort' => 'integer',
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

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'category_id');
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CmsStatus::Enabled->value);
    }
}
