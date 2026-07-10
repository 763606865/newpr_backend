<?php

namespace App\Libs\IM\Drivers;

use App\Libs\IM\Api\AbstractApi;
use App\Libs\IM\Api\Custom\User;
use Illuminate\Http\Client\Response;

class Custom extends AbstractDriver
{
    public function getAppCode(): string
    {
        return $this->config['app_code'] ?? ($this->config['app_id'] ?? ($this->config['app_key'] ?? '')) ?? '';
    }

    /**
     * @param string $method
     * @param string $path
     * @param array $options
     * @return Response
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function httpRequest(string $method, string $path, array $options = []): Response
    {
        $options['headers']['X-App-Code'] = $this->getAppCode();
        $options['headers']['X-App-Key'] = $this->config['app_key'];
        return parent::httpRequest($method, $path, $options);
    }

    public function api(string $name): AbstractApi
    {
        return match ($name) {
            'user' => new User($this),
            default => throw new \Exception("不支持的 API 名称：{$name}。")
        };
    }
}
