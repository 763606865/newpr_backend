<?php

namespace App\Libs\AI\Contracts;

use App\Libs\AI\AIException;

interface AiDriver
{
    /**
     * @param  list<array{role: string, content: string|array<int, mixed>}>  $messages
     * @param  array<string, mixed>  $options
     * @return array{
     *     provider: string,
     *     model: string|null,
     *     content: string|null,
     *     raw: array<string, mixed>,
     *     usage: array<string, mixed>|null
     * }
     *
     * @throws AIException
     */
    public function chat(array $messages, array $options = []): array;

    /**
     * @return array{
     *     provider: string,
     *     file_url: string,
     *     data: array<string, mixed>,
     *     raw: array<string, mixed>
     * }
     *
     * @throws AIException
     */
    public function parseResumeByFileUrl(string $fileUrl): array;
}
