<?php

namespace App\Libs\ThirdParty;

use App\Libs\Exceptions\BadRequestException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;

class ApiRequest
{
    protected array $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    public function __construct(protected Application $app) {}

    /**
     * @throws BindingResolutionException
     */
    public function request(string $method, string $endpoint, array $data = []): PromiseInterface
    {
        $params = $this->normalizeParams($method, $data);

        return $this->client()->requestAsync($method, $endpoint, $params);
    }

    public function response(PromiseInterface $promise): array
    {
        $body = $this->parseResponseBody($promise);
        if (isset($body['code']) && (int) $body['code'] !== 1) {
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
            'headers' => $this->getHeaders(),
            'verify' => false, // 禁用SSL验证
            'version' => 1.1, // 使用HTTP/1.1
            'timeout' => 3.0, // 设置超时
        ]);
    }

    protected function normalizeParams(string $method, array $data): array
    {
        $params = $data;

        $hasTransportOptions = isset($params['query'])
            || array_key_exists('json', $params)
            || isset($params['form_params'])
            || isset($params['multipart'])
            || isset($params['body']);

        if (! $hasTransportOptions) {
            if (strtoupper($method) === 'GET') {
                $params['query'] = $data;
            } else {
                $params['json'] = $data;
            }
        }

        return $params;
    }

    protected function getHeaders(): array
    {
        return array_merge($this->headers, $this->app->config['headers'] ?? []);
    }

    protected function setHeaders(array $headers): void
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    protected function resolveRequestBody(array $params): string
    {
        if (isset($params['json'])) {
            return json_encode($params['json'], JSON_THROW_ON_ERROR);
        }

        if (isset($params['body'])) {
            return (string) $params['body'];
        }

        return '';
    }

    protected function normalizeJsonBody(array $params): array
    {
        if (! array_key_exists('json', $params)) {
            return $params;
        }

        if (is_array($params['json']) && $params['json'] === []) {
            $params['json'] = (object) [];
        }

        return $params;
    }

    protected function configValue(array $keys, string $hint): string
    {
        foreach ($keys as $key) {
            $value = (string)($this->app->config[$key] ?? '');
            if ($value !== '') {
                return $value;
            }
        }

        throw new InvalidArgumentException('缺少配置: ' . $hint);
    }

    protected function parseResponseBody(PromiseInterface $promise): array
    {
        $response = $promise->wait();

        if ($response->getStatusCode() !== 200) {
            throw new BadRequestException('API请求失败，状态码: ' . $response->getStatusCode());
        }

        $body = $response->getBody()->getContents();
        if ($body === '') {
            throw new BadRequestException('Invalid JSON Response.');
        }

        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }
}
