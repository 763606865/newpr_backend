<?php

namespace Tests\Unit\Models;

use App\Enums\UserGender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_avatar_returns_accessible_oss_url(): void
    {
        $disk = Mockery::mock();
        $disk->shouldReceive('url')
            ->once()
            ->with('uploads/users/avatar/example.jpg')
            ->andReturn('https://cdn.example.com/uploads/users/avatar/example.jpg');

        Storage::shouldReceive('disk')
            ->once()
            ->with('oss')
            ->andReturn($disk);

        $user = User::factory()->create();
        $user->forceFill(['avatar' => 'uploads/users/avatar/example.jpg'])->save();

        $this->assertSame('uploads/users/avatar/example.jpg', $user->avatar);
        $this->assertSame(
            'https://cdn.example.com/uploads/users/avatar/example.jpg',
            $user->display_avatar,
        );
    }

    public function test_display_avatar_returns_null_when_avatar_is_empty(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->avatar);
        $this->assertNull($user->display_avatar);
    }

    public function test_mask_name_uses_gender_suffix(): void
    {
        $male = User::factory()->create([
            'name' => '刘阳',
            'gender' => UserGender::Male,
        ]);
        $female = User::factory()->create([
            'name' => '刘阳',
            'gender' => UserGender::Female,
        ]);
        $unknown = User::factory()->create([
            'name' => '刘阳',
            'gender' => UserGender::Unknown,
        ]);

        $this->assertSame('刘先生', $male->mask_name);
        $this->assertSame('刘女士', $female->mask_name);
        $this->assertSame('刘总', $unknown->mask_name);
    }
}
