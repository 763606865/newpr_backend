<?php

namespace App\Libs\AI\Drivers;

use App\Libs\AI\AIException;
use App\Libs\AI\Contracts\AiDriver;
use App\Libs\AI\LoggerMiddleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

abstract class AbstractHttpAiDriver implements AiDriver
{
    public function __construct(protected array $config) {}

    public function chat(array $messages, array $options = []): array
    {
        $this->validateMessages($messages);

        $payload = array_merge([
            'model' => $options['model'] ?? $this->model(),
            'messages' => $messages,
        ], $this->withoutReservedOptions($options));

        $response = $this->post($this->chatPath(), $payload);
        $raw = $this->decodeResponse($response);

        return $this->normalizeChatResponse($raw, (string) ($payload['model'] ?? ''));
    }

    public function parseResumeByFileUrl(string $fileUrl): array
    {
        throw new AIException("AI {$this->provider()} 暂不支持简历文件解析。");
    }

    abstract protected function provider(): string;

    protected function model(): ?string
    {
        $model = (string) ($this->config['model'] ?? '');

        return $model !== '' ? $model : null;
    }

    protected function baseUrl(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? ''), '/');
    }

    protected function chatPath(): string
    {
        return '/'.ltrim((string) ($this->config['chat_path'] ?? '/chat/completions'), '/');
    }

    protected function resumeParsePath(): string
    {
        return '/'.ltrim((string) ($this->config['resume_parse_path'] ?? '/api/v1/parse'), '/');
    }

    protected function timeout(): int
    {
        $timeout = $this->config['timeout'] ?? null;

        if (blank($timeout)) {
            return 30;
        }

        return max(1, (int) $timeout);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function post(string $path, array $payload): Response
    {
        if ($this->baseUrl() === '') {
            throw new AIException("AI {$this->provider()} base_url 未配置。");
        }

        try {
            return Http::withHeaders($this->headers())
                ->withMiddleware(new LoggerMiddleware(app('log')))
                ->timeout($this->timeout())
                ->post($this->baseUrl().$path, $payload);
        } catch (ConnectionException $exception) {
            throw new AIException("AI {$this->provider()} 请求失败：".$exception->getMessage(), $exception);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $apiKey = (string) ($this->config['api_key'] ?? '');

        if ($apiKey !== '') {
            $headers['Authorization'] = 'Bearer '.$apiKey;
        }

        return $headers;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeResponse(Response $response): array
    {
        if (! $response->successful()) {
            throw new AIException("AI {$this->provider()} 请求失败，状态码：".$response->status());
        }

        try {
            /** @var array<string, mixed> $body */
            $body = $response->json();
        } catch (Throwable $exception) {
            throw new AIException("AI {$this->provider()} 返回了无效 JSON。", $exception);
        }

        if (! is_array($body)) {
            throw new AIException("AI {$this->provider()} 返回了无效响应。");
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{provider: string, model: string|null, content: string|null, raw: array<string, mixed>, usage: array<string, mixed>|null}
     */
    protected function normalizeChatResponse(array $raw, string $fallbackModel): array
    {
        $choice = $raw['choices'][0] ?? [];
        $message = is_array($choice) ? ($choice['message'] ?? []) : [];
        $content = is_array($message) ? ($message['content'] ?? null) : null;
        $usage = $raw['usage'] ?? null;

        return [
            'provider' => $this->provider(),
            'model' => (string) ($raw['model'] ?? $fallbackModel ?: null),
            'content' => is_string($content) ? $content : null,
            'raw' => $raw,
            'usage' => is_array($usage) ? $usage : null,
        ];
    }

    /**
     * @param  list<array{role: string, content: string|array<int, mixed>}>  $messages
     */
    protected function validateMessages(array $messages): void
    {
        if ($messages === []) {
            throw new AIException('AI messages 不能为空。');
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function withoutReservedOptions(array $options): array
    {
        unset($options['model']);

        return $options;
    }
}
