<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UserService extends Service
{
    public function register(array $data): User
    {
        $params = [
            'uuid' => (string) Str::uuid(),
            'name' => $data['name'] ?? null,
            'nickname' => $data['nickname'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? '',
            'last_login_ip' => request()->ip(),
            'last_login_at' => Carbon::now(),
        ];

        return User::create($params);
    }

    /**
     * 合并用户信息
     */
    public function mergeAccount(User $user, User $user1, string $priority = 'last_login_at'): void
    {
        $master = Carbon::parse($user->$priority)->diff($user1->$priority) > 0 ? $user : $user1;
    }
}
