<?php

namespace App\Libs\Facades;

use App\Libs\AI\Contracts\AiDriver;
use Illuminate\Support\Facades\Facade;

/**
 * @method static AiDriver driver(?string $driver = null)
 * @method static array<string, mixed> chat(array $messages, array $options = [], ?string $driver = null)
 * @method static array<string, mixed> parseResumeByFileUrl(string $fileUrl, ?string $driver = null)
 *
 * @see \App\Libs\AI\AI
 */
class AI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Libs\AI\AI::class;
    }
}
