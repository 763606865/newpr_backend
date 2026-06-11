<?php

namespace App\Support;

use Illuminate\Support\Str;

class ContactMasker
{
    public static function maskPhone(?string $phone): ?string
    {
        if (! filled($phone)) {
            return $phone;
        }

        return Str::mask($phone, '*', 3, 4);
    }

    public static function maskEmail(?string $email): ?string
    {
        if (! filled($email)) {
            return $email;
        }

        $atPosition = strpos($email, '@');

        if ($atPosition === false || $atPosition <= 3) {
            return Str::mask($email, '*', 1, max(strlen($email) - 2, 1));
        }

        return Str::mask($email, '*', 3, $atPosition - 3);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function maskResumePayload(array $payload): array
    {
        if (array_key_exists('phone', $payload)) {
            $payload['phone'] = self::maskPhone(is_string($payload['phone']) ? $payload['phone'] : null);
        }

        if (array_key_exists('email', $payload)) {
            $payload['email'] = self::maskEmail(is_string($payload['email']) ? $payload['email'] : null);
        }

        return $payload;
    }
}
