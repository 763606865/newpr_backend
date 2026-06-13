<?php

namespace App\Models\Rc;

use App\Enums\RcOfferStatus;
use App\Enums\RcSalaryUnit;
use App\Models\Company;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘Offer表
 *
 * @property int $id 主键ID
 * @property int $receive_user_id 接收用户ID
 * @property int|null $receive_user_identity_id 接收身份ID
 * @property int $company_id 企业ID
 * @property int $application_id 投递ID
 * @property string $offer_no Offer编号
 * @property string|null $salary 确认薪资
 * @property int $salary_unit 薪资单位
 * @property bool $has_probation 是否有试用期
 * @property string|null $remuneration_note 薪酬说明
 * @property string|null $attendance_note 考勤说明
 * @property Carbon|null $entry_date 入职日期
 * @property Carbon|null $expire_date Offer过期日期
 * @property int $status 状态
 * @property Carbon|null $sent_at 发送时间
 * @property Carbon|null $replied_at 回复时间
 * @property string|null $note 备注
 * @property array<string, mixed>|null $extra 扩展字段（试用期详情等，结构因企业而异）
 * @property Carbon|null $email_sent_at 邮件发送时间
 * @property Carbon|null $sms_sent_at 短信发送时间
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read User $receiveUser 接收用户
 * @property-read UserIdentity|null $receiveUserIdentity 接收身份
 * @property-read Company $company 所属企业
 * @property-read Application $application 所属投递
 */
#[Table('rc_offers')]
#[Fillable([
    'receive_user_id',
    'receive_user_identity_id',
    'company_id',
    'application_id',
    'offer_no',
    'salary',
    'salary_unit',
    'has_probation',
    'remuneration_note',
    'attendance_note',
    'entry_date',
    'expire_date',
    'status',
    'sent_at',
    'replied_at',
    'note',
    'extra',
    'email_sent_at',
    'sms_sent_at',
])]
class Offer extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'salary_unit' => RcSalaryUnit::Month,
        'has_probation' => 0,
        'status' => RcOfferStatus::Draft,
    ];

    protected function casts(): array
    {
        return [
            'receive_user_id' => 'integer',
            'receive_user_identity_id' => 'integer',
            'company_id' => 'integer',
            'application_id' => 'integer',
            'salary' => 'decimal:2',
            'salary_unit' => RcSalaryUnit::class,
            'has_probation' => 'boolean',
            'entry_date' => 'date',
            'expire_date' => 'date',
            'status' => RcOfferStatus::class,
            'sent_at' => 'datetime',
            'replied_at' => 'datetime',
            'extra' => 'array',
            'email_sent_at' => 'datetime',
            'sms_sent_at' => 'datetime',
        ];
    }

    public function receiveUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receive_user_id');
    }

    public function receiveUserIdentity(): BelongsTo
    {
        return $this->belongsTo(UserIdentity::class, 'receive_user_identity_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function wasEmailSent(): bool
    {
        return $this->email_sent_at !== null;
    }

    public function wasSmsSent(): bool
    {
        return $this->sms_sent_at !== null;
    }
}
