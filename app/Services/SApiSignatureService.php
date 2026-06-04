<?php

namespace App\Services;

use Illuminate\Http\Request;

class SApiSignatureService extends Service
{
    /**
     * 根据请求内容生成签名。
     */
    public function sign(Request $request, string $appKey, string $appSecret): string
    {
        $canonical = $this->buildCanonicalString(
            method: $request->method(),
            path: $this->normalizePath($request->getPathInfo()),
            query: $request->query(),
            body: (string) $request->getContent(),
            timestamp: (string) $request->header($this->headerName('timestamp'), ''),
            nonce: (string) $request->header($this->headerName('nonce'), ''),
            appKey: $appKey,
        );

        return hash_hmac('sha256', $canonical, $appSecret);
    }

    /**
     * 校验客户端提交的签名是否有效。
     */
    public function verify(Request $request, string $appKey, string $appSecret, string $providedSign): bool
    {
        if ($providedSign === '') {
            return false;
        }

        $expectedSign = $this->sign($request, $appKey, $appSecret);

        return hash_equals($expectedSign, $providedSign);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function buildCanonicalString(
        string $method,
        string $path,
        array $query,
        string $body,
        string $timestamp,
        string $nonce,
        string $appKey,
    ): string {
        ksort($query);

        return implode("\n", [
            strtoupper($method),
            $this->normalizePath($path),
            http_build_query($query, '', '&', PHP_QUERY_RFC3986),
            hash('sha256', $body),
            $timestamp,
            $nonce,
            $appKey,
        ]);
    }

    public function headerName(string $key): string
    {
        return (string) config('sapi.headers.'.$key);
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/'.ltrim($path, '/');

        return $normalized === '/' ? $normalized : rtrim($normalized, '/');
    }
}
