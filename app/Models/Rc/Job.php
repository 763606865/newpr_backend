<?php

namespace App\Models\Rc;

use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Enums\RcSalaryUnit;
use App\Models\Company;
use App\Models\Department;
use App\Models\Model;
use App\Models\User;
use App\Support\ScoutQuery;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * 招聘职位表
 *
 * @property int $id 主键ID
 * @property int $company_id 企业ID
 * @property int|null $department_id 部门ID
 * @property string|null $position_code 岗位编码（关联 rc_positions.code）
 * @property int|null $creator_user_id 创建人用户ID
 * @property string $code 职位编码
 * @property string $title 职位名称
 * @property int $employment_type 用工类型
 * @property string|null $city_code 工作城市编码
 * @property string|null $workplace 工作地点
 * @property string|null $salary_min 最低薪资
 * @property string|null $salary_max 最高薪资
 * @property int $salary_unit 薪资单位
 * @property int|null $experience_min 最低经验年限
 * @property int|null $experience_max 最高经验年限
 * @property int|null $education_level 最低学历要求
 * @property int $headcount 招聘人数
 * @property string|null $description 职位描述
 * @property string|null $requirement 职位要求
 * @property string|null $benefit 福利待遇
 * @property int $status 状态
 * @property Carbon|null $published_at 发布时间
 * @property Carbon|null $expired_at 过期时间
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属企业
 * @property-read Department|null $department 所属部门
 * @property-read Position|null $position 常用职位（字典）
 * @property-read User|null $creator 创建人
 */
#[Table('rc_jobs')]
#[Fillable([
    'company_id',
    'department_id',
    'position_code',
    'creator_user_id',
    'code',
    'title',
    'employment_type',
    'city_code',
    'workplace',
    'salary_min',
    'salary_max',
    'salary_unit',
    'experience_min',
    'experience_max',
    'education_level',
    'headcount',
    'description',
    'requirement',
    'benefit',
    'status',
    'published_at',
    'expired_at',
    'extra',
])]
class Job extends Model
{
    use Searchable, SoftDeletes;

    protected $attributes = [
        'employment_type' => RcJobEmploymentType::FullTime,
        'salary_unit' => RcSalaryUnit::Month,
        'headcount' => 1,
        'status' => RcJobStatus::Draft,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'department_id' => 'integer',
            'creator_user_id' => 'integer',
            'employment_type' => RcJobEmploymentType::class,
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'salary_unit' => RcSalaryUnit::class,
            'experience_min' => 'integer',
            'experience_max' => 'integer',
            'education_level' => 'integer',
            'headcount' => 'integer',
            'status' => RcJobStatus::class,
            'published_at' => 'datetime:Y-m-d H:i:s',
            'expired_at' => 'datetime:Y-m-d H:i:s',
            'extra' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_code', 'code');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'job_id');
    }

    public function statsDaily(): HasMany
    {
        return $this->hasMany(JobStatsDaily::class, 'job_id');
    }

    public function searchableAs(): string
    {
        return 'rc_jobs';
    }

    public function shouldBeSearchable(): bool
    {
        return ! $this->trashed();
    }

    public function isPubliclySearchable(): bool
    {
        $status = $this->status instanceof RcJobStatus
            ? $this->status
            : RcJobStatus::tryFrom((int) $this->status);

        if ($status !== RcJobStatus::Published) {
            return false;
        }

        if ($this->expired_at === null) {
            return true;
        }

        $expiredAt = ScoutQuery::timestamp($this->expired_at);

        return $expiredAt === null || $expiredAt >= now()->getTimestamp();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['company', 'position']);

        $extra = $this->extra ?? [];
        $keywords = collect($extra['keywords'] ?? [])
            ->filter(static fn (mixed $keyword): bool => filled($keyword))
            ->map(static fn (mixed $keyword): string => trim((string) $keyword))
            ->values()
            ->all();

        return [
            'company_id' => (int) $this->company_id,
            'company_name' => $this->company?->name,
            'department_id' => $this->department_id !== null ? (int) $this->department_id : null,
            'position_code' => $this->position_code,
            'position_name' => $this->position?->name,
            'code' => $this->code,
            'title' => $this->title,
            'employment_type' => $this->employment_type instanceof RcJobEmploymentType
                ? $this->employment_type->value
                : (int) $this->employment_type,
            'city_code' => $this->city_code,
            'workplace' => $this->workplace,
            'salary_min' => $this->salary_min !== null ? (float) $this->salary_min : null,
            'salary_max' => $this->salary_max !== null ? (float) $this->salary_max : null,
            'salary_unit' => $this->salary_unit instanceof RcSalaryUnit
                ? $this->salary_unit->value
                : (int) $this->salary_unit,
            'experience_min' => $this->experience_min,
            'experience_max' => $this->experience_max,
            'education_level' => $this->education_level,
            'headcount' => (int) $this->headcount,
            'description' => $this->description,
            'requirement' => $this->requirement,
            'benefit' => $this->benefit,
            'keywords' => implode(' ', $keywords),
            'status' => $this->status instanceof RcJobStatus
                ? $this->status->value
                : (int) $this->status,
            'is_public' => $this->isPubliclySearchable() ? 1 : 0,
            'published_at' => ScoutQuery::timestamp($this->published_at),
            'expired_at' => ScoutQuery::timestamp($this->expired_at),
            'updated_at' => ScoutQuery::timestamp($this->getAttributes()['updated_at'] ?? null),
        ];
    }
}
