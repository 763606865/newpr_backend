<?php

namespace App\Models\Cms;

use App\Enums\CmsAdType;
use App\Enums\CmsStatus;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 门户广告表
 *
 * @property int $id 主键ID
 * @property int $slot_id 广告位ID
 * @property string|null $city_code 城市编码
 * @property string $title 广告标题
 * @property CmsAdType $type 广告类型
 * @property string|null $image 图片地址
 * @property string|null $text_content 文本内容
 * @property string|null $code_content 代码内容
 * @property string|null $link_url 跳转地址
 * @property Carbon|null $start_at 生效开始时间
 * @property Carbon|null $end_at 生效结束时间
 * @property CmsStatus $status 状态
 * @property int $sort 排序
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read AdSlot $slot 所属广告位
 *
 * @method static Builder forCity(?string $cityCode)
 * @method static Builder enabled()
 */
#[Table('cms_ads')]
#[Fillable([
    'slot_id',
    'city_code',
    'title',
    'type',
    'image',
    'text_content',
    'code_content',
    'link_url',
    'start_at',
    'end_at',
    'status',
    'sort',
    'extra',
])]
class Ad extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'type' => CmsAdType::Image,
        'status' => CmsStatus::Enabled,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'slot_id' => 'integer',
            'type' => CmsAdType::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => CmsStatus::class,
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AdSlot::class, 'slot_id');
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
        $query->where($this->getTable().'.status', '=', CmsStatus::Enabled->value);
    }
}
