<?php

namespace Tests\Feature;

use App\Enums\CmsLinkType;
use App\Enums\CmsOpenTarget;
use App\Enums\CmsStatus;
use App\Models\Cms\Banner;
use App\Models\Cms\BannerPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_returns_banners_by_position_code_and_city_code(): void
    {
        $position = BannerPosition::query()->create([
            'name' => '首页 Banner',
            'code' => 'zcgz.index.banner-1',
            'status' => CmsStatus::Enabled,
        ]);

        Banner::query()->create([
            'position_id' => $position->id,
            'city_code' => '360100',
            'title' => '南昌 Banner',
            'image' => 'banner/nanchang.png',
            'link_type' => CmsLinkType::Internal,
            'link_url' => '/jobs',
            'target' => CmsOpenTarget::Self,
            'status' => CmsStatus::Enabled,
            'sort' => 1,
        ]);

        Banner::query()->create([
            'position_id' => $position->id,
            'city_code' => null,
            'title' => '全国 Banner',
            'image' => 'banner/national.png',
            'link_type' => CmsLinkType::Internal,
            'link_url' => '/home',
            'target' => CmsOpenTarget::Self,
            'status' => CmsStatus::Enabled,
            'sort' => 2,
        ]);

        Banner::query()->create([
            'position_id' => $position->id,
            'city_code' => '440100',
            'title' => '广州 Banner',
            'image' => 'banner/guangzhou.png',
            'link_type' => CmsLinkType::Internal,
            'link_url' => '/about',
            'target' => CmsOpenTarget::Self,
            'status' => CmsStatus::Enabled,
            'sort' => 3,
        ]);

        $this->getJson('/cms/home/banners?banner_position_code=zcgz.index.banner-1&city_code=360100')
            ->assertOk()
            ->assertJsonCount(2, 'data.banners')
            ->assertJsonPath('data.banner_position.code', 'zcgz.index.banner-1')
            ->assertJsonPath('data.banners.0.title', '南昌 Banner')
            ->assertJsonPath('data.banners.1.title', '全国 Banner');
    }

    public function test_index_returns_not_found_for_missing_banner_position_code(): void
    {
        $this->getJson('/cms/home/banners?banner_position_code=missing-code')
            ->assertNotFound();
    }
}
