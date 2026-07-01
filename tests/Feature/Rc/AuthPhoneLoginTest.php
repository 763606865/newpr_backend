<?php

namespace Tests\Feature\Rc;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class AuthPhoneLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_new_phone_login_returns_token_for_selected_identity_type(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('RC Phone Login Test', 'rc_users');

        config()->set('app.debug', true);
        config()->set('app.skip_accounts', ['13800138000']);

        $response = $this->postJson('/rc/auth/phone-login', [
            'phone' => '13800138000',
            'code' => '123456',
            'rc_user_identity_type' => RcIdentityType::Recruiter->value,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.phone', '13800138000')
            ->assertJsonPath('data.user.current_identity.identity_type', RcIdentityType::Recruiter->value)
            ->assertJsonPath('data.user.current_identity.identity_name', '招聘方')
            ->assertJsonPath('data.user.current_identity.is_default', 1);

        $user = User::query()->where('phone', '13800138000')->firstOrFail();
        $identity = UserIdentity::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame(RcIdentityType::Recruiter, $identity->identity_type);
        $this->assertSame(RcIdentityStatus::Enabled, $identity->status);

        $this->assertDatabaseHas('oauth_access_tokens', [
            'user_id' => $user->id,
            'name' => 'rc',
            'responsible_type' => UserIdentity::class,
            'responsible_id' => $identity->id,
        ]);
    }

    public function test_existing_phone_login_switches_token_to_selected_identity_type(): void
    {
        app(ClientRepository::class)->createPersonalAccessGrantClient('RC Phone Login Test', 'rc_users');

        config()->set('app.debug', true);
        config()->set('app.skip_accounts', ['13800138001']);

        $user = User::factory()->create([
            'phone' => '13800138001',
        ]);

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $response = $this->postJson('/rc/auth/phone-login', [
            'phone' => '13800138001',
            'code' => '123456',
            'rc_user_identity_type' => RcIdentityType::Recruiter->value,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.user.current_identity.identity_type', RcIdentityType::Recruiter->value)
            ->assertJsonPath('data.user.current_identity.is_default', 1);

        $recruiterIdentity = UserIdentity::query()
            ->where('user_id', $user->id)
            ->where('identity_type', RcIdentityType::Recruiter)
            ->firstOrFail();

        $this->assertDatabaseHas('rc_user_identities', [
            'id' => $recruiterIdentity->id,
            'is_default' => 1,
        ]);
        $this->assertDatabaseHas('oauth_access_tokens', [
            'user_id' => $user->id,
            'name' => 'rc',
            'responsible_type' => UserIdentity::class,
            'responsible_id' => $recruiterIdentity->id,
        ]);
    }
}
