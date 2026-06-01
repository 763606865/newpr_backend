<?php

namespace Tests\Feature\Rc;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Token;
use App\Models\User;
use App\Rc\Controllers\AuthController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use Laravel\Passport\PersonalAccessTokenFactory;
use Laravel\Passport\PersonalAccessTokenResult;
use Mockery;
use Tests\TestCase;

class AuthRefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_refresh_token_creates_first_default_identity_when_missing(): void
    {
        $user = User::factory()->create();

        $oldToken = new class implements ScopeAuthorizable
        {
            public bool $revoked = false;

            public function revoke(): void
            {
                $this->revoked = true;
            }

            public mixed $responsible = null;

            public function can(string $scope): bool
            {
                return true;
            }

            public function cant(string $scope): bool
            {
                return false;
            }
        };

        $newToken = new Token;
        $newToken->forceFill([
            'id' => 'new-rc-token-id',
            'user_id' => $user->id,
            'client_id' => '00000000-0000-0000-0000-000000000001',
            'name' => 'rc',
            'scopes' => [],
            'revoked' => false,
            'expires_at' => now()->addHour(),
        ]);

        $tokenResult = new class($newToken) extends PersonalAccessTokenResult
        {
            public function __construct(private readonly Token $fakeToken)
            {
                parent::__construct([
                    'access_token' => 'new-rc-token',
                ]);
            }

            public function getToken(): ?Token
            {
                return $this->fakeToken;
            }
        };

        $factoryMock = Mockery::mock(PersonalAccessTokenFactory::class);
        $factoryMock
            ->shouldReceive('make')
            ->once()
            ->with($user->getAuthIdentifier(), 'rc', [], 'rc_users')
            ->andReturn($tokenResult);
        $this->app->instance(PersonalAccessTokenFactory::class, $factoryMock);

        $userMock = Mockery::mock($user)->makePartial();
        $userMock->shouldReceive('token')->once()->andReturn($oldToken);

        $request = Request::create('/rc/auth/refresh-token', 'POST', [
            'identity_type' => RcIdentityType::Headhunter->value,
        ]);
        $request->setUserResolver(static fn () => $userMock);

        $controller = new AuthController;
        $response = $controller->refreshToken($request);

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('new-rc-token', $payload['data']['access_token']);
        $this->assertSame(RcIdentityType::Headhunter->value, $payload['data']['user']['current_identity']['identity_type']);
        $this->assertSame(1, $payload['data']['user']['current_identity']['is_default']);
        $this->assertTrue($payload['data']['user']['current_identity']['has_basic_info']);
        $this->assertCount(1, $payload['data']['user']['identities']);
        $this->assertSame(RcIdentityType::Headhunter->value, $payload['data']['user']['identities'][0]['identity_type']);
        $this->assertDatabaseHas('rc_user_identities', [
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::Headhunter->value,
            'identity_name' => '猎头',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled->value,
        ]);
    }

    public function test_refresh_token_rejects_invalid_identity_type(): void
    {
        $user = User::factory()->create();

        $oldToken = new class implements ScopeAuthorizable
        {
            public mixed $responsible = null;

            public function revoke(): void {}

            public function can(string $scope): bool
            {
                return true;
            }

            public function cant(string $scope): bool
            {
                return false;
            }
        };

        $userMock = Mockery::mock($user)->makePartial();
        $userMock->shouldReceive('token')->never();

        $factoryMock = Mockery::mock(PersonalAccessTokenFactory::class);
        $factoryMock->shouldReceive('make')->never();
        $this->app->instance(PersonalAccessTokenFactory::class, $factoryMock);

        $request = Request::create('/rc/auth/refresh-token', 'POST', [
            'identity_type' => 999,
        ]);
        $request->setUserResolver(static fn () => $userMock);

        $this->expectException(ValidationException::class);

        $controller = new AuthController;
        $controller->refreshToken($request);
    }

    public function test_refreshed_rc_token_can_access_rc_guarded_route(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('RC Test', 'rc_users');

        $user = User::factory()->create();

        $bootstrapToken = app(PersonalAccessTokenFactory::class)->make(
            $user->getAuthIdentifier(),
            'rc-bootstrap',
            [],
            'rc_users',
        )->accessToken;

        $refreshResponse = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$bootstrapToken,
            ])
            ->postJson('/rc/auth/refresh-token', [
                'identity_type' => RcIdentityType::Headhunter->value,
            ]);

        $refreshResponse->assertOk();

        $newToken = $refreshResponse->json('data.access_token');

        $this->assertIsString($newToken);
        $this->assertNotSame('', $newToken);

        $meResponse = $this
            ->withHeaders([
                'Authorization' => 'Bearer '.$newToken,
            ])
            ->getJson('/rc/auth/me');

        $meResponse->assertOk();
        $this->assertSame($user->id, $meResponse->json('data.user.id'));
    }
}
