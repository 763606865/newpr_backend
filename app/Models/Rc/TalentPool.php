<?php

namespace App\Models\Rc;

use App\Enums\RcTalentPoolStatus;
use App\Models\Company;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘人才库表
 *
 * @property int $id 主键ID
 * @property int $company_id 企业ID
 * @property string $code 人才库编码
 * @property string $name 人才库名称
 * @property string|null $description 人才库描述
 * @property int $status 状态
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属企业
 */
#[Table('rc_talent_pools')]
#[Fillable(['company_id', 'code', 'name', 'description', 'status', 'extra'])]
class TalentPool extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => RcTalentPoolStatus::Enabled,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'status' => RcTalentPoolStatus::class,
            'extra' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TalentPoolMember::class, 'talent_pool_id');
    }

    public function candidateUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rc_talent_pool_members', 'talent_pool_id', 'candidate_user_id')
            ->withPivot(['company_id', 'resume_id', 'source_type', 'note', 'added_at'])
            ->withTimestamps();
    }
}
