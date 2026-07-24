<?php

namespace App\Models\Rc;

use App\Enums\RcApplicationSourceType;
use App\Enums\RcApplicationStatus;
use App\Models\Company;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘投递表
 *
 * @property int $id 主键ID
 * @property int $company_id 企业ID
 * @property int $job_id 职位ID
 * @property int $candidate_user_id 候选人用户ID
 * @property int $resume_id 简历ID
 * @property int|null $current_stage_id 当前阶段ID
 * @property int $source_type 来源类型
 * @property int $status 投递状态
 * @property Carbon|null $applied_at 投递时间
 * @property Carbon|null $withdrawn_at 撤回时间
 * @property array<string, mixed>|null $resume_snapshot 投递时简历快照
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属企业
 * @property-read Job $job 所属职位
 * @property-read User $candidateUser 候选人用户
 * @property-read Resume|null $resume 简历
 * @property-read JobStage|null $currentStage 当前阶段
 */
#[Table('rc_applications')]
#[Fillable([
    'company_id',
    'job_id',
    'candidate_user_id',
    'resume_id',
    'current_stage_id',
    'source_type',
    'status',
    'applied_at',
    'withdrawn_at',
    'resume_snapshot',
    'extra',
])]
class Application extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'source_type' => RcApplicationSourceType::Direct,
        'status' => RcApplicationStatus::Pending,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'job_id' => 'integer',
            'candidate_user_id' => 'integer',
            'resume_id' => 'integer',
            'current_stage_id' => 'integer',
            'source_type' => RcApplicationSourceType::class,
            'status' => RcApplicationStatus::class,
            'applied_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'resume_snapshot' => 'array',
            'extra' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function candidateUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(JobStage::class, 'current_stage_id');
    }

    public function flows(): HasMany
    {
        return $this->hasMany(ApplicationFlow::class, 'application_id');
    }

    public function latestFlow(): HasOne
    {
        return $this->hasOne(ApplicationFlow::class, 'application_id')->latestOfMany();
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'application_id');
    }

    public function offer(): HasOne
    {
        return $this->hasOne(Offer::class, 'application_id');
    }
}
