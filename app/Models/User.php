<?php

namespace App\Models;

use App\Models\Oa\LeaveBalance;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
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
#[Fillable(['name', 'nickname', 'phone', 'email', 'gender', 'password', 'status', 'last_login_ip', 'last_login_at'])]
#[Hidden(['password'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $attributes = [
        'status' => 'active',
        'gender' => 0,
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
            'gender' => 'integer',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
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
        return false;
    }

    /**
     * 假期额度
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'user_id');
    }
}
