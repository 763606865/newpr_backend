<?php

namespace App\Models\Rc;

use App\Models\Company;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 招聘方简历收藏表
 *
 * @property int $id 主键ID
 * @property int $user_id 招聘方用户ID
 * @property int $company_id 企业ID
 * @property int $resume_id 简历ID
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read User $user 收藏用户
 * @property-read Company $company 所属企业
 * @property-read Resume $resume 收藏简历
 */
#[Table('rc_resume_favorites')]
#[Fillable([
    'user_id',
    'company_id',
    'resume_id',
])]
class ResumeFavorite extends Model
{
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'company_id' => 'integer',
            'resume_id' => 'integer',
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

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }
}
