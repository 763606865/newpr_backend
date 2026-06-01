<?php

namespace App\Models\Cms;

use App\Enums\CmsArticleContentType;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 门户文章内容表
 *
 * @property int $id 主键ID
 * @property int $article_id 文章ID
 * @property string|null $content 正文内容
 * @property CmsArticleContentType $content_type 内容类型
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read Article $article 所属文章
 */
#[Table('cms_article_contents')]
#[Fillable(['article_id', 'content', 'content_type', 'extra'])]
#[Visible([
    'id',
    'article_id',
    'content',
    'content_type',
    'extra',
    'created_at',
    'updated_at',
])]
class ArticleContent extends Model
{
    protected $attributes = [
        'content_type' => CmsArticleContentType::Html,
    ];

    protected function casts(): array
    {
        return [
            'article_id' => 'integer',
            'content_type' => CmsArticleContentType::class,
            'extra' => 'array',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
