<?php

namespace App\Services;

use App\Libs\Facades\Jucai;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Random\RandomException;

class VerificationCodeService extends Service
{
    /**
     * 发送短信验证码
     *
     * @throws RandomException
     */
    public function send(string $type, string $account, string $scene): array
    {
        $normalizedAccount = $this->normalizeAccount($type, $account);
        $throttleKey = $this->buildThrottleKey($type, $normalizedAccount, $scene);

        if (Cache::has($throttleKey)) {
            return [
                'sent' => false,
                'message' => '验证码发送过于频繁，请稍后再试。',
                'expires_in' => 300,
            ];
        }

        $code = (string) random_int(100000, 999999);
        $payload = [
            'code' => $code,
            'scene' => $scene,
            'account' => $normalizedAccount,
        ];

        Cache::put($this->buildCacheKey($type, $normalizedAccount, $scene), $payload, now()->addSeconds(300));
        Cache::put($throttleKey, true, now()->addSeconds(60));

        $this->deliver($type, $normalizedAccount, $code, $scene);

        return [
            'sent' => true,
            'message' => '验证码已发送。',
            'expires_in' => 300,
            'code' => app()->isLocal() ? $code : null,
        ];
    }

    public function verify(string $type, string $account, string $scene, string $code, bool $forget = true): bool
    {
        if (
            config('app.env') !== 'production' &&
            config('app.debug') &&
            in_array($account, config('app.skip_accounts'), true)
        ) {
            return true;
        }
        $normalizedAccount = $this->normalizeAccount($type, $account);
        $cacheKey = $this->buildCacheKey($type, $normalizedAccount, $scene);
        $payload = Cache::get($cacheKey);

        if (! is_array($payload)) {
            return false;
        }

        $verified = hash_equals((string) ($payload['code'] ?? ''), $code);

        if ($verified && $forget) {
            Cache::forget($cacheKey);
        }

        return $verified;
    }

    /**
     * @throws BindingResolutionException
     */
    private function deliver(string $type, string $account, string $code, string $scene): void
    {
        if ($type === 'email') {
            Mail::raw(
                sprintf('您的%s验证码为：%s，有效期5分钟。', $this->sceneLabel($scene), $code),
                static function ($message) use ($account): void {
                    $message->to($account)->subject('验证码通知');
                }
            );

            return;
        }

        if ($type === 'phone') {
            $driver = config('sms.driver');
            if ($driver === 'jucai') {
                $config = config('sms.jucai');
                Jucai::sms()->send($config, [
                    'mobile' => $account,
                    'signature' => '【中测高科人才测评】',
                    'tpId' => config('sms.jucai.template_id'),
                    'tpContent' => [
                        'others' => Str::mask($account, '*', 3, strlen($account)-4),
                        'valid_code' => $code
                    ],
                ]);
            }
        }

        Log::info('auth_verification_code_sent', [
            'type' => $type,
            'account' => $account,
            'scene' => $scene,
            'code' => $code,
        ]);
    }

    private function buildCacheKey(string $type, string $account, string $scene): string
    {
        return sprintf('auth:verification:%s:%s:%s', $scene, $type, md5($account));
    }

    private function buildThrottleKey(string $type, string $account, string $scene): string
    {
        return sprintf('auth:verification_throttle:%s:%s:%s', $scene, $type, md5($account));
    }

    private function normalizeAccount(string $type, string $account): string
    {
        return $type === 'email'
            ? mb_strtolower(trim($account))
            : trim($account);
    }

    private function sceneLabel(string $scene): string
    {
        return match ($scene) {
            'forgot_password' => '重置密码',
            default => '登录',
        };
    }
}
