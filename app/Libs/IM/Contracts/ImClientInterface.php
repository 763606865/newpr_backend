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
}
