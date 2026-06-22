<?php

namespace App\Support;

class SchoolActivityInviteCode
{
    private const PREFIX = 'rc-school-activity:';

    public static function encode(int $activityId): string
    {
        $payload = self::PREFIX.$activityId;
        $token = base64_encode($payload.'.'.hash_hmac('sha256', $payload, (string) config('app.key')));

        return rtrim(strtr($token, '+/', '-_'), '=');
    }

    public static function decode(string $inviteCode): ?int
    {
        $normalized = strtr($inviteCode, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);

        if ($decoded === false || ! str_contains($decoded, '.')) {
            return null;
        }

        [$payload, $signature] = explode('.', $decoded, 2);

        if (! str_starts_with($payload, self::PREFIX)) {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', $payload, (string) config('app.key'));

        if (! hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $activityId = (int) substr($payload, strlen(self::PREFIX));

        return $activityId > 0 ? $activityId : null;
    }
}
