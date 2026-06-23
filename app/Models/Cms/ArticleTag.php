<?php

namespace App\Models\Cms;

use App\Enums\CmsStatus;
use App\Models\Model;
use App\Observers\ArticleTagMetaObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 门户文章标签表
 *
 * @property int $id 主键ID
 * @property string $name 标签名称
 * @property string|null $slug 标签别名
 * @property CmsStatus $status 状态
 * @property int $sort 排序
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Collection<int, Article> $articles 文章列表
 * @property-read Collection<int, ArticleTagRelation> $articleTagRelations 标签关联
 *
 * @method static Builder enabled()
 */
#[Table('cms_article_tags')]
#[ObservedBy(ArticleTagMetaObserver::class)]
#[Fillable(['name', 'slug', 'status', 'sort'])]
#[Visible([
    'id',
    'name',
    'slug',
    'status',
    'sort',
    'created_at',
    'updated_at',
])]
class ArticleTag extends Model
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

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'cms_article_tag_relations', 'tag_id', 'article_id')
            ->withTimestamps();
    }

    public function articleTagRelations(): HasMany
    {
        return $this->hasMany(ArticleTagRelation::class, 'tag_id');
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CmsStatus::Enabled->value);
    }
}
