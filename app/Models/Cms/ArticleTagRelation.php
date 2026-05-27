<?php

namespace App\Models\Cms;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 门户文章标签关联表
 *
 * @property int $id 主键ID
 * @property int $article_id 文章ID
 * @property int $tag_id 标签ID
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read Article $article 所属文章
 * @property-read ArticleTag $tag 所属标签
 */
#[Table('cms_article_tag_relations')]
#[Fillable(['article_id', 'tag_id'])]
class ArticleTagRelation extends Model
{
    protected function casts(): array
    {
        return [
            'article_id' => 'integer',
            'tag_id' => 'integer',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(ArticleTag::class, 'tag_id');
    }
}
