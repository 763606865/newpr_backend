<?php

namespace App\Models\Rc;

use App\Enums\RcCurrentIdentity;
use App\Enums\RcEducationLevel;
use App\Enums\RcMaritalStatus;
use App\Enums\RcPoliticalStatus;
use App\Enums\RcResumeSourceType;
use App\Enums\RcResumeStatus;
use App\Enums\RcSalaryUnit;
use App\Enums\UserGender;
use App\Models\Cast\AliyunOss;
use App\Models\Model;
use App\Models\User;
use App\Services\MetaService;
use App\Support\ScoutQuery;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

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
 * @property RcPoliticalStatus $political_status 政治面貌
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
    use HasEvents, Searchable, SoftDeletes;

    protected $attributes = [
        'gender' => UserGender::Unknown,
        'nation' => '未知',
        'marital_status' => RcMaritalStatus::Unknown,
        'political_status' => RcPoliticalStatus::Masses,
        'current_identity' => RcCurrentIdentity::Other,
        'source_type' => RcResumeSourceType::Manual,
        'is_primary' => 1,
        'status' => RcResumeStatus::Normal,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $resume): void {
            if (blank($resume->resume_no)) {
                $resume->resume_no = static::generateUniqueResumeNo((int) $resume->user_id);
            }
        });

        static::saving(function (self $resume): void {
            $resume->syncDerivedAttributes();
        });
    }

    /**
     * 根据已填写字段补全衍生数据（年龄、出生年月、工作年限等）。
     */
    protected function syncDerivedAttributes(): void
    {
        $this->syncTitleFromFullName();
        $this->syncAgeFromBirthDate();
        $this->syncBirthMonthFromBirthDate();
        $this->syncWorkStartDateFromWorkYear();
        $this->syncWorkYearsFromWorkStartDate();
        $this->syncCurrentResidenceCityFromCode();
        $this->syncIsFreshGraduateFromWorkYearAndIdentity();
    }

    protected function syncTitleFromFullName(): void
    {
        if (! $this->isDirty('full_name')) {
            return;
        }

        if (blank($this->full_name)) {
            $this->title = '求职简历';

            return;
        }

        $this->title = $this->full_name.'的简历';
    }

    protected function syncIsFreshGraduateFromWorkYearAndIdentity(): void
    {
        if ($this->isDirty('is_fresh_graduate') && ! $this->isDirty(['current_identity', 'work_years'])) {
            return;
        }

        $this->is_fresh_graduate = self::resolvesFreshGraduate($this->current_identity, $this->work_years) ? 1 : 0;
    }

    public static function resolvesFreshGraduate(?RcCurrentIdentity $identity, ?int $workYears): bool
    {
        if ($identity !== RcCurrentIdentity::Student) {
            return false;
        }

        return (int) ($workYears ?? 0) === 0;
    }

    protected function syncCurrentResidenceCityFromCode(): void
    {
        if (! $this->isDirty('current_city_code')) {
            return;
        }

        if (blank($this->current_city_code)) {
            $this->current_residence_city = null;

            return;
        }

        $this->current_residence_city = MetaService::make()->getCityFullName((string) $this->current_city_code);
    }

    protected function syncAgeFromBirthDate(): void
    {
        if (blank($this->birth_date)) {
            return;
        }

        if ($this->isDirty('age') && $this->age !== null) {
            return;
        }

        if ($this->age !== null && ! $this->isDirty('birth_date')) {
            return;
        }

        $this->age = min(max((int) Carbon::parse($this->birth_date)->age, 0), 120);
    }

    protected function syncBirthMonthFromBirthDate(): void
    {
        if (blank($this->birth_date)) {
            return;
        }

        if ($this->isDirty('birth_month') && filled($this->birth_month)) {
            return;
        }

        if (filled($this->birth_month) && ! $this->isDirty('birth_date')) {
            return;
        }

        $this->birth_month = Carbon::parse($this->birth_date)->format('Y-m');
    }

    protected function syncWorkStartDateFromWorkYear(): void
    {
        if ($this->work_years === null) {
            return;
        }

        if ($this->isDirty('work_start_date') && ! $this->isDirty('work_years')) {
            return;
        }

        if (filled($this->work_start_date) && ! $this->isDirty('work_years')) {
            return;
        }

        if ($this->isDirty('work_start_date') && filled($this->work_start_date)) {
            return;
        }

        $this->work_start_date = self::resolveWorkStartDateFromWorkYears((int) $this->work_years);
    }

    public static function resolveWorkStartDateFromWorkYears(int $workYears, ?Carbon $now = null): string
    {
        $years = min(max($workYears, 0), 80);
        $reference = ($now ?? Carbon::now())->copy()->startOfYear();

        return $reference->subYears($years)->format('Y-m-d');
    }

    protected function syncWorkYearsFromWorkStartDate(): void
    {
        if (blank($this->work_start_date)) {
            return;
        }

        if ($this->isDirty('work_years') && $this->work_years !== null) {
            return;
        }

        if ($this->work_years !== null && ! $this->isDirty('work_start_date')) {
            return;
        }

        $years = (int) Carbon::parse($this->work_start_date)->diffInYears(Carbon::now());

        $this->work_years = min(max($years, 0), 80);
    }

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'avatar' => AliyunOss::class.':oss,public,3600',
            'file_url' => AliyunOss::class.':oss,public,3600',
            'gender' => UserGender::class,
            'age' => 'integer',
            'marital_status' => RcMaritalStatus::class,
            'political_status' => RcPoliticalStatus::class,
            'current_identity' => RcCurrentIdentity::class,
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

    protected function birthDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => filled($value) ? Carbon::parse($value)->format('Y-m-d') : null,
            set: fn (mixed $value): ?string => filled($value) ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    protected function workStartDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => filled($value) ? Carbon::parse($value)->format('Y-m-d') : null,
            set: fn (mixed $value): ?string => filled($value) ? Carbon::parse($value)->format('Y-m-d') : null,
        );
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

    public function projects(): HasMany
    {
        return $this->hasMany(ResumeProject::class, 'resume_id');
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(ResumeTraining::class, 'resume_id');
    }

    public function languages(): HasMany
    {
        return $this->hasMany(ResumeLanguage::class, 'resume_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(ResumeSkill::class, 'resume_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(ResumeCertificate::class, 'resume_id');
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(ResumePortfolio::class, 'resume_id');
    }

    public function talentPoolMembers(): HasMany
    {
        return $this->hasMany(TalentPoolMember::class, 'resume_id');
    }

    public function statsDaily(): HasMany
    {
        return $this->hasMany(ResumeStatsDaily::class, 'resume_id');
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

    protected static function generateUniqueResumeNo(int $userId): string
    {
        do {
            $resumeNo = 'RC'.now()->format('YmdHis').strtoupper(Str::random(6));
        } while (static::query()->where('user_id', $userId)->where('resume_no', $resumeNo)->exists());

        return $resumeNo;
    }

    public function searchableAs(): string
    {
        return 'rc_resumes';
    }

    public function shouldBeSearchable(): bool
    {
        if ($this->trashed()) {
            return false;
        }

        $status = $this->status instanceof RcResumeStatus
            ? $this->status
            : RcResumeStatus::tryFrom((int) $this->status);

        return $status === RcResumeStatus::Normal;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['works', 'educations', 'intentions']);

        return [
            'user_id' => (int) $this->user_id,
            'resume_no' => $this->resume_no,
            'title' => $this->title,
            'full_name' => $this->full_name,
            'text_content' => $this->text_content,
            'gender' => $this->gender instanceof UserGender
                ? $this->gender->value
                : (int) $this->gender,
            'age' => $this->age,
            'work_years' => $this->work_years,
            'highest_education_level' => $this->highest_education_level instanceof RcEducationLevel
                ? $this->highest_education_level->value
                : $this->highest_education_level,
            'is_fresh_graduate' => (int) $this->is_fresh_graduate,
            'expected_salary_min' => $this->expected_salary_min !== null ? (float) $this->expected_salary_min : null,
            'expected_salary_max' => $this->expected_salary_max !== null ? (float) $this->expected_salary_max : null,
            'current_city_code' => $this->current_city_code,
            'current_residence_city' => $this->current_residence_city,
            'status' => $this->status instanceof RcResumeStatus
                ? $this->status->value
                : (int) $this->status,
            'is_primary' => (int) $this->is_primary,
            'company_names' => $this->works->pluck('company_name')->filter()->implode(' '),
            'positions' => $this->works->pluck('position')->filter()->implode(' '),
            'work_descriptions' => $this->works->pluck('description')->filter()->implode(' '),
            'school_names' => $this->educations->pluck('school_name')->filter()->implode(' '),
            'majors' => $this->educations->pluck('major')->filter()->implode(' '),
            'expected_city_codes' => $this->intentions
                ->pluck('expected_city_code')
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'updated_at' => ScoutQuery::timestamp($this->getAttributes()['updated_at'] ?? null),
        ];
    }
}
