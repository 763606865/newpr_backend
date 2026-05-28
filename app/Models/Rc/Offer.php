<?php

namespace App\Models\Rc;

use App\Enums\RcOfferStatus;
use App\Enums\RcSalaryUnit;
use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘Offer表
 *
 * @property int $id 主键ID
 * @property int $company_id 企业ID
 * @property int $application_id 投递ID
 * @property string $offer_no Offer编号
 * @property string|null $salary_min 最低薪资
 * @property string|null $salary_max 最高薪资
 * @property int $salary_unit 薪资单位
 * @property Carbon|null $entry_date 入职日期
 * @property Carbon|null $expire_date Offer过期日期
 * @property int $status 状态
 * @property Carbon|null $sent_at 发送时间
 * @property Carbon|null $replied_at 回复时间
 * @property string|null $note 备注
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属企业
 * @property-read Application $application 所属投递
 */
#[Table('rc_offers')]
#[Fillable([
    'company_id',
    'application_id',
    'offer_no',
    'salary_min',
    'salary_max',
    'salary_unit',
    'entry_date',
    'expire_date',
    'status',
    'sent_at',
    'replied_at',
    'note',
    'extra',
])]
class Offer extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'salary_unit' => RcSalaryUnit::Month,
        'status' => RcOfferStatus::Draft,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'application_id' => 'integer',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'salary_unit' => RcSalaryUnit::class,
            'entry_date' => 'date',
            'expire_date' => 'date',
            'status' => RcOfferStatus::class,
            'sent_at' => 'datetime',
            'replied_at' => 'datetime',
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
}
