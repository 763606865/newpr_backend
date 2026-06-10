<?php

namespace Tests\Unit\Resources\SApi;

use App\Models\User;
use App\Resources\SApi\SApiUserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class SApiUserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_avatar_path_and_display_url(): void
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

        $payload = (new SApiUserResource($user))->resolve(new Request);

        $this->assertSame('uploads/users/avatar/example.jpg', $payload['avatar']);
        $this->assertSame('https://cdn.example.com/uploads/users/avatar/example.jpg', $payload['display_avatar']);
    }
}
