<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\Pivot\CompanyBUsers;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * B端用户表
 *
 * @property int $id
 * @property string $name
 * @property string|null $nickname
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $avatar
 * @property string $password
 * @property string $status
 * @property string|null $last_login_ip
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'phone', 'email', 'avatar', 'password', 'status', 'last_login_ip', 'last_login_at', 'extra'])]
#[Hidden(['password'])]
class BUser extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $attributes = [
        'email' => '',
        'phone' => '',
        'avatar' => '',
        'status' => UserStatus::Active->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'extra' => 'json',
        ];
    }

    /**
     * 用户可访问的公司
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, CompanyBUsers::class, 'b_user_id', 'company_id')
            ->withPivot(['status', 'last_login_ip', 'last_login_at'])
            ->wherePivot('status', '=', 1)
            ->orderByPivot('last_login_at', 'desc');
    }
}
