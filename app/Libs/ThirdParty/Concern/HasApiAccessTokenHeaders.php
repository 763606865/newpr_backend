<?php

namespace App\Libs\ThirdParty\Concern;

use App\Libs\Exceptions\BadRequestException;
use Exception;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use JsonException;

trait HasApiAccessTokenHeaders
{
    private const int TOKEN_REFRESH_MAX_RETRIES = 3;

    /**
     * Return Authorization header when available.
     *
     * @throws Exception
     */
    protected function getAccessTokenHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->getAccessToken(),
        ];
    }

    /**
     * Get access token from cache or refresh if missing.
     *
     * @throws Exception
     */
    protected function getAccessToken(bool $forceRefresh = false): ?string
    {
        $cacheDriver = $this->getTokenCacheDriver();
        $key = $this->accessTokenCacheKey();

        if (! $forceRefresh) {
            try {
                $cached = $cacheDriver->get($key);
                if (! empty($cached) && is_array($cached) && isset($cached['access_token'])) {
                    return $cached['access_token'];
                }
            } catch (\Throwable $e) {
                // ignore cache errors and continue to fetch
            }
        }

        $data = $this->refreshAccessToken();

        return $this->extractAccessToken($data);
    }

    /**
     * Refresh token and store in cache. Returns array payload from login response.
     *
     * @throws BadRequestException
     * @throws BindingResolutionException
     * @throws JsonException
     */
    protected function refreshAccessToken(): array
    {
        $data = $this->requestLoginToken();

        $accessToken = $this->extractAccessToken($data);
        $expires = $this->extractExpiresIn($data);

        if (empty($accessToken) || $expires <= 0) {
            throw new \RuntimeException('Failed to obtain access token');
        }

        $ttl = max(1, $expires - 60);

        try {
            $this->getTokenCacheDriver()->put($this->accessTokenCacheKey(), ['access_token' => $accessToken, 'expires_in' => $expires], $ttl);
        } catch (\Throwable $e) {
            // ignore cache write errors
        }

        return $data;
    }

    /**
     * Calls the login endpoint to obtain token payload. Should return parsed response array.
     *
     * @throws BadRequestException
     * @throws JsonException
     * @throws BindingResolutionException
     */
    protected function requestLoginToken(): array
    {
        // login uses signature but NO Authorization header
        $params = ['json' => new \stdClass];

        $promise = $this->request('POST', '/auth/login', $params + ['_no_auth' => true]);
        $resp = $this->response($promise);

        return $resp['data'] ?? [];
    }

    protected function accessTokenCacheKey(): string
    {
        $host = $this->configValue(['host'], 'host');
        $appKey = $this->configValue(['app_key'], 'app_key');

        return 'thirdparty:'.self::class.':token:'.md5($host.'|'.$appKey);
    }

    protected function getTokenCacheDriver(): CacheRepository
    {
        try {
            $store = $this->configValue(['cache_store'], 'cache_store');
        } catch (\InvalidArgumentException $exception) {
            return cache()->store();
        }

        return cache()->store($store);
    }

    protected function extractAccessToken(array $data): ?string
    {
        return $data['access_token'] ?? null;
    }

    protected function extractExpiresIn(array $data): int
    {
        return isset($data['expires_in']) ? (int) $data['expires_in'] : 0;
    }

    private function retryRequestWithFreshToken(): array
    {
        if ($this->lastRequestContext === null) {
            throw new BadRequestException('数据中台服务异常');
        }

        $this->getAccessToken(true);

        $promise = $this->request(
            $this->lastRequestContext['method'],
            $this->lastRequestContext['endpoint'],
            $this->lastRequestContext['data'],
        );
        $body = $this->parseResponseBody($promise);

        if ($this->isTokenExpiredResponse($body)) {
            throw new BadRequestException('数据中台服务异常');
        }

        $this->assertBusinessSuccess($body);

        return $body;
    }
}
