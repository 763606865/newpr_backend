<?php

namespace Tests\Feature\Cms;

use App\Enums\CmsAdType;
use App\Enums\CmsStatus;
use App\Models\Cms\Ad;
use App\Models\Cms\AdSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AdControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_returns_enabled_ads_by_slot_code(): void
    {
        $this->mockOssUrl();

        $slot = AdSlot::query()->create([
            'name' => '首页广告位',
            'code' => 'home.sidebar',
            'type' => CmsAdType::Image,
            'width' => 320,
            'height' => 120,
            'status' => CmsStatus::Enabled,
            'sort' => 1,
        ]);

        Ad::query()->create([
            'slot_id' => $slot->id,
            'title' => '启用广告',
            'type' => CmsAdType::Image,
            'image' => 'uploads/cms/ad/enabled.jpg',
            'link_url' => 'https://example.com/enabled',
            'status' => CmsStatus::Enabled,
            'sort' => 2,
        ]);

        Ad::query()->create([
            'slot_id' => $slot->id,
            'title' => '禁用广告',
            'type' => CmsAdType::Image,
            'image' => 'uploads/cms/ad/disabled.jpg',
            'status' => CmsStatus::Disabled,
            'sort' => 1,
        ]);

        $response = $this->getJson('/cms/ads?code=home.sidebar');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.ad_slot.code', 'home.sidebar')
            ->assertJsonPath('data.ad_slot.type', CmsAdType::Image->value)
            ->assertJsonPath('data.ads.0.title', '启用广告')
            ->assertJsonPath('data.ads.0.image', 'uploads/cms/ad/enabled.jpg')
            ->assertJsonPath('data.ads.0.image_url', 'https://cdn.example.com/uploads/cms/ad/enabled.jpg')
            ->assertJsonCount(1, 'data.ads');
    }

    public function test_it_filters_ads_by_city_code_and_keeps_global_ads(): void
    {
        $this->mockOssUrl();

        $slot = AdSlot::query()->create([
            'name' => '城市广告位',
            'code' => 'city.home',
            'status' => CmsStatus::Enabled,
        ]);

        Ad::query()->create([
            'slot_id' => $slot->id,
            'city_code' => null,
            'title' => '全站广告',
            'image' => 'uploads/cms/ad/global.jpg',
            'status' => CmsStatus::Enabled,
            'sort' => 1,
        ]);

        Ad::query()->create([
            'slot_id' => $slot->id,
            'city_code' => '360100',
            'title' => '南昌广告',
            'image' => 'uploads/cms/ad/nanchang.jpg',
            'status' => CmsStatus::Enabled,
            'sort' => 2,
        ]);

        Ad::query()->create([
            'slot_id' => $slot->id,
            'city_code' => '440300',
            'title' => '深圳广告',
            'image' => 'uploads/cms/ad/shenzhen.jpg',
            'status' => CmsStatus::Enabled,
            'sort' => 3,
        ]);

        $response = $this->getJson('/cms/ads?code=city.home&city_code=360100');

        $response
            ->assertOk()
            ->assertJsonPath('data.ads.0.title', '全站广告')
            ->assertJsonPath('data.ads.1.title', '南昌广告')
            ->assertJsonCount(2, 'data.ads');
    }

    public function test_it_requires_slot_code(): void
    {
        $this->getJson('/cms/ads')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_it_returns_not_found_when_slot_is_missing_or_disabled(): void
    {
        AdSlot::query()->create([
            'name' => '禁用广告位',
            'code' => 'disabled.slot',
            'status' => CmsStatus::Disabled,
        ]);

        $this->getJson('/cms/ads?code=missing.slot')->assertNotFound();
        $this->getJson('/cms/ads?code=disabled.slot')->assertNotFound();
    }

    private function mockOssUrl(): void
    {
        $disk = Mockery::mock();
        $disk->shouldReceive('url')
            ->zeroOrMoreTimes()
            ->andReturnUsing(static fn (string $path): string => 'https://cdn.example.com/'.$path);

        Storage::shouldReceive('disk')
            ->zeroOrMoreTimes()
            ->with('oss')
            ->andReturn($disk);
    }
}
