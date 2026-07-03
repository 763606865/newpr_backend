<?php

namespace App\Models\Rc;

use App\Models\Company;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 求职者企业黑名单表
 *
 * 该黑名单仅用于求职者屏蔽企业推荐/曝光，不代表平台对企业的违规封禁状态。
 *
 * @property int $id 主键ID
 * @property int $user_id 求职者用户ID
 * @property int $company_id 被屏蔽企业ID
 * @property string|null $remark 备注
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read User $user 求职者用户
 * @property-read Company $company 被屏蔽企业
 *
 * @method static Builder forUser(User $user)
 * @method static Builder forCompany(Company $company)
 */
#[Table('rc_user_company_blacklists')]
#[Fillable([
    'user_id',
    'company_id',
    'remark',
])]
class UserCompanyBlacklist extends Model
{
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'company_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forUser(Builder $query, User $user): void
    {
        $query->where($this->getTable().'.user_id', $user->getKey());
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forCompany(Builder $query, Company $company): void
    {
        $query->where($this->getTable().'.company_id', $company->getKey());
    }
}
