<?php

namespace App\Models\Cms;

use App\Enums\CmsAnnouncementType;
use App\Enums\CmsPublishStatus;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 门户公告表
 *
 * @property int $id 主键ID
 * @property string|null $city_code 城市编码
 * @property string $title 公告标题
 * @property string|null $sub_title 公告副标题
 * @property string|null $link_url 公告链接
 * @property CmsAnnouncementType $type 公告类型
 * @property string|null $source_name 来源名称
 * @property string|null $source_url 来源地址
 * @property string|null $summary 公告摘要
 * @property string|null $content 公告正文
 * @property Carbon|null $published_at 发布时间
 * @property Carbon|null $start_at 生效开始时间
 * @property Carbon|null $end_at 生效结束时间
 * @property int $is_top 是否置顶
 * @property CmsPublishStatus $status 状态
 * @property int $sort 排序
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 *
 * @method static Builder forCity(?string $cityCode)
 * @method static Builder enabled()
 */
#[Table('cms_announcements')]
#[Fillable([
    'city_code',
    'title',
    'sub_title',
    'link_url',
    'type',
    'source_name',
    'source_url',
    'summary',
    'content',
    'published_at',
    'start_at',
    'end_at',
    'is_top',
    'status',
    'sort',
    'extra',
])]
class Announcement extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'type' => CmsAnnouncementType::SelfPublished,
        'is_top' => 0,
        'status' => CmsPublishStatus::Draft,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'type' => CmsAnnouncementType::class,
            'published_at' => 'datetime',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_top' => 'boolean',
            'status' => CmsPublishStatus::class,
            'sort' => 'integer',
            'extra' => 'array',
        ];
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
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CmsPublishStatus::Published->value);
    }
}
