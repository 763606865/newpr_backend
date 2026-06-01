<?php

namespace App\Models\Cms;

use App\Enums\CmsPublishStatus;
use App\Models\Cast\AliyunOss;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 门户文章表
 *
 * @property int $id 主键ID
 * @property int $category_id 分类ID
 * @property string|null $city_code 城市编码
 * @property string $title 标题
 * @property string|null $sub_title 副标题
 * @property string|null $slug 文章别名
 * @property string|null $cover 封面图
 * @property string|null $summary 摘要
 * @property string|null $author 作者
 * @property string|null $source_name 来源名称
 * @property string|null $source_url 来源链接
 * @property int $is_top 是否置顶
 * @property int $is_recommend 是否推荐
 * @property CmsPublishStatus $status 状态
 * @property Carbon|null $published_at 发布时间
 * @property int $view_count 浏览量
 * @property string|null $seo_keywords SEO关键词
 * @property string|null $seo_description SEO描述
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read ArticleCategory|null $category 所属分类
 * @property-read ArticleContent|null $content 正文内容
 * @property-read Collection<int, ArticleTag> $tags 标签列表
 * @property-read Collection<int, ArticleTagRelation> $articleTagRelations 标签关联
 *
 * @method static Builder forCity(?string $cityCode)
 * @method static Builder published()
 */
#[Table('cms_articles')]
#[Fillable([
    'category_id',
    'city_code',
    'title',
    'sub_title',
    'slug',
    'cover',
    'summary',
    'author',
    'source_name',
    'source_url',
    'is_top',
    'is_recommend',
    'status',
    'published_at',
    'view_count',
    'seo_keywords',
    'seo_description',
    'extra',
])]
#[Visible([
    'id',
    'category_id',
    'city_code',
    'title',
    'sub_title',
    'slug',
    'cover',
    'summary',
    'author',
    'source_name',
    'source_url',
    'is_top',
    'is_recommend',
    'status',
    'published_at',
    'view_count',
    'seo_keywords',
    'seo_description',
    'extra',
    'created_at',
    'updated_at',
])]
class Article extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'category_id' => 0,
        'is_top' => 0,
        'is_recommend' => 0,
        'status' => CmsPublishStatus::Draft,
        'view_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'cover' => AliyunOss::class.':oss,public,3600',
            'is_top' => 'boolean',
            'is_recommend' => 'boolean',
            'status' => CmsPublishStatus::class,
            'published_at' => 'datetime',
            'view_count' => 'integer',
            'extra' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    public function content(): HasOne
    {
        return $this->hasOne(ArticleContent::class, 'article_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ArticleTag::class, 'cms_article_tag_relations', 'article_id', 'tag_id')
            ->withTimestamps();
    }

    public function articleTagRelations(): HasMany
    {
        return $this->hasMany(ArticleTagRelation::class, 'article_id');
    }

    #[Scope]
    protected function forCity(Builder $query, ?string $cityCode): void
    {
        if (blank($cityCode)) {
            return;
        }

        $query->where(function (Builder $builder) use ($cityCode): void {
            $builder->where($this->getTable().'.city_code', '=', $cityCode)
                ->orWhereNull($this->getTable().'.city_code');
        });
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CmsPublishStatus::Published->value);
    }
}
