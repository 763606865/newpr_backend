<?php

namespace App\Models\Cms;

use App\Enums\CmsHomeRecommendationModuleType;
use App\Enums\CmsStatus;
use App\Models\Cast\AliyunOss;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * CMS 首页推荐位
 *
 * @property int $id 主键ID
 * @property CmsHomeRecommendationModuleType $module_type 模块类型
 * @property string $recommendable_type 推荐对象类型
 * @property int $recommendable_id 推荐对象ID
 * @property string|null $city_code 城市编码
 * @property string|null $title 推荐标题
 * @property string|null $cover_image 推荐展示图
 * @property-read string|null $cover_image_url 推荐展示图访问地址
 * @property string|null $link_url 跳转链接
 * @property Carbon|null $start_at 推荐开始时间
 * @property Carbon|null $end_at 推荐结束时间
 * @property CmsStatus $status 状态
 * @property int $sort 排序
 * @property int|null $order_id 关联订单ID
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read \Illuminate\Database\Eloquent\Model|null $recommendable 推荐对象
 *
 * @method static Builder enabled()
 * @method static Builder active()
 * @method static Builder forCity(?string $cityCode)
 * @method static Builder forModule(CmsHomeRecommendationModuleType $moduleType)
 */
#[Table('cms_home_recommendations')]
#[Fillable([
    'module_type',
    'recommendable_type',
    'recommendable_id',
    'city_code',
    'title',
    'cover_image',
    'link_url',
    'start_at',
    'end_at',
    'status',
    'sort',
    'order_id',
    'extra',
])]
#[Visible([
    'id',
    'module_type',
    'recommendable_type',
    'recommendable_id',
    'city_code',
    'title',
    'cover_image',
    'cover_image_url',
    'link_url',
    'start_at',
    'end_at',
    'status',
    'sort',
    'order_id',
    'extra',
    'created_at',
    'updated_at',
])]
class HomeRecommendation extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => CmsStatus::Enabled,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'module_type' => CmsHomeRecommendationModuleType::class,
            'recommendable_id' => 'integer',
            'cover_image' => AliyunOss::class.':oss,public,3600',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => CmsStatus::class,
            'sort' => 'integer',
            'order_id' => 'integer',
            'extra' => 'array',
        ];
    }

    public function recommendable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function coverImageUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (blank($this->cover_image)) {
                    return null;
                }

                $path = ltrim($this->cover_image, '/');
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
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CmsStatus::Enabled->value);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $now = now();

        $query->where(function (Builder $builder) use ($now): void {
            $builder->whereNull($this->getTable().'.start_at')
                ->orWhere($this->getTable().'.start_at', '<=', $now);
        })->where(function (Builder $builder) use ($now): void {
            $builder->whereNull($this->getTable().'.end_at')
                ->orWhere($this->getTable().'.end_at', '>=', $now);
        });
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
    protected function forModule(Builder $query, CmsHomeRecommendationModuleType $moduleType): void
    {
        $query->where($this->getTable().'.module_type', '=', $moduleType);
    }
}
