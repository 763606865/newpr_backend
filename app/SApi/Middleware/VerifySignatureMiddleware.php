<?php

namespace App\SApi\Middleware;

use App\Enums\SApiClientStatus;
use App\Models\SApi\Client;
use App\Services\SApiSignatureService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifySignatureMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signatureService = SApiSignatureService::make();
        $appKey = trim((string) $request->header($signatureService->headerName('app_key'), ''));
        $timestamp = trim((string) $request->header($signatureService->headerName('timestamp'), ''));
        $nonce = trim((string) $request->header($signatureService->headerName('nonce'), ''));
        $sign = trim((string) $request->header($signatureService->headerName('sign'), ''));

        if ($appKey === '' || $timestamp === '' || $nonce === '' || $sign === '') {
            return $this->unauthorized($request, '缺少 SApi 鉴权请求头。', [
                'missing_headers' => array_values(array_filter([
                    $appKey === '' ? $signatureService->headerName('app_key') : null,
                    $timestamp === '' ? $signatureService->headerName('timestamp') : null,
                    $nonce === '' ? $signatureService->headerName('nonce') : null,
                    $sign === '' ? $signatureService->headerName('sign') : null,
                ])),
            ]);
        }

        if (! ctype_digit($timestamp)) {
            return $this->unauthorized($request, 'SApi 时间戳格式无效。', [
                'timestamp' => $timestamp,
            ]);
        }

        $timestampValue = (int) $timestamp;
        $tolerance = max(1, (int) config('sapi.timestamp_tolerance', 300));

        if (abs(time() - $timestampValue) > $tolerance) {
            return $this->unauthorized($request, 'SApi 请求已过期。', [
                'timestamp' => $timestampValue,
                'server_time' => time(),
                'tolerance' => $tolerance,
            ]);
        }

        $client = Client::query()
            ->where('app_key', $appKey)
            ->first();

        if (! $client instanceof Client) {
            return $this->unauthorized($request, 'SApi 应用不存在。');
        }

        if ($client->status !== SApiClientStatus::Enabled) {
            return $this->unauthorized($request, 'SApi 应用已停用。', [
                'client_id' => $client->id,
            ]);
        }

        if (! $client->isIpAllowed($request->ip())) {
            return $this->unauthorized($request, 'SApi 来源 IP 不在白名单内。', [
                'client_id' => $client->id,
                'allowed_ips' => $client->allowed_ips,
            ]);
        }

        $nonceCacheKey = config('sapi.nonce_cache_prefix').$appKey.':'.$nonce;
        $nonceCacheStore = config('sapi.nonce_cache_store');
        $nonceCache = $nonceCacheStore ? Cache::store($nonceCacheStore) : Cache::store();

        if (! $nonceCache->add($nonceCacheKey, 1, $tolerance * 2)) {
            return $this->unauthorized($request, 'SApi 请求重复。', [
                'client_id' => $client->id,
                'nonce' => $nonce,
            ]);
        }

        if (! $signatureService->verify($request, $appKey, $client->app_secret, $sign)) {
            return $this->unauthorized($request, 'SApi 签名校验失败。', [
                'client_id' => $client->id,
            ]);
        }

        $request->attributes->set('sapi_client', $client);

        return $next($request);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function unauthorized(Request $request, string $message, array $context = []): Response
    {
        Log::debug('SApi 鉴权失败', array_merge($this->requestLogContext($request), [
            'reason' => $message,
        ], $context));

        $now = microtime(true);

        return response()->json([
            'code' => Response::HTTP_UNAUTHORIZED,
            'message' => $message,
            'meta' => [
                'timestamp' => $now,
                'response_time' => $now - LARAVEL_START,
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestLogContext(Request $request): array
    {
        $signatureService = SApiSignatureService::make();

        return [
            'method' => $request->method(),
            'path' => $request->getPathInfo(),
            'query' => $request->query(),
            'ip' => $request->ip(),
            'app_key' => $request->header($signatureService->headerName('app_key')),
            'timestamp' => $request->header($signatureService->headerName('timestamp')),
            'nonce' => $request->header($signatureService->headerName('nonce')),
        ];
    }
}
