<?php

namespace App\Models\Rc;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Model;
use App\Models\User;
use App\Services\IMService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * 招聘用户身份表
 *
 * @property int $id 主键ID
 * @property int $user_id 本地用户ID
 * @property string|null $organization_type 所属机构类型（多态）
 * @property int|null $organization_id 所属机构ID（多态）
 * @property int $identity_type 身份类型
 * @property string $identity_name 身份名称
 * @property string|null $organization_name 所属机构名称
 * @property string|null $job_title 头衔/岗位名称
 * @property int $is_default 是否默认身份
 * @property int $status 状态
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read User $user 所属用户
 * @property-read \Illuminate\Database\Eloquent\Model|null $organization 所属机构（多态）
 * @property-read bool $has_basic_info 是否包含基础信息
 * @property-read string $external_user_id 外部用户ID
 */
#[Table('rc_user_identities')]
#[Fillable([
    'user_id',
    'organization_type',
    'organization_id',
    'identity_type',
    'identity_name',
    'organization_name',
    'job_title',
    'is_default',
    'status',
    'extra',
])]
class UserIdentity extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'is_default' => 0,
        'status' => RcIdentityStatus::Enabled,
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'organization_id' => 'integer',
            'identity_type' => RcIdentityType::class,
            'is_default' => 'integer',
            'status' => RcIdentityStatus::class,
            'extra' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $identity): void {
            IMService::make()->createOrUpdate($identity);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function organization(): MorphTo
    {
        return $this->morphTo();
    }

    #[Scope]
    protected function withBasicInfoFlags(Builder $query): void
    {
        $query->select($this->getTable().'.*')
            ->selectRaw(
                'exists (select 1 from rc_resumes where rc_resumes.user_id = '.$this->getTable().'.user_id and rc_resumes.deleted_at is null) as jobseeker_has_resume'
            )
            ->selectRaw(
                'case when '.$this->getTable().'.identity_type = ? then exists (select 1 from rc_resumes where rc_resumes.user_id = '.$this->getTable().'.user_id and rc_resumes.deleted_at is null) else 0 end as has_basic_info_cached',
                [RcIdentityType::JobSeeker->value]
            );
    }

    protected function hasBasicInfo(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes): bool {
                $identityType = $this->identity_type;

                if (! $identityType instanceof RcIdentityType) {
                    return false;
                }

                return match ($identityType) {
                    RcIdentityType::JobSeeker => $this->resolveJobSeekerHasBasicInfo($attributes),
                    RcIdentityType::Recruiter,
                    RcIdentityType::CampusManager,
                    RcIdentityType::GovernmentManager => filled($this->organization_id),
                    RcIdentityType::Headhunter => true,
                };
            },
        );
    }

    protected function externalUserId(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes): string {
                $seed = (string) Config::get('app.name', '') . '|' . $attributes['id'];
                return hash('sha256', $seed);
            },
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolveJobSeekerHasBasicInfo(array $attributes): bool
    {
        if (Arr::exists($attributes, 'has_basic_info_cached')) {
            return (bool) $attributes['has_basic_info_cached'];
        }

        if (Arr::exists($attributes, 'jobseeker_has_resume')) {
            return (bool) $attributes['jobseeker_has_resume'];
        }

        return Resume::query()
            ->where('user_id', $this->user_id)
            ->exists();
    }
}
