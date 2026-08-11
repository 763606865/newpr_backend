<?php

namespace App\Libs\ThirdParty\CJWL\Api;

use App\Libs\Exceptions\BadRequestException;
use App\Libs\ThirdParty\ApiRequest as BaseApiRequest;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Contracts\Container\BindingResolutionException;

class ApiRequest extends BaseApiRequest
{
    private string $prefix = '/api';

    /**
     * @throws BindingResolutionException
     */
    public function request(string $method, string $endpoint, array $data = []): PromiseInterface
    {
        $params = $this->normalizeParams($method, $data);
        $params = $this->normalizeJsonBody($params);
        $endpoint = $this->prefix.'/'.trim($this->removeApiPrefix($endpoint), '/');

        $normalizedEndpoint = $this->normalizeEndpoint($endpoint);

        // 避免同一实例多次调用时 header 污染
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        return parent::request($method, $normalizedEndpoint, $params);
    }

    /**
     * @throws BadRequestException
     * @throws \JsonException
     */
    public function response(PromiseInterface $promise): array
    {
        $body = $this->parseResponseBody($promise);

        $this->assertBusinessSuccess($body);

        return $body;
    }

    private function normalizeEndpoint(string $endpoint): string
    {
        $path = parse_url($endpoint, PHP_URL_PATH) ?? $endpoint;

        return trim($path, '/');
    }

    private function removeApiPrefix(string $endpoint): string
    {
        $path = parse_url($endpoint, PHP_URL_PATH) ?? $endpoint;
        $normalizedPrefix = trim($this->prefix, '/');
        $normalizedPath = trim($path, '/');

        if ($normalizedPath === $normalizedPrefix) {
            return '';
        }

        if (str_starts_with($normalizedPath, $normalizedPrefix.'/')) {
            return substr($normalizedPath, strlen($normalizedPrefix) + 1);
        }

        return $endpoint;
    }

    private function assertBusinessSuccess(array $body): void
    {
        if (isset($body['code']) && (int) $body['code'] !== 200) {
            throw new BadRequestException((string) ($body['msg'] ?? 'Unknown error'));
        }
    }
}
