<?php

namespace Tests\Unit\Resources\Rc;

use App\Models\Rc\Resume;
use App\Resources\Rc\RcResumeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class RcResumeResourceTest extends TestCase
{
    public function test_it_returns_avatar_path_and_display_url(): void
    {
        $disk = Mockery::mock();
        $disk->shouldReceive('url')
            ->once()
            ->with('uploads/rc/avatar/example.jpg')
            ->andReturn('https://cdn.example.com/uploads/rc/avatar/example.jpg');

        Storage::shouldReceive('disk')
            ->once()
            ->with('oss')
            ->andReturn($disk);

        $resume = new class extends Resume
        {
            public function getAttributes(): array
            {
                return ['avatar' => 'uploads/rc/avatar/example.jpg'];
            }
        };

        $payload = (new RcResumeResource($resume))->resolve(new Request);

        $this->assertSame('uploads/rc/avatar/example.jpg', $payload['avatar']);
        $this->assertSame('https://cdn.example.com/uploads/rc/avatar/example.jpg', $payload['display_avatar']);
    }

    public function test_it_returns_null_avatar_fields_when_avatar_is_empty(): void
    {
        $resume = new class extends Resume
        {
            public function getAttributes(): array
            {
                return ['avatar' => ''];
            }
        };

        $payload = (new RcResumeResource($resume))->resolve(new Request);

        $this->assertNull($payload['avatar']);
        $this->assertNull($payload['display_avatar']);
    }
}
