<?php

namespace App\Models\Cms;

use App\Enums\CmsOpenTarget;
use App\Enums\CmsStatus;
use App\Models\Cast\AliyunOss;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 门户友情链接表
 *
 * @property int $id 主键ID
 * @property string|null $city_code 城市编码(为空表示全站可用)
 * @property string $name 友链名称
 * @property string $url 友链地址
 * @property string|null $logo 友链Logo
 * @property CmsOpenTarget $target 打开方式
 * @property string|null $rel 链接关系属性
 * @property string|null $description 描述
 * @property Carbon|null $start_at 生效开始时间
 * @property Carbon|null $end_at 生效结束时间
 * @property CmsStatus $status 状态
 * @property int $sort 排序
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 *
 * @method static Builder forCity(?string $cityCode)
 * @method static Builder enabled()
 */
#[Table('cms_friend_links')]
#[Fillable([
    'city_code',
    'name',
    'url',
    'logo',
    'target',
    'rel',
    'description',
    'start_at',
    'end_at',
    'status',
    'sort',
    'extra',
])]
class FriendLink extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'target' => CmsOpenTarget::Blank,
        'status' => CmsStatus::Enabled,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'logo' => AliyunOss::class.':oss,public,3600',
            'target' => CmsOpenTarget::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => CmsStatus::class,
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
        $query->where($this->getTable().'.status', '=', CmsStatus::Enabled->value);
    }
}
