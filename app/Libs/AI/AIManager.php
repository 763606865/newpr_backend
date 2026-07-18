<?php

namespace App\Libs\AI;

use App\Libs\AI\Contracts\AiDriver;
use App\Libs\AI\Drivers\BailianAi;
use App\Libs\AI\Drivers\CustomAi;
use App\Libs\AI\Drivers\OpenAi;
use Illuminate\Contracts\Foundation\Application;

class AIManager
{
    /** @var array<string, AiDriver> */
    protected array $drivers = [];

    public function __construct(protected Application $app) {}

    public function driver(?string $driver = null): AiDriver
    {
        $driver = $driver ?: (string) config('ai.default', 'custom');

        if (! isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    protected function createDriver(string $driver): AiDriver
    {
        $config = config("ai.drivers.{$driver}");

        if (! is_array($config)) {
            throw new AIException("未找到 AI 驱动配置：{$driver}。");
        }

        return match ($driver) {
            'custom' => new CustomAi($config),
            'bailian' => new BailianAi($config),
            'openai' => new OpenAi($config),
            default => throw new AIException("不支持的 AI 驱动：{$driver}。"),
        };
    }
}
