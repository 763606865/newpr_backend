<?php

namespace Tests\Unit\SApi;

use App\Services\SApiClientService;
use Tests\TestCase;

class SApiClientServiceTest extends TestCase
{
    public function test_it_generates_prefixed_app_key_and_hex_secret(): void
    {
        $service = SApiClientService::make();

        $appKey = $service->generateAppKey();
        $appSecret = $service->generateAppSecret();

        $this->assertStringStartsWith('sapi_', $appKey);
        $this->assertSame(37, strlen($appKey));
        $this->assertSame(64, strlen($appSecret));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $appSecret);
    }
}
