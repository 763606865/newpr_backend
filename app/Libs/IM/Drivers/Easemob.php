<?php

namespace App\Libs\IM\Drivers;

class Easemob extends AbstractDriver
{
    public function ping(): bool
    {
        // TODO: implement Easemob ping
        return true;
    }

    public function sendMessage(string $from, string $to, string $message, array $options = []): array
    {
        return ['success' => true, 'driver' => 'easemob'];
    }
}
