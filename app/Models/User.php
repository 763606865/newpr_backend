<?php

namespace App\Models;

use App\Enums\UserGender;
use App\Enums\UserStatus;
use App\Models\Oa\LeaveBalance;
use App\Models\Rc\Resume;
use App\Models\Rc\UserIdentity;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * 用户表
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $nickname
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $hub_user_id
 * @property int $gender
 * @property string|null $avatar
 * @property string $password
 * @property string $status
 * @property string|null $last_login_ip
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('users')]
#[Fillable(['name', 'nickname', 'phone', 'email', 'hub_user_id', 'gender', 'password', 'status', 'last_login_ip', 'last_login_at', 'extra'])]
#[Hidden(['password'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $attributes = [
        'email' => '',
        'phone' => '',
        'avatar' => '',
        'status' => UserStatus::Active->value,
        'gender' => UserGender::Unknown->value,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (blank($user->getRawOriginal('uuid')) && blank($user->getAttribute('uuid'))) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => UserGender::class,
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'extra' => 'json',
        ];
    }

    protected function uuid(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): ?string {
                if (blank($value)) {
                    return null;
                }

                $hex = bin2hex($value);

                return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
            },
            set: function (mixed $value): ?string {
                if (blank($value)) {
                    return null;
                }

                return hex2bin((string) str($value)->replace('-', '')) ?: null;
            },
        );
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * 假期额度
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'user_id');
    }

    /**
     * 员工信息
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'user_id');
    }

    /**
     * 用户可访问的公司
     */
    public function companies(): HasManyThrough
    {
        return $this->hasManyThrough(Company::class, Employee::class, 'user_id', 'id', 'id', 'company_id');
    }

    /**
     * 候选人简历
     */
    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class, 'user_id');
    }

    /**
     * 主简历
     */
    public function primaryResume(): HasOne
    {
        return $this->hasOne(Resume::class, 'user_id')->where('is_primary', 1);
    }

    /**
     * 候选人档案（兼容方法，返回主简历）
     */
    public function candidateProfile(): HasOne
    {
        return $this->primaryResume();
    }

    /**
     * 用户身份列表
     */
    public function identities(): HasMany
    {
        return $this->hasMany(UserIdentity::class, 'user_id');
    }

    /**
     * 主身份
     */
    public function defaultIdentity(): HasOne
    {
        return $this->hasOne(UserIdentity::class, 'user_id')->where('is_default', 1);
    }
}
