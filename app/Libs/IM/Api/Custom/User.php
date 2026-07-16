<?php

namespace App\Libs\IM\Api\Custom;

use App\Libs\IM\Api\AbstractApi;
use App\Libs\IM\IMException;
use Illuminate\Http\Client\ConnectionException;

/**
 * 用户相关
 */
class User extends AbstractApi
{
    /**
     * @throws IMException
     * @throws ConnectionException
     */
    public function createOrUpdateUser(array $payload): array
    {
        $json = $this->driver->post('/api/users', ['json' => $payload, 'timeout' => 5])->json();

        return $this->handleResponse($json);
    }

    /**
     * @throws IMException
     * @throws ConnectionException
     */
    public function getUser(string $externalUserId): array
    {
        $json = $this->driver->get('/api/users/'.rawurlencode($externalUserId), ['timeout' => 5])->json();

        return $this->handleResponse($json);
    }

    /**
     * @throws IMException
     * @throws ConnectionException
     */
    public function updateUser(string $externalUserId, array $payload): array
    {
        $json = $this->driver->put('/api/users/'.rawurlencode($externalUserId), ['json' => $payload, 'timeout' => 5])->json();

        return $this->handleResponse($json);
    }

    /**
     * @throws IMException
     * @throws ConnectionException
     */
    public function getImToken(string $externalUserId): array
    {
        $json = $this->driver->post('/api/users/'.rawurlencode($externalUserId).'/token', ['timeout' => 5])->json();

        return $this->handleResponse($json);
    }
}
