<?php

namespace App\Libs\ThirdParty\JucaiDT\Api;

use App\Libs\Exceptions\BadRequestException;
use App\Libs\ThirdParty\ApiRequest as BaseApiRequest;
use App\Libs\ThirdParty\Concern\HasApiAccessTokenHeaders;
use App\Libs\ThirdParty\Concern\HasApiSignedHeaders;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Contracts\Container\BindingResolutionException;

class ApiRequest extends BaseApiRequest
{
    use HasApiAccessTokenHeaders, HasApiSignedHeaders;

    private const int TOKEN_EXPIRED_ERROR_CODE = 10004;

    private const int TOKEN_INVALID_ERROR_CODE = 10005;

    /**
     * @var array{method: string, endpoint: string, data: array}|null
     */
    private ?array $lastRequestContext = null;

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

        if (! $this->isLoginEndpoint($normalizedEndpoint)) {
            $this->lastRequestContext = [
                'method' => $method,
                'endpoint' => $endpoint,
                'data' => $data,
            ];
        }

        // 避免同一实例多次调用时 header 污染
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $signedHeaders = $this->getSignedHeaders($params);

        $accessTokenHeaders = $this->isLoginEndpoint($normalizedEndpoint) ? [] : $this->getAccessTokenHeaders();

        $headers = array_merge($signedHeaders, $accessTokenHeaders);

        $this->setHeaders($headers);

        return parent::request($method, $endpoint, $params);
    }

    /**
     * @throws BadRequestException
     * @throws \JsonException
     */
    public function response(PromiseInterface $promise): array
    {
        $body = $this->parseResponseBody($promise);

        if ($this->isTokenExpiredResponse($body)) {
            if (method_exists(self::class, 'retryRequestWithFreshToken')) {
                $body = $this->retryRequestWithFreshToken();
            } else {
                throw new BadRequestException('Token Expired!');
            }
        }

        $this->assertBusinessSuccess($body);

        return $body;
    }

    private function isLoginEndpoint(string $endpoint): bool
    {
        $path = trim($endpoint, '/');

        return $path === 'login' || str_ends_with($path, '/login');
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
        if (isset($body['code']) && (int) $body['code'] !== 1) {
            throw new BadRequestException((string) ($body['msg'] ?? 'Unknown error'));
        }

        if (isset($body['errorcode']) && (int) $body['errorcode'] !== 0) {
            if ((int) $body['errorcode'] === self::TOKEN_EXPIRED_ERROR_CODE) {
                return;
            }

            throw new BadRequestException((string) ($body['errormsg'] ?? $body['msg'] ?? 'Unknown error'));
        }
    }

    private function isTokenExpiredResponse(array $body): bool
    {
        return in_array((int) ($body['errorcode'] ?? 0), [
            self::TOKEN_EXPIRED_ERROR_CODE,
            self::TOKEN_INVALID_ERROR_CODE,
        ]);
    }
}
