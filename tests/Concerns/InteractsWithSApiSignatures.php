<?php

namespace Tests\Concerns;

use App\Models\SApi\Client;
use App\Services\SApiSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait InteractsWithSApiSignatures
{
    /**
     * 生成 SApi 加签请求头。签名时的 path、query、body 须与实际请求完全一致。
     * 注意：GET 请求请使用 get()，勿用 getJson()（后者会附带 "[]" 请求体导致验签失败）。
     *
     * @param  array<string, mixed>  $query
     * @return array<string, string>
     */
    protected function sapiSignatureHeaders(
        Client $client,
        string $method,
        string $uri,
        array $query = [],
        string $body = '',
        ?string $timestamp = null,
        ?string $nonce = null,
    ): array {
        $signatureService = SApiSignatureService::make();
        $timestamp ??= (string) time();
        $nonce ??= Str::random(16);

        $request = Request::create($uri, $method, $query, [], [], [], $body);
        $request->headers->set($signatureService->headerName('timestamp'), $timestamp);
        $request->headers->set($signatureService->headerName('nonce'), $nonce);
        $request->headers->set($signatureService->headerName('app_key'), $client->app_key);

        $sign = $signatureService->sign($request, $client->app_key, $client->app_secret);

        return [
            $signatureService->headerName('app_key') => $client->app_key,
            $signatureService->headerName('timestamp') => $timestamp,
            $signatureService->headerName('nonce') => $nonce,
            $signatureService->headerName('sign') => $sign,
            'Accept' => 'application/json',
        ];
    }
}
