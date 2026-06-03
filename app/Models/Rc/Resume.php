<?php

namespace App\Models\Rc;

use App\Enums\RcCurrentIdentity;
use App\Enums\RcEducationLevel;
use App\Enums\RcMaritalStatus;
use App\Enums\RcResumeSourceType;
use App\Enums\RcResumeStatus;
use App\Enums\RcSalaryUnit;
use App\Enums\UserGender;
use App\Models\Cast\AliyunOss;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘简历表
 *
 * @property int $id 主键ID
 * @property int $user_id 关联用户ID
 * @property string $resume_no 简历编号
 * @property string $title 简历名称
 * @property int $source_type 来源类型
 * @property string|null $full_name 姓名
 * @property string|null $avatar 头像
 * @property int $gender 性别
 * @property string|null $id_card 身份证号
 * @property string $nation 民族
 * @property string|null $birth_date 出生日期
 * @property string|null $birth_month 出生年月
 * @property int|null $age 年龄
 * @property int $marital_status 婚姻状况
 * @property string $political_status 政治面貌
 * @property string|null $native_place 籍贯
 * @property int $current_identity 当前身份
 * @property string|null $work_start_date 参加工作日期
 * @property int|null $work_years 工作年限
 * @property int|null $highest_education_level 最高学历
 * @property int $is_fresh_graduate 是否应届生
 * @property float|null $expected_salary_min 期望薪资下限
 * @property float|null $expected_salary_max 期望薪资上限
 * @property int $expected_salary_unit 期望薪资单位
 * @property string|null $current_city_code 现居住城市编码
 * @property string|null $file_url 简历文件地址
 * @property string|null $file_name 简历文件名称
 * @property string|null $file_ext 文件后缀
 * @property string|null $text_content 简历文本内容
 * @property array<string, mixed>|null $parsed_data 解析后的结构化数据
 * @property int $is_primary 是否主简历
 * @property int $status 状态
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read User $user 所属用户
 *
 * @method static Builder atHighestEducationLevel(int $level)
 * @method static Builder freshGraduates()
 * @method static Builder inCityCode(string $cityCode)
 * @method static Builder withAgeBetween(int $minAge, int $maxAge)
 * @method static Builder withExpectedSalaryBetween(int|float|string $minSalary, int|float|string $maxSalary)
 * @method static Builder withWorkYearsBetween(int $minYears, int $maxYears)
 */
#[Table('rc_resumes')]
#[Fillable([
    'user_id',
    'resume_no',
    'title',
    'full_name',
    'avatar',
    'gender',
    'id_card',
    'nation',
    'birth_date',
    'birth_month',
    'age',
    'marital_status',
    'political_status',
    'native_place',
    'current_identity',
    'work_start_date',
    'work_years',
    'current_salary',
    'salary_remark',
    'recruit_source',
    'highest_education_level',
    'is_fresh_graduate',
    'expected_salary_min',
    'expected_salary_max',
    'expected_salary_unit',
    'household_register',
    'household_register_detail',
    'current_residence_city',
    'current_city_code',
    'current_residence_detail',
    'residence_country',
    'phone',
    'email',
    'source_type',
    'file_url',
    'file_name',
    'file_ext',
    'text_content',
    'parsed_data',
    'is_primary',
    'status',
    'extra',
])]
class Resume extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'source_type' => RcResumeSourceType::Upload,
        'is_primary' => 0,
        'status' => RcResumeStatus::Normal,
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'avatar' => AliyunOss::class.':oss,public,3600',
            'gender' => UserGender::class,
            'age' => 'integer',
            'marital_status' => RcMaritalStatus::class,
            'current_identity' => RcCurrentIdentity::class,
            'work_start_date' => 'date',
            'work_years' => 'integer',
            'highest_education_level' => RcEducationLevel::class,
            'is_fresh_graduate' => 'integer',
            'expected_salary_min' => 'decimal:2',
            'expected_salary_max' => 'decimal:2',
            'expected_salary_unit' => RcSalaryUnit::class,
            'source_type' => RcResumeSourceType::class,
            'is_primary' => 'integer',
            'status' => RcResumeStatus::class,
            'parsed_data' => 'array',
            'extra' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'resume_id');
    }

    public function intentions(): HasMany
    {
        return $this->hasMany(ResumeIntention::class, 'resume_id');
    }

    public function works(): HasMany
    {
        return $this->hasMany(ResumeWork::class, 'resume_id');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(ResumeEducation::class, 'resume_id');
    }

    public function talentPoolMembers(): HasMany
    {
        return $this->hasMany(TalentPoolMember::class, 'resume_id');
    }

    #[Scope]
    protected function atHighestEducationLevel(Builder $query, int $level): void
    {
        $query->where($this->getTable().'.highest_education_level', '=', $level);
    }

    #[Scope]
    protected function freshGraduates(Builder $query): void
    {
        $query->where($this->getTable().'.is_fresh_graduate', '=', 1);
    }

    #[Scope]
    protected function inCityCode(Builder $query, string $cityCode): void
    {
        $query->where($this->getTable().'.current_city_code', '=', $cityCode);
    }

    #[Scope]
    protected function withAgeBetween(Builder $query, int $minAge, int $maxAge): void
    {
        $query->whereBetween($this->getTable().'.age', [$minAge, $maxAge]);
    }

    #[Scope]
    protected function withExpectedSalaryBetween(Builder $query, int|float|string $minSalary, int|float|string $maxSalary): void
    {
        $query->where($this->getTable().'.expected_salary_min', '>=', $minSalary)
            ->where($this->getTable().'.expected_salary_max', '<=', $maxSalary);
    }

    #[Scope]
    protected function withWorkYearsBetween(Builder $query, int $minYears, int $maxYears): void
    {
        $query->whereBetween($this->getTable().'.work_years', [$minYears, $maxYears]);
    }
}
