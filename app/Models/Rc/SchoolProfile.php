<?php

namespace App\Models\Rc;

use App\Enums\SchoolProfileStatus;
use App\Models\Cast\AliyunOss;
use App\Models\Model;
use App\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 学校资料表
 *
 * @property int $id 主键ID
 * @property string|null $school_code 学校代码
 * @property string|null $short_name 学校简称
 * @property string|null $province_code 省
 * @property string|null $city_code 市
 * @property string|null $district_code 区/县
 * @property string|null $address 地址
 * @property string|null $contact_name 校方对接总负责人
 * @property string|null $contact_phone 联系电话
 * @property string|null $contact_email 就业办邮箱
 * @property string|null $qualification_file 资质证明
 * @property string|null $competent_dept 主管部门
 * @property array<int, mixed>|null $education_levels 办学层次
 * @property int|null $main_education_level 主办学层次
 * @property string|null $logo 校徽 logo
 * @property string|null $banner 首页横幅图
 * @property bool $allow_company_apply_activity 是否允许企业自主发起进校宣讲申请
 * @property bool $allow_company_cooperate_apply 是否开放校企对接申请入口
 * @property int $campus_count 校区数量
 * @property int $department_count 学院数量
 * @property int $cooperate_company_count 合作企业总数
 * @property int $activity_total 累计举办宣讲/双选会场次
 * @property string|null $intro 院校简介
 * @property SchoolProfileStatus $status 院校状态
 * @property string|null $remark 备注
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read School|null $school 所属学校
 */
#[Table('rc_school_profiles')]
#[Fillable([
    'school_code',
    'short_name',
    'province_code',
    'city_code',
    'district_code',
    'address',
    'contact_name',
    'contact_phone',
    'contact_email',
    'qualification_file',
    'competent_dept',
    'education_levels',
    'main_education_level',
    'logo',
    'banner',
    'allow_company_apply_activity',
    'allow_company_cooperate_apply',
    'campus_count',
    'department_count',
    'cooperate_company_count',
    'activity_total',
    'intro',
    'status',
    'remark',
])]
class SchoolProfile extends Model
{
    protected $attributes = [
        'allow_company_apply_activity' => true,
        'allow_company_cooperate_apply' => true,
        'campus_count' => 0,
        'department_count' => 0,
        'cooperate_company_count' => 0,
        'activity_total' => 0,
        'status' => SchoolProfileStatus::Normal,
    ];

    protected function casts(): array
    {
        return [
            'education_levels' => 'array',
            'main_education_level' => 'integer',
            'logo' => AliyunOss::class.':oss,public,3600',
            'banner' => AliyunOss::class.':oss,public,3600',
            'allow_company_apply_activity' => 'boolean',
            'allow_company_cooperate_apply' => 'boolean',
            'campus_count' => 'integer',
            'department_count' => 'integer',
            'cooperate_company_count' => 'integer',
            'activity_total' => 'integer',
            'status' => SchoolProfileStatus::class,
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_code', 'school_code');
    }
}
