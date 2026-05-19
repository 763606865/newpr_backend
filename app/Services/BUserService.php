<?php

namespace App\Services;

use App\Models\BUser;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BUserService extends Service
{
    public function register(array $data): BUser
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

        return BUser::create($params);
    }

    /**
     * 合并用户信息
     */
    public function mergeAccount(BUser $BUser, BUser $BUser1, string $priority = 'last_login_at'): void
    {
        $master = Carbon::parse($BUser->$priority)->diff($BUser1->$priority) > 0 ? $BUser : $BUser1;
    }

    public function attachCompany(BUser $BUser, Company $company): void
    {
        $BUser->companies()->attach(
            ids: [
                $company->id,
            ]);
    }
}
