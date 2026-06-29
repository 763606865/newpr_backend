<?php

namespace Tests\Unit\Services;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Models\Area;
use App\Models\Rc\Announcement;
use App\Services\RcAnnouncementSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RcAnnouncementSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_public_announcements_by_keyword(): void
    {
        Announcement::query()->create([
            'title' => '中粮集团2026届校园招聘',
            'publisher_name' => '中粮集团有限公司',
            'publisher_type' => CmsAnnouncementPublisherType::CentralEnterprise,
            'link_url' => 'https://example.com/cofco',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        Announcement::query()->create([
            'title' => '草稿公告',
            'publisher_name' => '测试企业',
            'link_url' => 'https://example.com/draft',
            'status' => CmsPublishStatus::Draft,
        ]);

        $paginator = RcAnnouncementSearchService::make()->search(15, [
            'keyword' => '中粮',
        ]);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('中粮集团2026届校园招聘', $paginator->items()[0]->title);
    }

    public function test_search_without_keyword_filters_by_city_via_database_when_elastic_driver(): void
    {
        Config::set('scout.driver', 'elastic');

        Area::query()->create([
            'name' => '苏州市',
            'code' => '320500',
            'parent_code' => '320000',
            'level' => 2,
            'type' => null,
        ]);

        $suzhou = Announcement::query()->create([
            'title' => '苏州地铁2026届招聘',
            'publisher_name' => '苏州轨道交通集团',
            'link_url' => 'https://example.com/suzhou',
            'status' => CmsPublishStatus::Published,
            'published_at' => now()->subDay(),
        ]);
        $suzhou->syncCityCodes(['320500']);

        Announcement::query()->create([
            'title' => '济南高速集团招聘',
            'publisher_name' => '山东高速集团',
            'link_url' => 'https://example.com/jinan',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $paginator = RcAnnouncementSearchService::make()->search(15, [
            'city_code' => '320500',
        ]);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('苏州地铁2026届招聘', $paginator->items()[0]->title);
    }

    public function test_search_includes_nationwide_announcements_for_city_filter(): void
    {
        Config::set('scout.driver', 'elastic');

        Announcement::query()->create([
            'title' => '全国校园招聘',
            'publisher_name' => '央企集团',
            'link_url' => 'https://example.com/nationwide',
            'is_nationwide' => true,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $paginator = RcAnnouncementSearchService::make()->search(15, [
            'city_code' => '320500',
        ]);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('全国校园招聘', $paginator->items()[0]->title);
    }

    public function test_search_sorts_top_announcements_first_without_keyword(): void
    {
        Config::set('scout.driver', 'elastic');

        Announcement::query()->create([
            'title' => '普通公告',
            'publisher_name' => '测试企业',
            'link_url' => 'https://example.com/normal',
            'is_top' => false,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        Announcement::query()->create([
            'title' => '置顶公告',
            'publisher_name' => '测试企业',
            'link_url' => 'https://example.com/top',
            'is_top' => true,
            'status' => CmsPublishStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $paginator = RcAnnouncementSearchService::make()->search(15, []);

        $this->assertSame(2, $paginator->total());
        $this->assertSame('置顶公告', $paginator->items()[0]->title);
        $this->assertSame('普通公告', $paginator->items()[1]->title);
    }
}
