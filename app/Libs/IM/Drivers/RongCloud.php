<?php

namespace App\Libs\IM\Drivers;

class RongCloud extends AbstractDriver
{
    public function ping(): bool
    {
        // TODO: implement RongCloud ping
        return true;
    }

    public function sendMessage(string $from, string $to, string $message, array $options = []): array
    {
        // Implement RongCloud send message
        return ['success' => true, 'driver' => 'rongcloud'];
    }
}
