<?php

namespace App\Models;

use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolActivityBooth;
use App\Models\Rc\SchoolActivitySchool;
use App\Models\Rc\SchoolBooth;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * 学校表
 *
 * @property int $id 主键ID
 * @property string|null $school_code 学校代码
 * @property string $name 学校名称
 * @property string|null $province 省
 * @property string|null $city 市
 * @property string|null $area 区/县
 * @property string|null $address 地址
 * @property string|null $competent_dept 主管部门
 * @property string|null $type 类型（本科/专科/高中/小学）
 * @property string|null $remark 备注
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read SchoolProfile|null $profile 学校资料
 * @property-read Collection<int, SchoolBooth> $booths 展位模板
 * @property-read Collection<int, SchoolActivity> $activities 关联活动
 */
#[Table('schools')]
#[Fillable([
    'school_code',
    'name',
    'province',
    'city',
    'area',
    'address',
    'competent_dept',
    'type',
    'remark',
])]
class School extends Model
{
    public function profile(): HasOne
    {
        return $this->hasOne(SchoolProfile::class, 'school_code', 'school_code');
    }

    public function booths(): HasMany
    {
        return $this->hasMany(SchoolBooth::class, 'school_code', 'school_code');
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(
            SchoolActivity::class,
            'rc_school_activity_schools',
            'school_id',
            'activity_id',
        )->withTimestamps();
    }

    public function activitySchoolLinks(): HasMany
    {
        return $this->hasMany(SchoolActivitySchool::class, 'school_id');
    }

    public function activityBooths(): HasMany
    {
        return $this->hasMany(SchoolActivityBooth::class, 'school_id');
    }
}
