<?php

namespace App\Libs\ThirdParty\Concern;

trait HasApiSignedHeaders
{
    protected function makeSignature(string $appKey, string $timestamp, string $nonce, string $bodyMd5, string $appSecret): string
    {
        $signString = $appKey."\n".$timestamp."\n".$nonce."\n".$bodyMd5."\n".$appSecret;

        return hash('sha256', $signString);
    }

    protected function generateNonce(): string
    {
        return bin2hex(random_bytes(16)); // 32 chars
    }

    protected function getSignedHeaders(array $params = []): array
    {
        $appKey = $this->configValue(['app_key'], 'app_key');
        $appSecret = $this->configValue(['app_secret'], 'app_secret');

        $timestamp = (string) time();
        $nonce = $this->generateNonce();
        $bodyMd5 = md5($this->resolveRequestBody($params));
        $sign = $this->makeSignature($appKey, $timestamp, $nonce, $bodyMd5, $appSecret);

        return [
            'X-App-Key' => $appKey,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Sign' => $sign,
        ];
    }
}
