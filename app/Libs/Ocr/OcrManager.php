<?php

namespace App\Libs\Ocr;

use App\Libs\Ocr\Contracts\OcrDriver;
use App\Libs\Ocr\Drivers\AliyunOcr;
use Illuminate\Contracts\Foundation\Application;

class OcrManager
{
    /** @var array<string, OcrDriver> */
    protected array $drivers = [];

    public function __construct(protected Application $app) {}

    public function driver(?string $driver = null): OcrDriver
    {
        $driver = $driver ?: (string) config('ocr.default', 'aliyun');

        if (! isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    protected function createDriver(string $driver): OcrDriver
    {
        $config = config("ocr.drivers.{$driver}");

        if (! is_array($config)) {
            throw new OcrException("未找到 OCR 驱动配置：{$driver}。");
        }

        return match ($driver) {
            'aliyun' => new AliyunOcr($config),
            default => throw new OcrException("不支持的 OCR 驱动：{$driver}。"),
        };
    }
}
