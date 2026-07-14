<?php

namespace App\Libs\IM\Api;

use App\Libs\IM\Drivers\AbstractDriver;
use App\Libs\IM\IMException;

abstract class AbstractApi
{
    public function __construct(protected AbstractDriver $driver)
    {

    }

    public function getDriver(): AbstractDriver
    {
        return $this->driver;
    }

    protected function handleResponse(array $response): array
    {
        if (!isset($response['code'], $response['data']) || $response['code'] !== 200) {
            throw new IMException("IM API Error: " . ($response['message'] ?? 'Unknown error'));
        }
        return $response['data'];
    }
}
