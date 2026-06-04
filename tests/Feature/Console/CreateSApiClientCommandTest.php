<?php

namespace Tests\Feature\Console;

use App\Enums\SApiClientStatus;
use App\Models\SApi\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSApiClientCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_client_and_outputs_credentials(): void
    {
        $this->artisan('sapi:client:create', [
            'name' => '测试对接方',
            '--remark' => '单元测试',
            '--ip' => ['203.0.113.1', '203.0.113.2'],
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('SApi 客户端已创建。')
            ->expectsOutputToContain('请立即保存 App Secret');

        $client = Client::query()->where('name', '测试对接方')->first();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertStringStartsWith('sapi_', $client->app_key);
        $this->assertSame(64, strlen($client->app_secret));
        $this->assertSame(SApiClientStatus::Enabled, $client->status);
        $this->assertSame(['203.0.113.1', '203.0.113.2'], $client->allowed_ips);
        $this->assertSame('单元测试', $client->remark);
    }

    public function test_command_can_create_disabled_client_without_ip_whitelist(): void
    {
        $this->artisan('sapi:client:create', [
            'name' => '停用客户端',
            '--disabled' => true,
        ])->assertExitCode(0);

        $client = Client::query()->where('name', '停用客户端')->first();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame(SApiClientStatus::Disabled, $client->status);
        $this->assertNull($client->allowed_ips);
    }

    public function test_command_rejects_empty_name(): void
    {
        $this->artisan('sapi:client:create', [
            'name' => '   ',
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('客户端名称不能为空。');

        $this->assertSame(0, Client::query()->count());
    }

    public function test_command_rejects_invalid_ip(): void
    {
        $this->artisan('sapi:client:create', [
            'name' => '无效 IP',
            '--ip' => ['not-an-ip'],
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('IP 格式无效');

        $this->assertSame(0, Client::query()->count());
    }
}
