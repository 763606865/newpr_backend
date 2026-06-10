<?php

namespace App\Libs\Ocr\Drivers;

use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeBusinessLicenseRequest;
use AlibabaCloud\SDK\Ocrapi\V20210707\Models\RecognizeGeneralRequest;
use AlibabaCloud\SDK\Ocrapi\V20210707\Ocrapi;
use AlibabaCloud\Tea\Exception\TeaError;
use App\Libs\Ocr\Contracts\OcrDriver;
use App\Libs\Ocr\Data\BusinessLicenseResult;
use App\Libs\Ocr\Data\RecognizeResult;
use App\Libs\Ocr\OcrException;
use Darabonba\OpenApi\Exceptions\AlibabaCloudException;
use Darabonba\OpenApi\Models\Config;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

class AliyunOcr implements OcrDriver
{
    public function __construct(
        protected array $config,
        protected ?Ocrapi $client = null,
        protected ?LoggerInterface $logger = null,
    ) {}

    public function recognizeGeneralByUrl(string $url): RecognizeResult
    {
        if (blank($url)) {
            throw new OcrException('图片 URL 不能为空。');
        }

        $request = new RecognizeGeneralRequest([
            'url' => $url,
        ]);

        $parsed = $this->invokeAction(
            'RecognizeGeneral',
            fn (Ocrapi $client) => $client->recognizeGeneral($request),
            [
                'source' => 'url',
                'url' => $this->sanitizeUrl($url),
            ],
        );

        $result = RecognizeResult::fromAliyunData($parsed['request_id'], $parsed['data']);

        $this->logGeneralSuccess($parsed['context'], $parsed['started_at'], $parsed['request_id'], $result);

        return $result;
    }

    public function recognizeGeneralByContent(string $content): RecognizeResult
    {
        if ($content === '') {
            throw new OcrException('图片内容不能为空。');
        }

        $request = new RecognizeGeneralRequest([
            'body' => Utils::streamFor($content),
        ]);

        $parsed = $this->invokeAction(
            'RecognizeGeneral',
            fn (Ocrapi $client) => $client->recognizeGeneral($request),
            [
                'source' => 'content',
                'content_bytes' => strlen($content),
            ],
        );

        $result = RecognizeResult::fromAliyunData($parsed['request_id'], $parsed['data']);

        $this->logGeneralSuccess($parsed['context'], $parsed['started_at'], $parsed['request_id'], $result);

        return $result;
    }

    public function recognizeBusinessLicenseByUrl(string $url): BusinessLicenseResult
    {
        if (blank($url)) {
            throw new OcrException('图片 URL 不能为空。');
        }

        $request = new RecognizeBusinessLicenseRequest([
            'url' => $url,
        ]);

        $parsed = $this->invokeAction(
            'RecognizeBusinessLicense',
            fn (Ocrapi $client) => $client->recognizeBusinessLicense($request),
            [
                'source' => 'url',
                'url' => $this->sanitizeUrl($url),
            ],
        );

        $result = BusinessLicenseResult::fromAliyunData($parsed['request_id'], $parsed['data']);

        $this->logBusinessLicenseSuccess($parsed['context'], $parsed['started_at'], $parsed['request_id'], $result);

        return $result;
    }

    public function recognizeBusinessLicenseByContent(string $content): BusinessLicenseResult
    {
        if ($content === '') {
            throw new OcrException('图片内容不能为空。');
        }

        $request = new RecognizeBusinessLicenseRequest([
            'body' => Utils::streamFor($content),
        ]);

        $parsed = $this->invokeAction(
            'RecognizeBusinessLicense',
            fn (Ocrapi $client) => $client->recognizeBusinessLicense($request),
            [
                'source' => 'content',
                'content_bytes' => strlen($content),
            ],
        );

        $result = BusinessLicenseResult::fromAliyunData($parsed['request_id'], $parsed['data']);

        $this->logBusinessLicenseSuccess($parsed['context'], $parsed['started_at'], $parsed['request_id'], $result);

        return $result;
    }

