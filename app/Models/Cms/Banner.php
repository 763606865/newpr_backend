<?php

namespace App\Models\Cms;

use App\Enums\CmsLinkType;
use App\Enums\CmsOpenTarget;
use App\Enums\CmsStatus;
use App\Models\Cast\AliyunOss;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * 门户Banner表
 *
 * @property int $id 主键ID
 * @property int $position_id 版位ID
 * @property string|null $city_code 城市编码
 * @property string $title 标题
 * @property string $image 图片地址
 * @property-read string|null $image_url 图片访问地址
 * @property CmsLinkType $link_type 链接类型
 * @property string|null $link_url 跳转地址
 * @property CmsOpenTarget $target 打开方式
 * @property Carbon|null $start_at 生效开始时间
 * @property Carbon|null $end_at 生效结束时间
 * @property CmsStatus $status 状态
 * @property int $sort 排序
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read BannerPosition $position 所属版位
 *
 * @method static Builder forCity(?string $cityCode)
 * @method static Builder enabled()
 */
#[Table('cms_banners')]
#[Fillable([
    'position_id',
    'city_code',
    'title',
    'image',
    'image_url',
    'link_type',
    'link_url',
    'target',
    'start_at',
    'end_at',
    'status',
    'sort',
    'extra',
])]
#[Visible([
    'id',
    'position_id',
    'city_code',
    'title',
    'image',
    'link_type',
    'link_url',
    'target',
    'start_at',
    'end_at',
    'status',
    'sort',
    'extra',
    'created_at',
    'updated_at',
])]
class Banner extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'link_type' => CmsLinkType::Internal,
        'target' => CmsOpenTarget::Self,
        'status' => CmsStatus::Enabled,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'position_id' => 'integer',
            'image' => AliyunOss::class.':oss,public,3600',
            'link_type' => CmsLinkType::class,
            'target' => CmsOpenTarget::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => CmsStatus::class,
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(BannerPosition::class, 'position_id');
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (blank($this->image)) {
                    return null;
                }

                $path = ltrim($this->image, '/');
                $visibility = (string) config('filesystems.disks.oss.visibility', 'public');

                try {
                    if ($visibility === 'private') {
                        return Storage::disk('oss')->temporaryUrl(
                            $path,
                            now()->addSeconds((int) config('filesystems.disks.oss.temporary_url_ttl', 3600)),
                        );
                    }

                    return Storage::disk('oss')->url($path);
                } catch (Throwable) {
                    return $path;
                }
            },
        );
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
