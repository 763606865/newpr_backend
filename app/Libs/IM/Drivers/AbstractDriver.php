<?php

namespace App\Libs\IM\Drivers;

use App\Libs\IM\Contracts\ImClientInterface;

abstract class AbstractDriver implements ImClientInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function ping(): bool
    {
        // Default ping: assume available. Drivers may override.
        return true;
    }

    public function sendMessage(string $from, string $to, string $message, array $options = []): array
    {
        // Default not implemented; drivers should override
        return ['success' => false, 'message' => 'not_implemented'];
    }
}
