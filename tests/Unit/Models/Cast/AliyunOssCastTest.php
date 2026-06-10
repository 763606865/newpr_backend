<?php

namespace Tests\Unit\Models\Cast;

use App\Models\Cast\AliyunOss;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AliyunOssCastTest extends TestCase
{
    public function test_get_returns_relative_path_for_runtime_usage(): void
    {
        $cast = new AliyunOss;
        $model = new class extends Model {};

        $value = $cast->get($model, 'image', '/ads/banner.jpg', []);

        $this->assertSame('ads/banner.jpg', $value);

        $fromFullUrl = $cast->get(
            $model,
            'image',
            'https://newpr-develop.oss-cn-hangzhou.aliyuncs.com/ads/banner.jpg',
            [],
        );

        $this->assertSame('ads/banner.jpg', $fromFullUrl);
    }

    public function test_set_normalizes_full_url_to_relative_path(): void
    {
        $cast = new AliyunOss;
        $model = new class extends Model {};

        $value = $cast->set(
            $model,
            'image',
            'https://newpr-develop.oss-cn-hangzhou.aliyuncs.com/ads/banner.jpg',
            [],
        );

        $this->assertSame('ads/banner.jpg', $value);
    }

    public function test_from_model_uses_cast_configuration_for_display_url(): void
    {
        Carbon::setTestNow('2026-05-28 12:00:00');

        try {
            $user = new class extends Model
            {
                protected function casts(): array
                {
                    return [
                        'avatar' => AliyunOss::class.':oss,private,120',
                    ];
                }
            };

            $disk = Mockery::mock();
            $disk->shouldReceive('temporaryUrl')
                ->once()
                ->withArgs(function (string $path, $expiration): bool {
                    return $path === 'uploads/avatar.jpg'
                        && $expiration instanceof \DateTimeInterface
                        && $expiration->getTimestamp() === now()->addSeconds(120)->getTimestamp();
                })
                ->andReturn('https://signed.example.com/uploads/avatar.jpg?token=abc');

            Storage::shouldReceive('disk')
                ->once()
                ->with('oss')
                ->andReturn($disk);

            $url = AliyunOss::fromModel($user, 'avatar')->toDisplayUrl('uploads/avatar.jpg');

            $this->assertSame('https://signed.example.com/uploads/avatar.jpg?token=abc', $url);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_serialize_returns_public_url_for_public_assets(): void
    {
        $cast = new AliyunOss('oss', 'public', 3600);
        $model = new class extends Model {};

        $disk = Mockery::mock();
        $disk->shouldReceive('url')
            ->once()
            ->with('ads/banner.jpg')
            ->andReturn('https://cdn.example.com/ads/banner.jpg');

        Storage::shouldReceive('disk')
            ->once()
            ->with('oss')
            ->andReturn($disk);

        $value = $cast->serialize($model, 'image', 'ads/banner.jpg', []);

        $this->assertSame('https://cdn.example.com/ads/banner.jpg', $value);
    }

    public function test_serialize_returns_temporary_url_for_private_assets(): void
    {
        Carbon::setTestNow('2026-05-28 12:00:00');

        try {
            $cast = new AliyunOss('oss', 'private', 120);
            $model = new class extends Model {};

            $disk = Mockery::mock();
            $disk->shouldReceive('temporaryUrl')
                ->once()
                ->withArgs(function (string $path, $expiration): bool {
                    return $path === 'ads/private.jpg'
                        && $expiration instanceof \DateTimeInterface
                        && $expiration->getTimestamp() === now()->addSeconds(120)->getTimestamp();
                })
                ->andReturn('https://signed.example.com/ads/private.jpg?token=abc');

            Storage::shouldReceive('disk')
                ->once()
                ->with('oss')
                ->andReturn($disk);

            $value = $cast->serialize($model, 'image', 'ads/private.jpg', []);

            $this->assertSame('https://signed.example.com/ads/private.jpg?token=abc', $value);
        } finally {
            Carbon::setTestNow();
        }
    }
}
