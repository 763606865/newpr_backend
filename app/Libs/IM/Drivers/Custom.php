<?php

namespace App\Libs\IM\Drivers;

class Custom extends AbstractDriver
{
    public function ping(): bool
    {
        // For custom self-hosted IM, maybe ping an endpoint
        if (! empty($this->config['end_point'])) {
            return true;
        }

        return true;
    }

    public function sendMessage(string $from, string $to, string $message, array $options = []): array
    {
        // User should implement their custom logic via extending this driver or passing hooks in config
        return ['success' => true, 'driver' => 'custom'];
    }
}
