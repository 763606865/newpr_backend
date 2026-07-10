<?php

namespace App\Libs\IM;

use App\Libs\IM\Drivers\AbstractDriver;
use App\Libs\IM\Drivers\Custom;
use App\Libs\IM\Drivers\Easemob;
use App\Libs\IM\Drivers\RongCloud;
use App\Libs\IM\Drivers\Tencent;
use Illuminate\Contracts\Foundation\Application;

class ImManager
{
    protected array $drivers = [];

    public function __construct(protected Application $app) {}

    public function driver(?string $driver = null): AbstractDriver
    {
        $driver = $driver ?: (string) config('im.default', 'custom');

        if (! isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    /**
     * @param string $driver
     * @return AbstractDriver
     * @throws IMException
     */
    protected function createDriver(string $driver): AbstractDriver
    {
        $config = config("im.{$driver}");

        if (! is_array($config)) {
            throw new IMException("未找到 OCR 驱动配置：{$driver}。");
        }

        return match ($driver) {
            'custom' => new Custom($config),
            'easemob' => new Easemob($config),
            'rongcloud' => new RongCloud($config),
            'tencent' => new Tencent($config),
            default => throw new IMException("不支持的 IM 驱动：{$driver}。"),
        };
    }
}
