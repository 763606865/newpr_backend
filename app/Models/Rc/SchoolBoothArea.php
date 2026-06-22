<?php

namespace App\Models\Rc;

use App\Models\Cast\AliyunOss;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 展区表
 *
 * @property int $id 主键ID
 * @property int $booth_id 展位ID
 * @property string|null $code 展区编码
 * @property string $name 展区名称
 * @property int|null $area_size 分区占地面积㎡
 * @property int|null $max_people 分区最大容纳人数
 * @property string|null $map_image 分区独立平面图
 * @property int $start_no 展位起始号
 * @property int $end_no 展位结束号
 * @property int $total_booth_count 分区展位总数
 * @property int|null $max_company_count 单个展位最多企业人数
 * @property array<string, mixed>|null $extra 扩展数据
 * @property int $sort 排序
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read SchoolBooth $booth 所属展位模板
 *
 * @method static Builder ordered()
 */
#[Table('rc_school_booth_areas')]
#[Fillable([
    'booth_id',
    'code',
    'name',
    'area_size',
    'max_people',
    'map_image',
    'start_no',
    'end_no',
    'total_booth_count',
    'max_company_count',
    'extra',
    'sort',
])]
class SchoolBoothArea extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'total_booth_count' => 0,
        'sort' => 0,
    ];

    protected function casts(): array
    {
        return [
            'booth_id' => 'integer',
            'area_size' => 'integer',
            'max_people' => 'integer',
            'map_image' => AliyunOss::class.':oss,public,3600',
            'start_no' => 'integer',
            'end_no' => 'integer',
            'total_booth_count' => 'integer',
            'max_company_count' => 'integer',
            'extra' => 'array',
            'sort' => 'integer',
        ];
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(SchoolBooth::class, 'booth_id');
    }

    public function activityBooths(): HasMany
    {
        return $this->hasMany(SchoolActivityBooth::class, 'booth_area_id');
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('id');
    }
}
