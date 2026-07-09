<?php

namespace App\Libs\IM\Contracts;

interface ImClientInterface
{
    /**
     * Check connectivity / heartbeat
     */
    public function ping(): bool;

    /**
     * Send a message
     *
     * @return array driver response
     */
    public function sendMessage(string $from, string $to, string $message, array $options = []): array;

    /**
     * Create or update a user (external_user_id)
     *
     * @return array parsed response
     */
    public function createOrUpdateUser(array $payload): array;

    /**
     * List users
     *
     * @return array parsed response
     */
    public function listUsers(int $limit = 50): array;

    /**
     * Get single user by external id
     */
    public function getUser(string $externalUserId): array;

    /**
     * Update user status (active|disabled)
     */
    public function updateUserStatus(string $externalUserId, string $status): array;
}
