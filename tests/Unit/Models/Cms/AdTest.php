<?php

namespace Tests\Unit\Models\Cms;

use App\Models\Cms\Ad;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AdTest extends TestCase
{
    public function test_image_url_returns_public_url_for_public_assets(): void
    {
        config()->set('filesystems.disks.oss.visibility', 'public');

        $ad = new Ad;
        $ad->image = 'ads/banner.jpg';

        $disk = Mockery::mock();
        $disk->shouldReceive('url')
            ->once()
            ->with('ads/banner.jpg')
            ->andReturn('https://cdn.example.com/ads/banner.jpg');

        Storage::shouldReceive('disk')
            ->once()
            ->with('oss')
            ->andReturn($disk);

        $this->assertSame('https://cdn.example.com/ads/banner.jpg', $ad->image_url);
    }

    public function test_image_url_returns_temporary_url_for_private_assets(): void
    {
        Carbon::setTestNow('2026-05-28 12:00:00');

        try {
            config()->set('filesystems.disks.oss.visibility', 'private');
            config()->set('filesystems.disks.oss.temporary_url_ttl', 120);

            $ad = new Ad;
            $ad->image = 'ads/private.jpg';

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

            $this->assertSame('https://signed.example.com/ads/private.jpg?token=abc', $ad->image_url);
        } finally {
            Carbon::setTestNow();
        }
    }
}
