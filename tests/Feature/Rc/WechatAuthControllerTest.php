<?php

namespace Tests\Feature\Rc;

use App\Models\User;
use App\Models\UserThirdPartyAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class WechatAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        config()->set('services.wechat', [
            'mini_app_id' => 'mini-app-id',
            'mini_app_secret' => 'mini-secret',
            'app_id' => 'app-id',
            'app_secret' => 'app-secret',
            'pending_token_ttl' => 600,
        ]);
        Cache::flush();
    }

    public function test_mini_login_creates_user_binding_and_access_token(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('Wechat Mini Test', 'rc_users');

        Http::fake([
            'api.weixin.qq.com/sns/jscode2session*' => Http::response([
                'openid' => 'mini-open-id',
                'unionid' => 'union-id',
                'session_key' => 'session-key',
            ]),
            'api.weixin.qq.com/cgi-bin/token*' => Http::response([
                'access_token' => 'wechat-access-token',
                'expires_in' => 7200,
            ]),
            'api.weixin.qq.com/wxa/business/getuserphonenumber*' => Http::response([
                'errcode' => 0,
                'phone_info' => ['purePhoneNumber' => '13800138000'],
            ]),
        ]);

        $response = $this->postJson('/rc/auth/wechat-mini-login', [
            'code' => 'login-code',
            'phone_code' => 'phone-code',
            'nickname' => '微信用户',
            'avatar' => 'uploads/rc/avatar/avatar.jpg',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.phone', '13800138000')
            ->assertJsonPath('data.user.nickname', '微信用户');

        $user = User::query()->where('phone', '13800138000')->firstOrFail();

        $this->assertDatabaseHas('user_third_party_accounts', [
            'user_id' => $user->id,
            'provider' => 'wechat_mini',
            'open_id' => 'mini-open-id',
            'union_id' => 'union-id',
        ]);
    }

    public function test_app_login_without_bound_phone_returns_pending_token(): void
    {
        Http::fake([
            'api.weixin.qq.com/sns/oauth2/access_token*' => Http::response([
                'access_token' => 'oauth-token',
                'openid' => 'app-open-id',
                'unionid' => 'app-union-id',
                'scope' => 'snsapi_userinfo',
            ]),
        ]);

        $response = $this->postJson('/rc/auth/wechat-app-login', [
            'code' => 'app-login-code',
            'nickname' => 'App 微信用户',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'need_phone')
            ->assertJsonPath('data.token_type', null)
            ->assertJsonPath('data.access_token', null)
            ->assertJsonPath('data.user', null);

        $pendingToken = $response->json('data.pending_token');
        $this->assertIsString($pendingToken);
        $this->assertSame(64, strlen($pendingToken));
    }

    public function test_app_phone_binding_creates_account_and_returns_access_token(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('Wechat App Test', 'rc_users');
        config()->set('app.debug', true);
        config()->set('app.skip_accounts', ['13800138001']);

        Http::fake([
            'api.weixin.qq.com/sns/oauth2/access_token*' => Http::response([
                'access_token' => 'oauth-token',
                'openid' => 'app-open-id',
                'unionid' => 'app-union-id',
            ]),
        ]);

        $loginResponse = $this->postJson('/rc/auth/wechat-app-login', [
            'code' => 'app-login-code',
            'nickname' => 'App 微信用户',
        ]);

        $response = $this->postJson('/rc/auth/wechat-bind-phone', [
            'pending_token' => $loginResponse->json('data.pending_token'),
            'phone' => '13800138001',
            'code' => '123456',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.phone', '13800138001');

        $this->assertDatabaseHas('user_third_party_accounts', [
            'provider' => 'wechat_app',
            'open_id' => 'app-open-id',
            'union_id' => 'app-union-id',
        ]);
    }

    public function test_app_login_with_existing_binding_returns_token_directly(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('Wechat Existing App Test', 'rc_users');
        $user = User::factory()->create(['phone' => '13800138002']);
        UserThirdPartyAccount::query()->create([
            'user_id' => $user->id,
            'provider' => 'wechat_app',
            'app_code' => 'app-id',
            'open_id' => 'existing-open-id',
            'union_id' => 'existing-union-id',
            'bound_at' => now(),
        ]);

        Http::fake([
            'api.weixin.qq.com/sns/oauth2/access_token*' => Http::response([
                'access_token' => 'oauth-token',
                'openid' => 'existing-open-id',
                'unionid' => 'existing-union-id',
            ]),
        ]);

        $response = $this->postJson('/rc/auth/wechat-app-login', ['code' => 'app-login-code']);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id);
    }
}