    /**
     * @param  callable(Ocrapi): object  $apiCall
     * @param  array<string, mixed>  $context
     * @return array{request_id: string, data: array<string, mixed>, context: array<string, mixed>, started_at: float}
     */
    protected function invokeAction(string $action, callable $apiCall, array $context): array
    {
        $context = array_merge([
            'driver' => 'aliyun',
            'action' => $action,
        ], $context);

        $startedAt = microtime(true);

        $this->logger()->info('OCR 请求开始', $this->logContext($context));

        try {
            $response = $apiCall($this->client());
        } catch (Throwable $exception) {
            $this->logFailure($context, $startedAt, $exception);

            throw new OcrException('阿里云 OCR 请求失败：'.$exception->getMessage(), $exception);
        }

        $body = $response->body ?? null;

        if ($body === null) {
            $this->logFailure($context, $startedAt, message: '阿里云 OCR 返回了空响应。');

            throw new OcrException('阿里云 OCR 返回了空响应。');
        }

        $requestId = (string) ($body->requestId ?? '');

        if (filled($body->code ?? null)) {
            $message = (string) (($body->message ?? '') ?: '阿里云 OCR 返回错误：'.($body->code ?? ''));

            $this->logFailure(
                $context,
                $startedAt,
                message: $message,
                requestId: $requestId,
                code: (string) $body->code,
            );

            throw new OcrException($message);
        }

        if (blank($body->data ?? null)) {
            $this->logFailure(
                $context,
                $startedAt,
                message: '阿里云 OCR 未返回识别结果。',
                requestId: $requestId,
            );

            throw new OcrException('阿里云 OCR 未返回识别结果。');
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode((string) $body->data, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            $this->logFailure(
                $context,
                $startedAt,
                exception: $exception,
                message: '阿里云 OCR 返回了无效的识别数据。',
                requestId: $requestId,
            );

            throw new OcrException('阿里云 OCR 返回了无效的识别数据。', $exception);
        }

        return [
            'request_id' => $requestId,
            'data' => $data,
            'context' => $context,
            'started_at' => $startedAt,
        ];
    }

    protected function client(): Ocrapi
    {
        if ($this->client instanceof Ocrapi) {
            return $this->client;
        }

        $accessKeyId = (string) ($this->config['access_key_id'] ?? '');
        $accessKeySecret = (string) ($this->config['access_key_secret'] ?? '');

        if ($accessKeyId === '' || $accessKeySecret === '') {
            throw new OcrException('未配置阿里云 OCR AccessKey，请在 .env 中设置 OCR_ACCESS_KEY_ID / OCR_ACCESS_KEY_SECRET（或复用 OSS 密钥）。');
        }

        $config = new Config([
            'accessKeyId' => $accessKeyId,
            'accessKeySecret' => $accessKeySecret,
            'endpoint' => (string) ($this->config['endpoint'] ?? 'ocr-api.cn-hangzhou.aliyuncs.com'),
            'regionId' => (string) ($this->config['region_id'] ?? 'cn-hangzhou'),
            'connectTimeout' => (int) ($this->config['connect_timeout'] ?? 5),
            'readTimeout' => (int) ($this->config['read_timeout'] ?? 10),
        ]);

        return new Ocrapi($config);
    }

    protected function logger(): LoggerInterface
    {
        return $this->logger ?? Log::getFacadeRoot();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function logContext(array $context): array
    {
        return array_filter($context, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function logGeneralSuccess(array $context, float $startedAt, string $requestId, RecognizeResult $result): void
    {
        $this->logger()->info('OCR 请求成功', $this->logContext(array_merge($context, [
            'request_id' => $requestId,
            'content_length' => mb_strlen($result->content),
            'word_count' => count($result->words),
            'duration_ms' => $this->durationMs($startedAt),
        ])));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function logBusinessLicenseSuccess(array $context, float $startedAt, string $requestId, BusinessLicenseResult $result): void
    {
        $this->logger()->info('OCR 请求成功', $this->logContext(array_merge($context, [
            'request_id' => $requestId,
            'company_name' => $result->companyName,
            'credit_code' => $result->creditCode,
            'duration_ms' => $this->durationMs($startedAt),
        ])));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function logFailure(
        array $context,
        float $startedAt,
        ?Throwable $exception = null,
        ?string $message = null,
        ?string $requestId = null,
        ?string $code = null,
    ): void {
        $requestId ??= $exception !== null ? $this->extractRequestId($exception) : null;

        $this->logger()->error('OCR 请求失败', $this->logContext(array_merge($context, [
            'request_id' => $requestId,
            'code' => $code ?? ($exception instanceof TeaError ? (string) $exception->code : null),
            'message' => $message ?? $exception?->getMessage(),
            'exception' => $exception !== null ? $exception::class : null,
            'duration_ms' => $this->durationMs($startedAt),
        ])));
    }

    protected function extractRequestId(Throwable $exception): ?string
    {
        if ($exception instanceof AlibabaCloudException) {
            $requestId = $exception->getRequestId();

            return $requestId !== '' ? $requestId : null;
        }

        if ($exception instanceof TeaError) {
            $errorInfo = $exception->getErrorInfo();

            if (is_array($errorInfo)) {
                $requestId = $errorInfo['requestId']
                    ?? $errorInfo['data']['RequestId']
                    ?? $errorInfo['data']['requestId']
                    ?? null;

                if (is_string($requestId) && $requestId !== '') {
                    return $requestId;
                }
            }
        }

        if (preg_match('/request id:\s*(\S+)/i', $exception->getMessage(), $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    protected function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    protected function sanitizeUrl(string $url): string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts)) {
            return $url;
        }

        $sanitized = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        if (isset($parts['path'])) {
            $sanitized .= $parts['path'];
        }

        return $sanitized;
    }
}
