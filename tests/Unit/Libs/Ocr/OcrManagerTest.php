<?php

namespace Tests\Unit\Libs\Ocr;

use App\Libs\Ocr\Contracts\OcrDriver;
use App\Libs\Ocr\Drivers\AliyunOcr;
use App\Libs\Ocr\OcrException;
use App\Libs\Ocr\OcrManager;
use Illuminate\Foundation\Application;
use Tests\TestCase;

class OcrManagerTest extends TestCase
{
    public function test_driver_resolves_aliyun_implementation(): void
    {
        config([
            'ocr.default' => 'aliyun',
            'ocr.drivers.aliyun' => [
                'access_key_id' => 'test-key-id',
                'access_key_secret' => 'test-key-secret',
            ],
        ]);

        $driver = $this->manager()->driver();

        $this->assertInstanceOf(AliyunOcr::class, $driver);
        $this->assertInstanceOf(OcrDriver::class, $driver);
    }

    public function test_driver_throws_for_unknown_driver(): void
    {
        config([
            'ocr.default' => 'unknown',
            'ocr.drivers.unknown' => [],
        ]);

        $this->expectException(OcrException::class);
        $this->expectExceptionMessage('不支持的 OCR 驱动：unknown');

        $this->manager()->driver('unknown');
    }

    public function test_driver_throws_when_config_is_missing(): void
    {
        config([
            'ocr.default' => 'aliyun',
            'ocr.drivers.aliyun' => null,
        ]);

        $this->expectException(OcrException::class);
        $this->expectExceptionMessage('未找到 OCR 驱动配置：aliyun');

        $this->manager()->driver('aliyun');
    }

    private function manager(): OcrManager
    {
        return new OcrManager($this->app->make(Application::class));
    }
}
