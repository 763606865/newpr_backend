<?php

namespace App\Libs\ThirdParty;

use App\Libs\Exceptions\BadRequestException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Container\BindingResolutionException;

class ApiRequest
{
    public function __construct(protected Application $app) {}

    /**
     * @throws BindingResolutionException
     */
    public function request(string $method, string $endpoint, array $data = []): PromiseInterface
    {
        $params = $data;
        if (! isset($data['query'])) {
            if ($method === 'GET') {
                $params['query'] = $data;
            } else {
                $params['json'] = $data;
            }
        }

        return $this->client()->requestAsync($method, $endpoint, $params);
    }

    public function response(PromiseInterface $promise): array
    {
        /** @var Response $response */
        $response = $promise->wait();

        if ($response->getStatusCode() !== 200) {
            throw new BadRequestException('API请求失败，状态码: '.$response->getStatusCode());
        }

        $body = $response->getBody()->getContents(); // 先读取body，避免stream被消耗

        if (empty($body)) {
            throw new BadRequestException('Invalid JSON Response.');
        }

        $body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (isset($body['code']) && (int)$body['code'] !== 1) {
            throw new BadRequestException($body['msg'] ?? 'Unknown error');
        }

        return $body;
    }

    /**
     * @throws BindingResolutionException
     */
    public function client(): ClientInterface
    {
        $stack = HandlerStack::create();
        $logger = $this->app->app->log ?? $this->app->app->make('log');

        // 使用自定义LoggerMiddleware
        $stack->push(new LoggerMiddleware($logger));

        return new Client([
            'handler' => $stack,
            'base_uri' => $this->app->config['host'] ?? '',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'verify' => false, // 禁用SSL验证
            'version' => 1.1, // 使用HTTP/1.1
            'timeout' => 3.0, // 设置超时
        ]);
    }
}
