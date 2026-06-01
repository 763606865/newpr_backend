<?php

namespace App\Models\Cms;

use App\Enums\CmsStatus;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 门户Banner版位表
 *
 * @property int $id 主键ID
 * @property string $name 版位名称
 * @property string $code 版位编码
 * @property int|null $width 建议宽度
 * @property int|null $height 建议高度
 * @property CmsStatus $status 状态
 * @property int $sort 排序
 * @property string|null $remark 备注
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Collection<int, Banner> $banners Banner列表
 *
 * @method static Builder enabled()
 */
#[Table('cms_banner_positions')]
#[Fillable(['name', 'code', 'width', 'height', 'status', 'sort', 'remark'])]
#[Visible([
    'id',
    'name',
    'code',
    'width',
    'height',
    'status',
    'sort',
    'remark',
    'created_at',
    'updated_at',
])]
class BannerPosition extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => CmsStatus::Enabled,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'status' => CmsStatus::class,
            'sort' => 'integer',
        ];
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class, 'position_id');
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CmsStatus::Enabled->value);
    }
}
