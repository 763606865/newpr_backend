<?php

namespace App\Models\Rc;

use App\Enums\RcInterviewMode;
use App\Enums\RcInterviewResult;
use App\Enums\RcInterviewStatus;
use App\Models\Company;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘面试表
 *
 * @property int $id 主键ID
 * @property int $company_id 企业ID
 * @property int $application_id 投递ID
 * @property int|null $stage_id 阶段ID
 * @property int|null $interviewer_user_id 面试官用户ID
 * @property string|null $interviewer_name 面试官姓名
 * @property Carbon|null $interview_at 面试时间
 * @property int|null $duration_mins 时长(分钟)
 * @property int $mode 面试方式
 * @property int $status 状态
 * @property int $result 结果
 * @property string|null $location 面试地点
 * @property string|null $meeting_url 线上会议地址
 * @property string|null $note 面试备注
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属企业
 * @property-read Application $application 所属投递
 */
#[Table('rc_interviews')]
#[Fillable([
    'company_id',
    'application_id',
    'stage_id',
    'interviewer_user_id',
    'interviewer_name',
    'interview_at',
    'duration_mins',
    'mode',
    'status',
    'result',
    'location',
    'meeting_url',
    'note',
    'extra',
])]
class Interview extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'mode' => RcInterviewMode::Online,
        'status' => RcInterviewStatus::Pending,
        'result' => RcInterviewResult::Pending,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'application_id' => 'integer',
            'stage_id' => 'integer',
            'interviewer_user_id' => 'integer',
            'interview_at' => 'datetime',
            'duration_mins' => 'integer',
            'mode' => RcInterviewMode::class,
            'status' => RcInterviewStatus::class,
            'result' => RcInterviewResult::class,
            'extra' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(JobStage::class, 'stage_id');
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_user_id');
    }
}
