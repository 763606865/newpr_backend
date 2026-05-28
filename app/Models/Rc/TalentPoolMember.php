<?php

namespace App\Models\Rc;

use App\Enums\RcTalentPoolMemberSourceType;
use App\Models\Company;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘人才库成员表
 *
 * @property int $id 主键ID
 * @property int $company_id 企业ID
 * @property int $talent_pool_id 人才库ID
 * @property int $candidate_user_id 候选人用户ID
 * @property int|null $resume_id 来源简历ID
 * @property int $source_type 来源类型
 * @property string|null $note 备注
 * @property Carbon|null $added_at 加入时间
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属企业
 * @property-read TalentPool $talentPool 所属人才库
 * @property-read User $candidateUser 候选人用户
 * @property-read Resume|null $resume 来源简历
 */
#[Table('rc_talent_pool_members')]
#[Fillable([
    'company_id',
    'talent_pool_id',
    'candidate_user_id',
    'resume_id',
    'source_type',
    'note',
    'added_at',
    'extra',
])]
class TalentPoolMember extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'source_type' => RcTalentPoolMemberSourceType::Manual,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'talent_pool_id' => 'integer',
            'candidate_user_id' => 'integer',
            'resume_id' => 'integer',
            'source_type' => RcTalentPoolMemberSourceType::class,
            'added_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function talentPool(): BelongsTo
    {
        return $this->belongsTo(TalentPool::class, 'talent_pool_id');
    }

    public function candidateUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }
}
