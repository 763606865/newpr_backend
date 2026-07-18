<?php

namespace App\Libs\AI\Drivers;

use App\Libs\AI\AIException;

class CustomAi extends AbstractHttpAiDriver
{
    public function parseResumeByFileUrl(string $fileUrl): array
    {
        if (blank($fileUrl)) {
            throw new AIException('简历文件地址不能为空。');
        }

        $response = $this->post($this->resumeParsePath(), [
            'file_url' => $fileUrl,
        ]);
        $raw = $this->decodeResponse($response);
        $data = $raw['data'] ?? $raw;

        if (! is_array($data)) {
            throw new AIException('AI custom 简历解析返回了无效数据。');
        }

        return [
            'provider' => $this->provider(),
            'file_url' => $fileUrl,
            'data' => $data,
            'raw' => $raw,
        ];
    }

    protected function provider(): string
    {
        return 'custom';
    }

    protected function headers(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $apiKey = (string) ($this->config['api_key'] ?? '');

        if ($apiKey !== '') {
            $headers['X-API-Key'] = $apiKey;
        }

        return $headers;
    }
}
