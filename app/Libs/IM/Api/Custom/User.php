<?php

namespace App\Libs\IM\Api\Custom;

use App\Libs\IM\Api\AbstractApi;

class User extends AbstractApi
{
    public function createOrUpdateUser(array $payload): array
    {
        return $this->driver->post('/api/users', ['json' => $payload, 'timeout' => 5])->json();
    }

    public function getUser(string $externalUserId): array
    {
        return $this->driver->get('/api/users/' . rawurlencode($externalUserId), ['timeout' => 5])->json();
    }

    public function updateUser(string $externalUserId, array $payload): array
    {
        return $this->driver->put('/api/users/' . rawurlencode($externalUserId), ['json' => $payload, 'timeout' => 5])->json();
    }

    public function getImToken(string $externalUserId): array
    {
        return $this->driver->post('/api/users/' . rawurlencode($externalUserId) . '/token', ['timeout' => 5])->json();
    }
}
