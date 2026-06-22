<?php

namespace App\Models\Rc;

use App\Enums\RcSchoolBoothStatus;
use App\Models\Cast\AliyunOss;
use App\Models\Model;
use App\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 展位表
 *
 * @property int $id 主键ID
 * @property string|null $school_code 学校代码
 * @property string|null $province_code 省
 * @property string|null $city_code 市
 * @property string|null $district_code 区/县
 * @property string|null $address 地址
 * @property string $name 展位名称
 * @property string|null $image 展位平面图
 * @property int|null $area_size 场地总占地面积㎡
 * @property int|null $max_people 场地最大容纳人数
 * @property int $total_booth_count 该模板下总展位数量
 * @property string|null $description 场地说明
 * @property array<string, mixed>|null $rule 分区规则
 * @property RcSchoolBoothStatus $status 状态
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read School|null $school 所属学校
 * @property-read Collection<int, SchoolActivity> $schoolActivities 使用该模板的活动
 *
 * @method static Builder enabled()
 * @method static Builder disabled()
 * @method static Builder forSchoolCode(string $schoolCode)
 */
#[Table('rc_school_booths')]
#[Fillable([
    'school_code',
    'province_code',
    'city_code',
    'district_code',
    'address',
    'name',
    'image',
    'area_size',
    'max_people',
    'total_booth_count',
    'description',
    'rule',
    'status',
    'extra',
])]
class SchoolBooth extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'total_booth_count' => 0,
        'status' => RcSchoolBoothStatus::Enabled,
    ];

    protected function casts(): array
    {
        return [
            'image' => AliyunOss::class.':oss,public,3600',
            'area_size' => 'integer',
            'max_people' => 'integer',
            'total_booth_count' => 'integer',
            'rule' => 'array',
            'status' => RcSchoolBoothStatus::class,
            'extra' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_code', 'school_code');
    }

    public function areas(): HasMany
    {
        return $this->hasMany(SchoolBoothArea::class, 'booth_id');
    }

    public function activityBooths(): HasMany
    {
        return $this->hasMany(SchoolActivityBooth::class, 'booth_id');
    }

    public function schoolActivities(): HasMany
    {
        return $this->hasMany(SchoolActivity::class, 'booth_id');
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where('status', RcSchoolBoothStatus::Enabled);
    }

    #[Scope]
    protected function disabled(Builder $query): void
    {
        $query->where('status', RcSchoolBoothStatus::Disabled);
    }

    #[Scope]
    protected function forSchoolCode(Builder $query, string $schoolCode): void
    {
        $query->where('school_code', $schoolCode);
    }
}
