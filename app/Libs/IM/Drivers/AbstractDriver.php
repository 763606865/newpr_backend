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

    // Default user management methods - drivers should override if supported
    public function createOrUpdateUser(array $payload): array
    {
        return ['success' => false, 'message' => 'not_implemented'];
    }

    public function listUsers(int $limit = 50): array
    {
        return ['success' => false, 'message' => 'not_implemented'];
    }

    public function getUser(string $externalUserId): array
    {
        return ['success' => false, 'message' => 'not_implemented'];
    }

    public function updateUserStatus(string $externalUserId, string $status): array
    {
        return ['success' => false, 'message' => 'not_implemented'];
    }
}
