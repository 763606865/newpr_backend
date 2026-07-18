<?php

namespace App\Libs\AI;

use App\Libs\AI\Contracts\AiDriver;

class AI
{
    public function __construct(protected AIManager $manager) {}

    public function driver(?string $driver = null): AiDriver
    {
        return $this->manager->driver($driver);
    }

    /**
     * @param  list<array{role: string, content: string|array<int, mixed>}>  $messages
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function chat(array $messages, array $options = [], ?string $driver = null): array
    {
        return $this->driver($driver)->chat($messages, $options);
    }

    /**
     * @return array<string, mixed>
     */
    public function parseResumeByFileUrl(string $fileUrl, ?string $driver = null): array
    {
        return $this->driver($driver)->parseResumeByFileUrl($fileUrl);
    }
}
