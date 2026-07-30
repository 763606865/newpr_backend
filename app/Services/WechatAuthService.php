<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserThirdPartyAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class WechatAuthService
{
    /** 微信小程序账号在三方账号表中的渠道标识。 */
    private const MINI_PROVIDER = 'wechat_mini';

    /** 微信开放平台 App 账号在三方账号表中的渠道标识。 */
    private const APP_PROVIDER = 'wechat_app';

    /**
     * 使用小程序登录凭证及手机号凭证完成注册或登录。
     *
     * @param  array{code:string, phone_code:string, nickname?:string|null, avatar?:string|null}  $data
     */
    public function loginMini(array $data): User
    {
        $identity = $this->requestMiniIdentity($data['code']);
        $phone = $this->requestMiniPhone($data['phone_code']);

        return $this->bindIdentityToPhone(
            provider: self::MINI_PROVIDER,
            appCode: $this->configuration('mini_app_id'),
            openId: $identity['openid'],
            unionId: $identity['unionid'] ?? null,
            phone: $phone,
            nickname: $data['nickname'] ?? null,
            avatar: $data['avatar'] ?? null,
            extra: [],
        );
    }

    /**
     * 使用微信开放平台 OAuth2 授权码登录。
     *
     * 未找到带手机号的历史绑定时，只缓存微信身份并返回一次性绑定令牌。
     *
     * @param  array{code:string, nickname?:string|null, avatar?:string|null}  $data
     * @return array{user: User|null, pending_token: string|null}
     */
    public function loginApp(array $data): array
    {
        $identity = $this->requestAppIdentity($data['code']);
        $account = $this->findAccount(self::APP_PROVIDER, $identity['openid'], $identity['unionid'] ?? null);

        if ($account?->user instanceof User && filled($account->user->phone)) {
            $this->updateProfile($account->user, $data['nickname'] ?? null, $data['avatar'] ?? null);
            $account->forceFill(['extra' => $this->identityExtra($data, $identity)])->save();

            return ['user' => $account->user, 'pending_token' => null];
        }

        $pendingToken = hash('sha256', Str::random(80));
        Cache::put($this->pendingCacheKey($pendingToken), [
            'provider' => self::APP_PROVIDER,
            'app_code' => $this->configuration('app_id'),
            'open_id' => $identity['openid'],
            'union_id' => $identity['unionid'] ?? null,
            'nickname' => $data['nickname'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'extra' => $this->identityExtra($data, $identity),
        ], now()->addSeconds((int) config('services.wechat.pending_token_ttl', 600)));

        return ['user' => null, 'pending_token' => $pendingToken];
    }

    /**
     * 将待绑定的 App 微信身份关联到手机号账号。
     *
     * 绑定成功后立即删除 pending_token，防止重复使用。
     */
    public function bindAppPhone(string $pendingToken, string $phone): User
    {
        $pending = Cache::get($this->pendingCacheKey($pendingToken));

        if (! is_array($pending)) {
            throw new RuntimeException('微信登录状态已失效，请重新授权。');
        }

        $user = $this->bindIdentityToPhone(
            provider: (string) $pending['provider'],
            appCode: (string) $pending['app_code'],
            openId: (string) $pending['open_id'],
            unionId: isset($pending['union_id']) ? (string) $pending['union_id'] : null,
            phone: $phone,
            nickname: isset($pending['nickname']) ? (string) $pending['nickname'] : null,
            avatar: isset($pending['avatar']) ? (string) $pending['avatar'] : null,
            extra: is_array($pending['extra'] ?? null) ? $pending['extra'] : [],
        );

        Cache::forget($this->pendingCacheKey($pendingToken));

        return $user;
    }

    /**
     * 判断 App 微信登录的待绑定状态是否仍有效。
     */
    public function hasPendingToken(string $pendingToken): bool
    {
        return Cache::has($this->pendingCacheKey($pendingToken));
    }

    /**
     * 调用小程序 auth.code2Session 换取微信身份。
     *
     * @return array{openid:string, session_key:string, unionid?:string}
     */
    private function requestMiniIdentity(string $code): array
    {
        $payload = Http::acceptJson()
            ->timeout(10)
            ->get('https://api.weixin.qq.com/sns/jscode2session', [
                'appid' => $this->configuration('mini_app_id'),
                'secret' => $this->configuration('mini_app_secret'),
                'js_code' => $code,
                'grant_type' => 'authorization_code',
            ])
            ->throw()
            ->json();

        $this->ensureWechatSucceeded($payload, ['openid', 'session_key']);

        return $payload;
    }

    /**
     * 使用 getPhoneNumber 动态凭证获取微信已验证手机号。
     */
    private function requestMiniPhone(string $phoneCode): string
    {
        $accessToken = Cache::remember('wechat:mini:access_token', now()->addSeconds(7000), function (): string {
            $payload = Http::acceptJson()
                ->timeout(10)
                ->get('https://api.weixin.qq.com/cgi-bin/token', [
                    'grant_type' => 'client_credential',
                    'appid' => $this->configuration('mini_app_id'),
                    'secret' => $this->configuration('mini_app_secret'),
                ])
                ->throw()
                ->json();

            $this->ensureWechatSucceeded($payload, ['access_token']);

            return (string) $payload['access_token'];
        });

        $payload = Http::acceptJson()
            ->timeout(10)
            ->post('https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token='.urlencode($accessToken), [
                'code' => $phoneCode,
            ])
            ->throw()
            ->json();

        $this->ensureWechatSucceeded($payload);
        $phone = data_get($payload, 'phone_info.purePhoneNumber');

        if (! is_string($phone) || $phone === '') {
            throw new RuntimeException('微信未返回有效手机号。');
        }

        return $phone;
    }

    /**
     * 调用微信开放平台 OAuth2 接口换取 App 微信身份。
     *
     * @return array<string, mixed>
     */
    private function requestAppIdentity(string $code): array
    {
        $payload = Http::acceptJson()
            ->timeout(10)
            ->get('https://api.weixin.qq.com/sns/oauth2/access_token', [
                'appid' => $this->configuration('app_id'),
                'secret' => $this->configuration('app_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
            ])
            ->throw()
            ->json();

        $this->ensureWechatSucceeded($payload, ['openid']);

        return $payload;
    }

    /**
     * 原子地解析手机号账号并保存微信身份绑定。
     *
     * unionid 可跨小程序与 App 匹配；若微信身份与手机号已属于不同用户，
     * 则拒绝自动合并，避免账号被意外接管。
     *
     * @param  array<string, mixed>  $extra
     */
    private function bindIdentityToPhone(
        string $provider,
        string $appCode,
        string $openId,
        ?string $unionId,
        string $phone,
        ?string $nickname,
        ?string $avatar,
        array $extra,
    ): User {
        return DB::transaction(function () use ($provider, $appCode, $openId, $unionId, $phone, $nickname, $avatar, $extra): User {
            $account = $this->findAccount($provider, $openId, $unionId, true);
            $phoneUser = User::query()->where('phone', $phone)->lockForUpdate()->first();

            if ($account && $phoneUser && $account->user_id !== $phoneUser->id) {
                throw new RuntimeException('该微信账号与手机号已分别绑定其他账号。');
            }

            $user = $account?->user ?? $phoneUser ?? UserService::make()->register([
                'phone' => $phone,
                'nickname' => $nickname,
            ]);

            $this->updateProfile($user, $nickname, $avatar);

            UserThirdPartyAccount::query()->updateOrCreate(
                ['provider' => $provider, 'open_id' => $openId],
                [
                    'user_id' => $user->id,
                    'app_code' => $appCode,
                    'union_id' => $unionId,
                    'extra' => array_merge($extra, compact('nickname', 'avatar')),
                    'bound_at' => now(),
                ],
            );

            return $user->refresh();
        });
    }

    /**
     * 按当前渠道 openid 或跨渠道 unionid 查找已有绑定。
     */
    private function findAccount(string $provider, string $openId, ?string $unionId, bool $lock = false): ?UserThirdPartyAccount
    {
        $query = UserThirdPartyAccount::query()
            ->with('user')
            ->where(function ($query) use ($provider, $openId, $unionId): void {
                $query->where(function ($query) use ($provider, $openId): void {
                    $query->where('provider', $provider)->where('open_id', $openId);
                });

                if (filled($unionId)) {
                    $query->orWhere('union_id', $unionId);
                }
            });

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    /**
     * 仅填充用户尚未设置的微信昵称和头像，不覆盖用户主动维护的资料。
     */
    private function updateProfile(User $user, ?string $nickname, ?string $avatar): void
    {
        $attributes = [];

        if (filled($nickname) && blank($user->nickname)) {
            $attributes['nickname'] = $nickname;
        }

        if (filled($avatar) && blank($user->getRawOriginal('avatar'))) {
            $attributes['avatar'] = $avatar;
        }

        if ($attributes !== []) {
            $user->forceFill($attributes)->save();
        }
    }

    /**
     * 校验微信业务响应及必须字段。
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $required
     */
    private function ensureWechatSucceeded(array $payload, array $required = []): void
    {
        if (isset($payload['errcode']) && (int) $payload['errcode'] !== 0) {
            throw new RuntimeException('微信接口调用失败：'.((string) ($payload['errmsg'] ?? $payload['errcode'])));
        }

        foreach ($required as $key) {
            if (! filled($payload[$key] ?? null)) {
                throw new RuntimeException('微信接口返回数据不完整。');
            }
        }
    }

    /**
     * 生成可安全持久化的微信资料快照，不保存 access_token 或 session_key。
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $identity
     * @return array<string, mixed>
     */
    private function identityExtra(array $data, array $identity): array
    {
        return array_filter([
            'nickname' => $data['nickname'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'scope' => $identity['scope'] ?? null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function configuration(string $key): string
    {
        $value = config("services.wechat.{$key}");

        if (! is_string($value) || $value === '') {
            throw new RuntimeException('微信登录配置不完整。');
        }

        return $value;
    }

    private function pendingCacheKey(string $token): string
    {
        return 'wechat:app:pending:'.$token;
    }
}
