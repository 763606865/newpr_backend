<?php

namespace Tests\Unit;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsAnnouncementType;
use App\Enums\CmsPublishStatus;
use App\Models\Cms\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcement_casts_new_fields(): void
    {
        $announcement = Announcement::query()->create([
            'title' => '测试公告',
            'type' => CmsAnnouncementType::JobRecruitment,
            'publisher_type' => CmsAnnouncementPublisherType::StateOwnedEnterprise,
            'province_code' => '360000',
            'city_code' => '360100',
            'files' => [['name' => '附件.pdf', 'url' => 'https://example.com/file.pdf']],
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $announcement->refresh();

        $this->assertSame(CmsAnnouncementType::JobRecruitment, $announcement->type);
        $this->assertSame(CmsAnnouncementPublisherType::StateOwnedEnterprise, $announcement->publisher_type);
        $this->assertSame('360100', $announcement->city_code);
        $this->assertSame('附件.pdf', $announcement->files[0]['name']);
    }

    public function test_for_region_scope_matches_global_and_local_announcements(): void
    {
        $global = Announcement::query()->create([
            'title' => '全站公告',
            'type' => CmsAnnouncementType::System,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $local = Announcement::query()->create([
            'title' => '南昌公告',
            'type' => CmsAnnouncementType::LocalPolicy,
            'city_code' => '360100',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        Announcement::query()->create([
            'title' => '北京公告',
            'type' => CmsAnnouncementType::LocalPolicy,
            'city_code' => '110100',
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $ids = Announcement::query()
            ->forRegion('360100')
            ->pluck('id')
            ->all();

        $this->assertContains($global->id, $ids);
        $this->assertContains($local->id, $ids);
        $this->assertCount(2, $ids);
    }

    public function test_increment_read_count(): void
    {
        $announcement = Announcement::query()->create([
            'title' => '阅读统计',
            'type' => CmsAnnouncementType::System,
            'status' => CmsPublishStatus::Published,
            'published_at' => now(),
        ]);

        $announcement->incrementReadCount();

        $this->assertSame(1, $announcement->refresh()->read_count);
    }
}
