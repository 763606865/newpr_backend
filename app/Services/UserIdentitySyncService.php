<?php

namespace App\Services;

use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class UserIdentitySyncService
{
    public const HUB_SYSTEM = 'hub';

    public function resolveByHubUserId(string $hubUserId): ?User
    {
        return User::query()->where('hub_user_id', $hubUserId)->first();
    }

    public function syncHubUser(array $payload): User
    {
        $hubUserId = trim((string) ($payload['hub_user_id'] ?? ''));

        if ($hubUserId === '') {
            throw new InvalidArgumentException('hub_user_id is required.');
        }

        return DB::transaction(function () use ($payload, $hubUserId): User {
            $user = $this->resolveByHubUserId($hubUserId)
                ?? $this->resolveByPhoneOrEmail($payload)
                ?? new User;

            $user->forceFill([
                'uuid' => $user->getAttribute('uuid') ?: (string) Str::uuid(),
                'hub_user_id' => $hubUserId,
                'name' => Arr::get($payload, 'name'),
                'nickname' => Arr::get($payload, 'nickname'),
                'phone' => Arr::get($payload, 'phone'),
                'email' => Arr::get($payload, 'email', ''),
                'avatar' => Arr::get($payload, 'avatar', ''),
                'gender' => Arr::get($payload, 'gender', 0),
                'status' => Arr::get($payload, 'status', $user->status ?? 'active'),
            ]);

            $user->save();

            $this->bindHubIdentity($user, $hubUserId, $payload);

            return $user->refresh();
        });
    }

    public function bindHubIdentity(User $user, string $hubUserId, array $payload = []): UserIdentity
    {
        $bind = UserIdentity::query()
            ->where('bind_system', self::HUB_SYSTEM)
            ->where('external_user_id', $hubUserId)
            ->first()
            ?? new UserIdentity;

        $bind->forceFill([
            'bind_system' => self::HUB_SYSTEM,
            'external_user_id' => $hubUserId,
            'user_id' => $user->id,
            'external_app_code' => Arr::get($payload, 'external_app_code', 'hub'),
            'external_union_id' => Arr::get($payload, 'external_union_id'),
            'is_primary' => 1,
            'status' => Arr::get($payload, 'bind_status', 1),
            'extra' => Arr::get($payload, 'extra'),
        ]);

        $bind->save();

        UserIdentity::query()
            ->where('user_id', $user->id)
            ->where('id', '!=', $bind->id)
            ->update(['is_primary' => 0]);

        return $bind->refresh();
    }

    private function resolveByPhoneOrEmail(array $payload): ?User
    {
        $phone = trim((string) Arr::get($payload, 'phone', ''));
        $email = trim((string) Arr::get($payload, 'email', ''));

        if ($phone === '' && $email === '') {
            return null;
        }

        $query = User::query();

        $query->where(function ($builder) use ($phone, $email): void {
            if ($phone !== '') {
                $builder->where('phone', $phone);
            }

            if ($email !== '') {
                $method = $phone !== '' ? 'orWhere' : 'where';
                $builder->{$method}('email', $email);
            }
        });

        return $query->first();
    }
}
