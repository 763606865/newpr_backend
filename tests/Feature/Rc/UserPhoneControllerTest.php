<?php

namespace Tests\Feature\Rc;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserPhoneControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        config()->set('sms.driver', null);
    }

    public function test_lookup_phone_reports_availability(): void
    {
        $user = User::factory()->create([
            'phone' => '13800138000',
        ]);
        User::factory()->create([
            'phone' => '13800138001',
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/users/phone/lookup?phone=13800138001')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.phone', '13800138001')
            ->assertJsonPath('data.exists', true)
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.is_current_user', false);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/users/phone/lookup?phone=13800138002')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.exists', false)
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.is_current_user', false);
    }

    public function test_send_phone_verification_code_for_available_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '13800138000',
        ]);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/users/phone/verification-code', [
                'phone' => '13800138002',
            ])
            ->assertOk()
            ->assertJsonPath('code', 200);

        $this->assertTrue(Cache::has('auth:verification:change_phone:phone:'.md5('13800138002')));
    }

    public function test_update_phone_with_verification_code(): void
    {
        $user = User::factory()->create([
            'phone' => '13800138000',
        ]);

        config()->set('app.debug', true);
        config()->set('app.skip_accounts', ['13800138002']);

        $this->actingAs($user, 'rc')
            ->putJson('/rc/users/phone', [
                'phone' => '13800138002',
                'code' => '123456',
            ])
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.phone', '13800138002');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '13800138002',
        ]);
    }

    public function test_update_phone_rejects_occupied_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '13800138000',
        ]);
        User::factory()->create([
            'phone' => '13800138001',
        ]);

        $this->actingAs($user, 'rc')
            ->putJson('/rc/users/phone', [
                'phone' => '13800138001',
                'code' => '123456',
            ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '手机号已被其他用户使用。');
    }

    public function test_update_phone_rejects_invalid_code(): void
    {
        $user = User::factory()->create([
            'phone' => '13800138000',
        ]);

        $this->actingAs($user, 'rc')
            ->putJson('/rc/users/phone', [
                'phone' => '13800138002',
                'code' => '123456',
            ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '验证码错误或已失效。');
    }
}
