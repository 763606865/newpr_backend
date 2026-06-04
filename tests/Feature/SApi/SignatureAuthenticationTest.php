<?php

namespace Tests\Feature\SApi;

use App\Models\SApi\Client;
use App\Services\SApiSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithSApiSignatures;
use Tests\TestCase;

class SignatureAuthenticationTest extends TestCase
{
    use InteractsWithSApiSignatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_ping_requires_signature_headers(): void
    {
        $response = $this->getJson('/sapi/ping');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('code', 401)
            ->assertJsonPath('message', '缺少 SApi 鉴权请求头。');
    }

    public function test_ping_returns_pong_with_valid_signature(): void
    {
        $client = Client::factory()->create([
            'name' => 'Test Client',
        ]);

        $response = $this->withHeaders(
            $this->sapiSignatureHeaders($client, 'GET', '/sapi/ping'),
        )->get('/sapi/ping');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.message', 'pong')
            ->assertJsonPath('data.client.id', $client->id)
            ->assertJsonPath('data.client.app_key', $client->app_key);
    }

    public function test_ping_rejects_invalid_signature(): void
    {
        $client = Client::factory()->create();
        $headers = $this->sapiSignatureHeaders($client, 'GET', '/sapi/ping');
        $headers[SApiSignatureService::make()->headerName('sign')] = 'invalid-signature';

        $response = $this->withHeaders($headers)->get('/sapi/ping');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'SApi 签名校验失败。');
    }

    public function test_unauthorized_request_writes_debug_log(): void
    {
        Log::partialMock()
            ->shouldReceive('debug')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'SApi 鉴权失败'
                    && ($context['reason'] ?? '') === '缺少 SApi 鉴权请求头。'
                    && ($context['path'] ?? '') === '/sapi/ping';
            });

        $this->get('/sapi/ping');
    }

    public function test_ping_rejects_expired_timestamp(): void
    {
        $client = Client::factory()->create();
        $expiredTimestamp = (string) (time() - 600);

        $response = $this->withHeaders(
            $this->sapiSignatureHeaders(
                $client,
                'GET',
                '/sapi/ping',
                [],
                '',
                $expiredTimestamp,
            ),
        )->get('/sapi/ping');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'SApi 请求已过期。');
    }

    public function test_ping_rejects_replayed_nonce(): void
    {
        $client = Client::factory()->create();
        $nonce = 'fixed-nonce-'.Str::random(8);
        $headers = $this->sapiSignatureHeaders(
            $client,
            'GET',
            '/sapi/ping',
            [],
            '',
            null,
            $nonce,
        );

        $this->withHeaders($headers)->get('/sapi/ping')->assertOk();

        $response = $this->withHeaders($headers)->get('/sapi/ping');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'SApi 请求重复。');
    }

    public function test_ping_rejects_disabled_client(): void
    {
        $client = Client::factory()->disabled()->create();

        $response = $this->withHeaders(
            $this->sapiSignatureHeaders($client, 'GET', '/sapi/ping'),
        )->get('/sapi/ping');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'SApi 应用已停用。');
    }

    public function test_ping_rejects_ip_not_in_whitelist(): void
    {
        $client = Client::factory()->withAllowedIps(['203.0.113.10'])->create();

        $response = $this->withHeaders(
            $this->sapiSignatureHeaders($client, 'GET', '/sapi/ping'),
        )->get('/sapi/ping');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'SApi 来源 IP 不在白名单内。');
    }

    public function test_ping_rejects_unknown_app_key(): void
    {
        $client = Client::factory()->create();
        $headers = $this->sapiSignatureHeaders($client, 'GET', '/sapi/ping');
        $headers[SApiSignatureService::make()->headerName('app_key')] = 'sapi_unknown_key';

        $response = $this->withHeaders($headers)->get('/sapi/ping');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'SApi 应用不存在。');
    }
}
